<?php namespace Model;


use Lib\CliHelper;
use Lib\DB;

class SpdCheck {
    use CliHelper;

    public static $keywords = [];

    /**
     * keyword:
     * [
     *    'trust' =>[
     *        'normal'=>[
     *            [id,operate,type,position,value,delta,max_expire,time_avail]
     *        ],
     *        'user_name'  =>[
     *            'user_name'=>[id,operate,type,position,value,delta,max_expire,time_avail]
     *        ],
     *        'user_portrait'  =>[
     *            'user_portrait'=>[id,operate,type,position,value,delta,max_expire,time_avail]
     *        ],
     *    ],
     *    'normal'=>[
     *    ],
     *    'alert' =>[
     *    ],
     * ]
     */
    /**
     * post:
     * [
     *    'cid'=>[
     *        'id'             =>'',
     *        'fid'            =>'',
     *        'tid'            =>'',
     *        'pid'            =>'',
     *        'cid'            =>'',
     *        'user_name'      =>'',
     *        'user_id'        =>'',
     *        'user_portrait'  =>'',
     *        'index_p'        =>'',
     *        'index_c'        =>'',
     *        'time_pub'       =>'',
     *        'time_scan'      =>'',
     *        'content'        =>'',
     *        'title'          =>'',[optional]
     *    ],
     * ]
     */

    public function loadKeywords() {
        self::line('load keywords start', 1);
        self::tick();
        $time  = date('Y-m-d H:i:s');
        $query = DB::query(
            'select 
id,operate,type,position,value,delta,max_expire,time_avail 
from spd_keyword 
where status=1 and time_avail>:time',
            ['time' => $time]
        );
        self::line('keyword db loaded');
        self::tick();
        foreach ($query as $item) {
            self::$keywords [] = [
                'id'         => $item['id'],
                'operate'    => SpdOpMap::parseBinary('operate', $item['operate']),
                'type'       => SpdOpMap::parseBinary('type', $item['type']),
                'position'   => SpdOpMap::parseBinary('position', $item['position']),
                'value'      => $item['value'],
                'delta'      => $item['delta'],
                'max_expire' => $item['max_expire'],
                'time_avail' => $item['time_avail'],
            ];
//			var_dump($result);
//			break;
        }
        self::line('keywords arr loaded');
        self::tick(true);
        return self::$keywords;
    }


    public function groupKeyword() {
        self::line('parse keywords start', 1);
        self::tick();
        $group = [
            'trust'  => [
                'normal'        => [],
                'user_name'     => [],
                'user_portrait' => [],
            ],
            'normal' => [
                'normal'        => [],
                'user_name'     => [],
                'user_portrait' => [],
            ],
            'alert'  => [
                'normal'        => [],
                'user_name'     => [],
                'user_portrait' => [],
            ],
        ];
        //分组
        foreach (self::$keywords as $item) {
            //扫描优先级分组
            $g1 = 'normal';
            if ($item['operate']['trust']) {
                $g1 = 'trust';
            } elseif ($item['operate']['alert']) {
                $g1 = 'alert';
            }
            //user单独分组
            if ($item['position']['user_name']) {
                $group[$g1]['user_name'][$item['value']] = $item;
            } elseif ($item['position']['user_portrait']) {
                $group[$g1]['user_portrait'][$item['value']] = $item;
            } else {
                $group[$g1]['normal'][] = $item;
            }
        }
        self::line('keyword statics:');
        self::line('trust:normal    :' . sizeof($group['trust']['normal']));
        self::line('trust:user      :' . sizeof($group['trust']['user_name']));
        self::line('trust:portrait  :' . sizeof($group['trust']['user_portrait']));
        self::line('normal:normal   :' . sizeof($group['normal']['normal']));
        self::line('normal:user     :' . sizeof($group['normal']['user_name']));
        self::line('normal:portrait :' . sizeof($group['normal']['user_portrait']));
        self::line('alert:normal    :' . sizeof($group['alert']['normal']));
        self::line('alert:user      :' . sizeof($group['alert']['user_name']));
        self::line('alert:portrait  :' . sizeof($group['alert']['user_portrait']));
        self::line('group keywords finished');
        self::tick();
        return $group;
    }

