<?php namespace Model;

use Lib\CliHelper;
use Lib\DB;
use Lib\GenFunc;

class SpdOperate extends Kernel {
    public $header = [
        'pc'     => '',
        'mobile' => '',
    ];

    public function __construct($config) {
        $this->header = Settings::get('header');
    }

    use CliHelper;

    /**
     * forbid_guest
     * day: 1
     * fid: 10087515
     * tbs: bd5379bc40bf6ac21554623637
     * ie: gbk
     * user_name[]:
     * nick_name[]: 贴吧用户_76t5yb9
     * pid[]: 124313046649
     * portrait[]: 6efaa1a0
     * reason: 辱骂吧务，对吧务工作造成干扰，给予封禁处罚。
     */
    /**
     * delete_guest
     * commit_fr: pb
     * ie: utf-8
     * tbs: bd5379bc40bf6ac21554623637
     * kw: 火星笔记本
     * fid: 10087515
     * tid: 6049614717
     * is_vipdel: 0
     * pid: 124417973888
     * is_finf: 1
     */
    /**
     * boom_guest
     * commit_fr: pb
     * ie: utf-8
     * tbs: 68741b22443d56801554623686
     * kw: 火星笔记本
     * fid: 10087515
     * tid: 6049614717
     */

    private $url = [
        'forbid' => 'https://tieba.baidu.com/pmc/blockid',
        'delete' => 'https://tieba.baidu.com/f/commit/post/delete',
        'boom'   => 'https://tieba.baidu.com/f/commit/thread/delete',
        'loop'   => 'https://c.tieba.baidu.com/c/c/bawu/commitprison',
    ];

