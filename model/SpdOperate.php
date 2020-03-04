<?php namespace Model;

use Lib\CliHelper;
use Lib\DB;
use Lib\GenFunc;

class SpdOperate extends Kernel {
    public $header = [
        'pc'     => '',
        'mobile' => '',
    ];

    private $config = [
        'name'          => '',
        'kw'            => '',
        'fid'           => '',
        'user'          => '',
        'cookie'        => '',
        'scan'          => true,
        'operate'       => true,
        'forbid_reason' => '',
        'loop_day'      => '',
    ];

    public function __construct($config = []) {
        $this->header = Settings::get('header');
        $this->config = $config + $this->config;
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
     * 'reason'  => ''
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
so.id,so.post_id,so.operate,
soc.operate_reason, 
sp.tid,sp.pid,sp.cid,sp.is_lz,sp.uid,
sus.userid,sus.portrait,sus.username,sus.nickname
from spd_operate so FORCE index(`time_execute_operate`)
left join spd_post sp on so.post_id=sp.id
left join spd_user_signature sus on sp.uid=sus.id
left join spd_operate_content soc on so.id=soc.id
where so.time_execute is null and so.operate!=16 and sp.fid=:fid
;', ['fid' => $this->config['fid']]);
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
                'reason'  => $post['operate_reason'],
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

    public function execute($postData) {
        self::line('execute:' . $postData['id']);
        $resTxt = [];
        foreach ($postData['operate'] as $operate => $if) {
            if ($if) continue;
            switch ($operate) {
                case 'trust':
                    $resTxt [] = $this->trust($postData);
                    break;
                case 'delete':
                    $resTxt [] = $this->delete($postData);
                    sleep(3);
                    break;
                case 'forbid':
                    $resTxt [] = $this->forbid($postData);
                    sleep(3);
                    break;
                case 'boom':
                    $resTxt [] = $this->boom($postData);
                    sleep(3);
                    break;
                case 'black':
                    $resTxt [] = $this->black($postData);
                    break;
                case 'alert':
                    $resTxt [] = $this->alert($postData);
                    break;
            }
        }
        return implode(',', $resTxt);
    }

    private function trust($post) {
        return 'success';
    }

    private function delete($post) {
        $result = GenFunc::curl(
            [
                CURLOPT_URL        => $this->url['delete'],
                CURLOPT_POST       => 1,
                CURLOPT_POSTFIELDS =>
                    http_build_query(
                        [
                            'commit_fr' => 'pb',
                            'ie'        => 'utf-8',
                            'tbs'       => $this->getTBS(),
                            'kw'        => $this->config['kw'],
                            'fid'       => $this->config['fid'],
                            'tid'       => $post['tid'],
                            'is_vipdel' => '0',
                            'pid'       => !empty($post['cid']) ? $post['cid'] : $post['pid'],
                            'is_finf'   => '1',
                        ]
                    ),
                CURLOPT_COOKIE     => $this->config['cookie'],
                CURLOPT_HTTPHEADER => $this->header['pc'],
            ]);
        return $result;
    }

    private function forbid($post) {
        $ifDup = DB::query(
            'select * from spd_log_forbid where uid=:uid and fid=:fid and time_execute>:time',
            [
                'uid'          => $post['uid'],
                'fid'          => $this->config['fid'],
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
                            'fid'         => $this->config['fid'],
                            'tbs'         => $this->getTBS(),
                            'ie'          => 'gbk',
                            'user_name[]' => !empty($post['user']['username']) ? $post['user']['username'] : '',
                            'nick_name[]' => !empty($post['user']['nickname']) ? $post['user']['nickname'] : '',
                            'pid[]'       => !empty($post['cid']) ? $post['cid'] : $post['pid'],
                            'portrait[]'  => !empty($post['user']['portrait']) ? $post['user']['portrait'] : '',
                            'reason'      => $this->config['forbid_reason'],
                        ]
                    ),
                CURLOPT_COOKIE     => $this->config['cookie'],
                CURLOPT_HTTPHEADER => $this->header['pc'],
            ]);
        DB::query(
            'insert into spd_log_forbid(fid,uid, forbid_day, time_execute) VALUE (:fid, :uid, :forbid_day, :time_execute)',
            [
                'fid'          => $this->config['fid'],
                'uid'          => $post['uid'],
                'forbid_day'   => 1,
                'time_execute' => date('Y-m-d H:i:s', time()),
            ]
        );
        return $result;
    }

    private function boom($post) {
        $result = GenFunc::curl(
            [
                CURLOPT_URL        => $this->url['boom'],
                CURLOPT_POST       => 1,
                CURLOPT_POSTFIELDS =>
                    http_build_query(
                        [
                            'commit_fr' => 'pb',
                            'ie'        => 'utf-8',
                            'tbs'       => $this->getTBS(),
                            'kw'        => $this->config['kw'],
                            'fid'       => $this->config['fid'],
                            'tid'       => $post['tid'],
                        ]
                    ),
                CURLOPT_COOKIE     => $this->config['cookie'],
                CURLOPT_HTTPHEADER => $this->header['pc'],
            ]);
        return $result;
    }

    private function black($post) {
        $time  = date('Y-m-d H:i:s');
        $ifDup = DB::query('select * from spd_keyword
    where `value`=:uid
    and status>0
    and fid=:fid
    and time_avail>:time
    and position=2
    ;',
                           [
                               'fid'  => $this->config['fid'],
                               'uid'  => $post['uid'],
                               'time' => date('Y-m-d H:i:s'),
                           ]
        );
        if (!empty($ifDup)) {
            return 'already blacklisted';
        }
        DB::query('insert ignore into spd_keyword(
    fid,operate, type, position, value, reason, status, time_avail
    ) value (:fid,6,1,2,:value,:reason,1,:avail);', [
            'fid'    => $this->config['fid'],
            'value'  => $post['uid'],
            'reason' => $post['reason'],
            'avail'  => '2099-01-01 00:00:00',
        ]);
        return 'blacklisted';
    }

    private function alert($post) {
        return '';
    }


    private function getTBS() {
        global $cache;
        $cacheKey = 'tieba_spider:tbs:' . $this->config['fid'];
        $ifCached = $cache->get($cacheKey);
        if (!empty($ifCached)) {
            self::line('tbs from cache:' . $ifCached);
            return $ifCached;
        }
        $curl = GenFunc::curl(
            [
                CURLOPT_URL        => 'http://tieba.baidu.com/dc/common/tbs',
                CURLOPT_COOKIE     => $this->config['cookie'],
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

    public function writeExecuteResult($postData, $result) {
        DB::query(
            'update spd_operate set time_execute=current_timestamp where id=:id;',
            ['id' => $postData['id']]
        );
        DB::query(
            'update spd_operate_content set execute_result=:result where id=:id;',
            ['id' => $postData['id'], 'result' => $result,]
        );
        return true;
    }


    /**
     * 无需id的封禁接口
     * 和其他操作分开写，主要是套用了客户端接口所以操作会多一点点……
     * @param $userId
     * @return array
     */
    public function loop($userName) {
        self::line('loop');
        //BDUSS
        $bduss = '';
        preg_match(
            '/BDUSS=(\w+);/im',
            $this->config['cookie'],
            $bduss
        );
        if (sizeof($bduss) != 2) {
            self::line('error:get BDUSS failed');
            return [];
        }
        $bduss = $bduss[1];
        //
        $postArray = [
            'BDUSS' => $bduss,
            'day'   => 1,
            'fid'   => $this->config['fid'],
            'ntn'   => 'banid',
            'tbs'   => $this->getTBS(),
            'un'    => $userName,
            'word'  => $this->config['name'],
            'z'     => '1111111111',
        ];
        //sign
        $signString = '';
        foreach ($postArray as $key => $value) {
            $signString .= $key . '=' . $value;
        }
        $signString        .= Settings::get('basic.sign_salt');
        $postArray['sign'] = $signString;
        //
        $result = GenFunc::curl(
            [
                CURLOPT_URL        => $this->url['loop'],
                CURLOPT_COOKIE     => $this->config['cookie'],
                CURLOPT_HTTPHEADER => $this->header['mobile'],
                CURLOPT_POST       => 1,
                CURLOPT_POSTFIELDS =>
                    http_build_query($postArray),
            ]
        );
        //获取uid
        $checkUn = DB::query('select id from spd_user_signature where username=:username', ['username' => $userName]);
        if (empty($checkUn)) {
            return $result;
        }
        $uid = $checkUn[0]['id'];
        //
        DB::query(
            'insert into spd_log_forbid(fid,uid, forbid_day, time_execute) VALUE (:fid,:uid, :forbid_day, :time_execute)',
            [
                'fid'          => $this->config['fid'],
                'uid'          => $uid,
                'forbid_day'   => 1,
                'time_execute' => date('Y-m-d H:i:s'),
            ]
        );
        return $result;
    }
}