    private static function loadPost() {
        self::line('load post start', 1);
        $postList = DB::query('select 
id,tid,pid,spp.cid,
user_name,user_id,user_portrait,
index_p,index_c,
time_pub,spp.time_scan,
content
from spd_post spp left join spd_post_content spc on spp.cid=spc.cid
where spp.time_operate is null 
;');
        self::line('post std loaded');
        self::tick();
        $targetPostList = [];
        //获取tid并写入到数组
        $targetTidList = [];
        foreach ($postList as $post) {
            $item                         = (array)$post;
            $targetPostList[$item['cid']] = $item;
            if ($item['index_p'] == 1) {
                $targetTidList[] = $item['tid'];
            }
        }
        $titleList = [];
        //获取标题
        if (!empty($targetTidList)) {
            $titles = DB::select('select tid,title
from spd_post_title where tid in (' . implode(',', $targetTidList) . ');');
            foreach ($titles as $title) {
                $titleList[$title->tid] = $title->title;
            }
        }
        self::line('post title loaded');
        self::tick();
        //这里用foreach肯定有效率问题[引用]，但是懒得管了
        foreach ($targetPostList as $k => $item) {
            if (empty($titleList[$item['tid']])) continue;
            if ($item['index_p'] != 1) continue;
            $targetPostList[$k]['title'] = $titleList[$item['tid']];
        }
        self::line('post statics:');
        self::line('size  :' . sizeof($targetPostList));
        self::tick();
        return $targetPostList;
    }

    private static function checkPost($post, $keywordGroup) {
        $matched = [];
        foreach ($keywordGroup as $keywordList) {
//			var_dump($post['user_name']);
//			var_dump($post['user_name']);
//			var_dump(isset($keywordList['user_name'][$post['user_name']]));
            if (isset($keywordList['user_name'][$post['user_name']])) {
                $matched[] = $keywordList['user_name'][$post['user_name']];
                self::line('matched:user_name:' . $post['user_name']);
            }
            if (isset($keywordList['user_portrait'][$post['user_portrait']])) {
                $matched[] = $keywordList['user_portrait'][$post['user_portrait']];
                self::line('matched:user_portrait:' . $post['user_portrait']);
            }
            foreach ($keywordList['normal'] as $keyword) {
                foreach ($keyword['position'] as $position => $ifP) {
                    if (!$ifP) continue;
                    if (empty($post[$position])) continue;
                    foreach ($keyword['type'] as $type => $ifT) {
                        if (!$ifT) continue;
                        if (!self::matchVal($post[$position], $keyword['value'], $type)) continue;
                        $matched[$keyword['id']] = $keyword;
                        self::line('matched:keyword:' . $keyword['value']);
                        break(2);
                    }
                }
            }
            if (!empty($matched)) break;
        }
        return $matched;
    }

    private static function writePost($postList) {
        self::line('write post start', 1);
        if (empty($postList)) return false;
        $time      = date('Y-m-d H:i:s');
        $checkedId = [];
        foreach ($postList as $post) {
            $checkedId[] = $post['id'];
        }
        self::bathQuery("update spd_post set time_operate = '$time' where id in ", [$checkedId]);
        return true;
    }

    private static function writeOperate($operateList) {
        self::line('write operate start', 1);
        $targetOperateList = [];
        $time              = date('Y-m-d H:i:s');
        foreach ($operateList as $item) {
            $operateMeta = [
                'id'      => array_column($item['keywords'], 'id'),
                'operate' => [],
                'reason'  => array_column($item['keywords'], 'value'),
            ];
            foreach ($item['keywords'] as $keyword) {
                $operate                = array_keys(array_filter($keyword['operate']));
                $operateMeta['operate'] = array_merge($operateMeta['operate'], $operate);
            }
            $operateMeta['operate'] = array_keys(array_flip($operateMeta['operate']));
            //
            $targetOperateList[] = [
                //'id'             => '',
                'post_id'        => $item['post']['id'],
                //'fid'            => $item['post']['fid'],
                'tid'            => $item['post']['tid'],
                'pid'            => $item['post']['pid'],
                'cid'            => $item['post']['cid'],
                'user_name'      => $item['post']['user_name'],
                'user_id'        => $item['post']['user_id'],
                'user_portrait'  => $item['post']['user_portrait'],
                'operate_id'     => implode(',', $operateMeta['id']),
                'operate'        => SpdOpMap::writeBinary('operate', $operateMeta['operate']),
                'operate_reason' => '命中：' . implode(',', $operateMeta['reason']),
                'time_operate'   => $time,
                //'execute_result' => null,
                //'time_execute'   => null,
            ];
        }
        self::bathQuery('insert into spd_log_operate 
(post_id, tid, pid, cid, user_name, user_id, user_portrait, operate_id, operate, operate_reason,time_operate) values ', $targetOperateList);
        return true;
    }

    private static function writeKeyword($keywordList) {
        self::line('write keyword start');
        $time = time();
        //更新关键词的有效期时间戳
        foreach ($keywordList as $keyword) {
            if (empty($keyword['delta']) || empty($keyword['max_expire'])) continue;
            $curAvail    = strtotime($keyword['time_avail']);
            $maxAvail    = $time + $keyword['max_expire'] * 86400;
            $targetAvail = $curAvail + $keyword['delta'] * 86400;
            $targetAvail = min($targetAvail, $maxAvail);
            if ($targetAvail <= $curAvail) continue;
            DB::update('update spd_keyword set time_avail=:targetAvail where id=:id', [
                'targetAvail' => date('Y-m-d H:i:s', $targetAvail),
                'id'          => $keyword['id'],
            ]);
        }
        return true;
    }

    public static function checkIt() {
        self::line('check post start');
        $keywordList  = self::loadKeywords();
        $keywordGroup = self::groupKeyword($keywordList);
        $postList     = self::loadPost();
        //
        self::line('checking post');
        $checkedPost      = [];
        $postToOperate    = [];
        $keywordToOperate = [];
        $counter          = 0;
        foreach ($postList as $post) {
            if (!(($counter += 1) % 100)) {
                echo 'to:' . $counter . "\n";
                self::tick();
            }
            $checkedKeywords = self::checkPost($post, $keywordGroup);
            $checkedPost[]   = $post;
            if (!sizeof($checkedKeywords)) continue;
            $postToOperate[]  = [
                'post'     => $post,
                'keywords' => $checkedKeywords,
            ];
            $keywordToOperate += $checkedKeywords;
        }
        self::line('check statics:');
        self::line('post checked     :' . sizeof($checkedPost));
        self::line('post to operate  :' . sizeof($postToOperate));
        self::line('keyword to update:' . sizeof($keywordToOperate));
        self::tick();
        //
//		DB::enableQueryLog();
//		DB::beginTransaction();
        self::writePost($checkedPost);
        self::writeOperate($postToOperate);
        self::writeKeyword($keywordToOperate);
//		DB::rollBack();
//		var_dump(DB::getQueryLog());
        self::line('check post finished:');
        self::tick();
        return true;
    }

    /**
     * @internal
     * 根据指定的匹配方式匹配关键词
     *
     * @param $value     string    所在位置的内容
     * @param $target    string    关键词
     * @param $matchType string    匹配类型
     * @return boolean
     *
     */
    private static function matchVal($value, $target, $matchType) {
//		ScannerFunc::dumpAndLog('|||||||||||||');
//		ScannerFunc::dumpAndLog('||value    ||' . '{$value}');
//		ScannerFunc::dumpAndLog('||target   ||' . '{$target}');
//		ScannerFunc::dumpAndLog('||matchType||' . '{$matchType}');
        $result = false;
        switch ($matchType) {
            case 'contain':
                //
                $result = stripos($value, $target) !== false;
                break;
            case 'space':
                $target = explode(' ', $target);
                foreach ($target as $kw) {
                    //跳过长度为0的
                    //话说这种写法比empty快
                    if (strlen($kw) == 0) continue;
                    $result = stripos($value, $kw) !== false;
                    if (!$result) break;
                }
                break;
            case 'regex':
//				$value  = preg_quote($value);
                $result = !!preg_match('/' . $target . '/im', $value);
                break;
            case 'match':
            default:
                $result = $target == $value;
                break;
        }
        return $result;
    }


}