    /**
     * [[
     * 'id'      => '',
     * 'post_id' => '',
     * 'operate' => [],
     * 'tid'     => ''
     * 'pid'     => ''
     * 'cid'     => ''
     * 'is_lz'   => 0
     * 'uid'     => 0
     * 'user'    => [
     *      'userid'   => '',
     *      'portrait' => '',
     *      'username' => '',
     *      'nickname' => '',
     *  ],
     *  ]]
     */
    public function loadPost() {
        $postList       = DB::query('select 
so.id,so.post_id,so.operate ,
sp.fid,sp.tid,sp.pid,sp.cid,sp.is_lz,sp.uid,
sus.userid,sus.portrait,sus.username,sus.nickname
from spd_operate so FORCE index(`time_execute_operate`)
left join spd_post sp on so.post_id=sp.id
left join spd_user_signature sus on sp.uid=sus.id
-- left join spd_operate_content soc on so.id=soc.id
where so.time_execute is null and so.operate!=16
;');
        $targetPostList = [];
        foreach ($postList as $post) {
            $targetPostList[] = [
                'id'      => $post['id'],
                'post_id' => $post['post_id'],
                'operate' => SpdOpMap::parseBinary('operate', $post['operate']),
                'fid'     => $post['fid'],
                'tid'     => $post['tid'],
                'pid'     => $post['pid'],
                'cid'     => $post['cid'],
                'is_lz'   => $post['is_lz'],
                'user'    => [
                    'userid'   => $post['userid'],
                    'portrait' => $post['portrait'],
                    'username' => $post['username'],
                    'nickname' => $post['nickname'],
                ],
            ];
        }
        return $targetPostList;
    }

    public function execute($postData, $config = []) {
        self::line('execute:' . $postData['id']);
        $resTxt = [];
        foreach ($postData['operate'] as $operate => $if) {
            if ($if) continue;
            switch ($operate) {
                case 'trust':
                    $resTxt [] = $this->trust($postData, $config);
                    break;
                case 'delete':
                    $resTxt [] = $this->delete($postData, $config);
                    sleep(3);
                    break;
                case 'forbid':
                    $resTxt [] = $this->forbid($postData, $config);
                    sleep(3);
                    break;
                case 'boom':
                    $resTxt [] = $this->boom($postData, $config);
                    sleep(3);
                    break;
                case 'black':
                    $resTxt [] = $this->black($postData, $config);
                    break;
                case 'alert':
                    $resTxt [] = $this->alert($postData, $config);
                    break;
            }
        }
        return implode(',', $resTxt);
    }

    private function trust($post, $config) {
        return 'success';
    }

    private function delete($post, $config) {
        $result = GenFunc::curl(
            [
                CURLOPT_URL        => $this->url['delete'],
                CURLOPT_POST       => 1,
                CURLOPT_POSTFIELDS =>
                    http_build_query(
                        [
                            'commit_fr' => 'pb',
                            'ie'        => 'utf-8',
                            'tbs'       => $this->getTBS($config),
                            'kw'        => $config['kw'],
                            'fid'       => $config['fid'],
                            'tid'       => $post['tid'],
                            'is_vipdel' => '0',
                            'pid'       => !empty($post['cid']) ? $post['cid'] : $post['pid'],
                            'is_finf'   => '1',
                        ]
                    ),
                CURLOPT_COOKIE     => $config['cookie'],
                CURLOPT_HTTPHEADER => $this->header['pc'],
            ]);
        return $result;
    }

    private function forbid($post, $config) {
        $ifDup = DB::query(
            'select * from spd_log_forbid where uid=:uid and time_execute>:time',
            [
                'uid'          => $post['uid'],
                'time_execute' => date('Y-m-d H:i:s', time() - 43200),
            ]
        );
        if (!empty($ifDup)) {
            return 'has forbidden';
        }
        $result = GenFunc::curl(
            [
                CURLOPT_URL        => $this->url['forbid'],
                CURLOPT_POST       => 1,
                CURLOPT_POSTFIELDS =>
                    http_build_query(
                        [
                            'day'         => '1',
                            'fid'         => $config['fid'],
                            'tbs'         => $this->getTBS($config),
                            'ie'          => 'gbk',
                            'user_name[]' => !empty($post['user']['username']) ? $post['user']['username'] : '',
                            'nick_name[]' => !empty($post['user']['nickname']) ? $post['user']['nickname'] : '',
                            'pid[]'       => !empty($post['cid']) ? $post['cid'] : $post['pid'],
                            'portrait[]'  => !empty($post['user']['portrait']) ? $post['user']['portrait'] : '',
                            'reason'      => Settings::get('execute.forbid_reason'),
                        ]
                    ),
                CURLOPT_COOKIE     => $config['cookie'],
                CURLOPT_HTTPHEADER => $this->header['pc'],
            ]);
        DB::query(
            'insert into spd_log_forbid(uid, forbid_day, time_execute) VALUE (:uid, :forbid_day, :time_execute)',
            [
                'uid'          => $post['uid'],
                'forbid_day'   => 1,
                'time_execute' => date('Y-m-d H:i:s', time()),
            ]
        );
        return $result;
    }

    private function boom($post, $config) {
        $result = GenFunc::curl(
            [
                CURLOPT_URL        => $this->url['boom'],
                CURLOPT_POST       => 1,
                CURLOPT_POSTFIELDS =>
                    http_build_query(
                        [
                            'commit_fr' => 'pb',
                            'ie'        => 'utf-8',
                            'tbs'       => $this->getTBS($config),
                            'kw'        => $config['kw'],
                            'fid'       => $config['fid'],
                            'tid'       => $post['tid'],
                        ]
                    ),
                CURLOPT_COOKIE     => $config['cookie'],
                CURLOPT_HTTPHEADER => $this->header['pc'],
            ]);
        return $result;
    }

    private function black($post, $config) {
        return '';
    }

    private function alert($post, $config) {
        return '';
    }


    private function getTBS($config) {
        global $cache;
        $cacheKey = 'tieba_spider:tbs:' . $config['fid'];
        $ifCached = $cache->get($cacheKey);
        if (!empty($ifCached)) {
            self::line('tbs from cache:' . $ifCached);
            return $ifCached;
        }
        $curl = GenFunc::curl(
            [
                CURLOPT_URL        => 'http://tieba.baidu.com/dc/common/tbs',
                CURLOPT_COOKIE     => $config['cookie'],
                CURLOPT_HTTPHEADER => $this->header['pc'],
            ]
        );
        if (empty($curl)) {
            self::line('load tbs curl failed');
            return false;
        }
        $curl = json_decode($curl, true);
        if (empty($curl['tbs'])) {
            self::line('load tbs failed');
            return false;
        }
        $cache->setex($cacheKey, 300, $curl['tbs']);
        self::line('tbs from web:' . $curl['tbs']);
        //整理处理结果
        return $curl['tbs'];
    }

    // -----------------------------------------------------------
    // -- old
    // -----------------------------------------------------------
    /*
        public static function execute() {
            echo '=========== execute start ===========' . "\n";
            $logs = self::getLogs();
            foreach ($logs as $item) {
                echo 'execute item:' . $item['id'] . "\n";
                $txt = '';
                foreach ($item['operate'] as $operate => $if) {
                    if (!$if) continue;
                    echo '-------------' . "\n";
                    echo 'operate:' . $operate . "\n";
                    $subTxt = '';
                    switch ($operate) {
                        case 'trust':
                            $subTxt = self::trust($item);
                            break;
                        case 'delete':
                            $subTxt = self::delete($item);
                            sleep(3);
                            break;
                        case 'forbid':
                            $subTxt = self::forbid($item);
                            sleep(3);
                            break;
                        case 'boom':
                            $subTxt = self::boom($item);
                            sleep(3);
                            break;
                        case 'black':
                            $subTxt = self::black($item);
                            break;
                        case 'alert':
                            $subTxt = self::alert($item);
                            break;
                    }
                    echo 'operate result:' . ($subTxt ?: 'pass') . "\n";
                    if (!$subTxt) continue(2);
                    $txt .= $subTxt;
                }
                self::updateLog($item['id'], $txt);
            }
            echo '=========== execute finished:' . "\n";
            self::tick(false);
            self::global();
        }

        private static function getLogs() {
            $list   = DB::select('select
    id,tid,pid,cid,user_name,user_id,user_portrait,operate
    from spd_log_operate where time_execute is null and operate!=16;');
            $result = [];
            foreach ($list as $item) {
                $tmItem            = (array)$item;
                $tmItem['operate'] = self::parseBinary('operate', $tmItem['operate']);
                $result[]          = $tmItem;
            }
            return $result;
        }

        private static function updateLog($id, $result) {
            $time = date('Y-m-d H:i:s');
            DB::update('update spd_log_operate set execute_result=:result,time_execute=:time where id=:id', [
                'id'     => $id,
                'result' => $result,
                'time'   => $time,
            ]);
            return true;
        }

        private static function alert($input) {
            return false;
        }

        private static function forbid($input) {
            $ifDup = DB::select('select * from spd_log_forbid where
    (user_name=:user_name or user_portrait=:user_portrait or user_id=:user_id)
    and time_execute>:last_forbid;', [
                'user_name'     => $input['user_name'],
                'user_id'       => $input['user_id'],
                'user_portrait' => $input['user_portrait'],
                'last_forbid'   => date('Y-m-d H:i:s', time() - 86400 * 0.8),
            ]);
            if (!empty($ifDup)) return 'has forbidden';
            $result = GenFunc::exeCurl(self::$url['forbid'], [
                'post' => [
                    'day'         => '1',
                    'fid'         => Config::read('basic.fid'),
                    'tbs'         => self::getTBS(),
                    'ie'          => 'gbk',
                    'user_name[]' => !empty($input['user_name']) ? $input['user_name'] : '',
                    'nick_name[]' => '',
                    'pid[]'       => !empty($input['cid']) ? $input['cid'] : $input['pid'],
                    'portrait[]'  => !empty($input['user_portrait']) ? $input['user_portrait'] : '',
                    'reason'      => Config::read('execute.forbid_reason'),
                ],
            ], [
                                           CURLOPT_COOKIE     => Config::read('cookie'),
                                           CURLOPT_HTTPHEADER => self::$header['pc'],
                                       ]);
            if (empty($result['success'])) return false;
            DB::insert('insert into spd_log_forbid
    (user_name, user_id, user_portrait, forbid_day,time_execute) value
    (:user_name, :user_id, :user_portrait, :forbid_day,:time_execute);', [
                'user_name'     => $input['user_name'],
                'user_id'       => $input['user_id'],
                'user_portrait' => $input['user_portrait'],
                'forbid_day'    => 1,
                'time_execute'  => date('Y-m-d H:i:s'),
            ]);
            return $result['success'];
        }

        private static function delete($input) {
            $result = GenFunc::exeCurl(self::$url['delete'], [
                'post' => [
                    'commit_fr' => 'pb',
                    'ie'        => 'utf-8',
                    'tbs'       => self::getTBS(),
                    'kw'        => Config::read('basic.kw'),
                    'fid'       => Config::read('basic.fid'),
                    'tid'       => $input['tid'],
                    'is_vipdel' => '0',
                    'pid'       => !empty($input['cid']) ? $input['cid'] : $input['pid'],
                    'is_finf'   => '1',
                ],
            ], [
                                           CURLOPT_COOKIE     => Config::read('cookie'),
                                           CURLOPT_HTTPHEADER => self::$header['pc'],
                                       ]);
            if (empty($result['success'])) return false;
            return $result['success'];
        }

        private static function boom($input) {
            $result = GenFunc::exeCurl(self::$url['boom'], [
                'post' => [
                    'commit_fr' => 'pb',
                    'ie'        => 'utf-8',
                    'tbs'       => self::getTBS(),
                    'kw'        => Config::read('basic.kw'),
                    'fid'       => Config::read('basic.fid'),
                    'tid'       => $input['tid'],
                ],
            ], [
                                           CURLOPT_COOKIE     => Config::read('cookie'),
                                           CURLOPT_HTTPHEADER => self::$header['pc'],
                                       ]);
            if (empty($result['success'])) return false;
            return $result['success'];
        }

        private static function trust($input) {
            return 'trusted';
        }

        private static function black($input) {
            $time   = date('Y-m-d H:i:s');
            $target = !empty($input['user_name']) ? $input['user_name'] : $input['user_portrait'];
            $ifDup  = DB::select('select * from spd_keyword
    where value=:user_name
    and status>0
    and time_avail>:time
    ;',
                                 [
                                     'user_name' => $target,
                                     'time'      => $time,
                                 ]
            );
            if (empty($ifDup)) {
                $insData          = [
                    'value'  => $target,
                    'reason' => 'blacklist from' . ':' . $input['tid'] . ':' . $input['pid'] . ':' . $input['cid'],
                    'delta'  => Config::read('default.black.delta'),
                    'max'    => Config::read('default.black.max'),
                    'avail'  => '',
                ];
                $insData['avail'] = date('Y-m-d H:i:s', time() + $insData['max'] * 86400);
                //
                DB::insert('insert into spd_keyword(
    operate, type, position, value, reason, status, delta, max_expire, time_avail
    ) value (6,1,2,:value,:reason,1,:delta,:max,:avail);', $insData);
                return 'blacklisted';
            }
            $curKw       = (array)$ifDup[0];
            $curAvail    = strtotime($curKw['time_avail']);
            $maxAvail    = $time + $curKw['max_expire'] * 86400;
            $targetAvail = $curAvail + $curKw['delta'] * 86400;
            $targetAvail = min($targetAvail, $maxAvail);
            DB::update('update spd_keyword set time_avail=:new_avail', [
                'new_avail' => date('Y-m-d H:i:s', $targetAvail),
            ]);
            return 'blacklisted';
        }*/
}