<?php namespace Model;


use Lib\CliHelper;
use Lib\DB;

class SpdCheck extends Kernel {
    use CliHelper;

    public static $keywords      = [];
    public static $keywordsGroup = [];

    /**
     * keyword:
     * [
     *    'trust' =>[
     *        'normal'=>[
     *            [id,operate,type,position,value,time_avail]
     *        ],
     *        'user_name'  =>[
     *            'user_name'=>[id,operate,type,position,value,time_avail]
     *        ],
     *        'user_portrait'  =>[
     *            'user_portrait'=>[id,operate,type,position,value,time_avail]
     *        ],
     *    ],
     *    'normal'=>[
     *    ],
     *    'alert' =>[
     *    ],
     * ]
     */


    /**
     * @return array
     *
     * [[
     *  'id'         =>'',
     *  'operate'    =>'',
     *  'type'       =>'',
     *  'position'   =>'',
     *  'value'      =>'',
     *  'time_avail' =>'',
     * ]]
     */
    public function loadKeywords() {
        self::line('load keywords start', 1);
        self::tick();
        $time  = date('Y-m-d H:i:s');
        $query = DB::query(
            'select 
id,operate,type,position,`value`,time_avail 
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
                'time_avail' => $item['time_avail'],
            ];
//			var_dump($result);
//			break;
        }
        self::line('keywords arr loaded');
        self::tick(true);
        return self::$keywords;
    }


    /**
     * @return array
     * [
     * 'trust'  => ['normal'   => [],'tid'=>[],'uid'=>[],],
     * 'normal' => ['normal'   => [],'tid'=>[],'uid'=>[],],
     * 'alert'  => ['normal'   => [],'tid'=>[],'uid'=>[],],
     * ]
     */
    public function groupKeyword() {
        self::line('parse keywords start', 1);
        self::tick();
        //几个name都拆开存储，方便使用
        $group = [
            'trust'  => [
                'normal' => [],
                //                'nickname' => [],
                //                'username' => [],
                //                'portrait' => [],
                //                'userid'   => [],
                'tid'    => [],
                'uid'    => [],
            ],
            'normal' => [
                'normal' => [],
                //                'nickname' => [],
                //                'username' => [],
                //                'portrait' => [],
                //                'userid'   => [],
                'tid'    => [],
                'uid'    => [],
            ],
            'alert'  => [
                'normal' => [],
                //                'nickname' => [],
                //                'username' => [],
                //                'portrait' => [],
                //                'userid'   => [],
                'tid'    => [],
                'uid'    => [],
            ],
        ];
        //分组
        foreach (self::$keywords as $item) {
            //扫描优先级分组
            $opLevel = 'normal';
            if ($item['operate']['trust']) {
                $opLevel = 'trust';
            } elseif ($item['operate']['alert']) {
                $opLevel = 'alert';
            }
            //user一系列直接匹配的参数单独分组
            if (false) {
//            } elseif ($item['position']['nickname']) {
//                $group[$opLevel]['nickname'][$item['value']] = $item;
//            } elseif ($item['position']['username']) {
//                $group[$opLevel]['username'][$item['value']] = $item;
//            } elseif ($item['position']['portrait']) {
//                $group[$opLevel]['portrait'][$item['value']] = $item;
//            } elseif ($item['position']['userid']) {
//                $group[$opLevel]['userid'][$item['value']] = $item;
            } elseif ($item['position']['uid']) {
                $group[$opLevel]['uid'][$item['value']] = $item;
            } elseif ($item['position']['tid']) {
                $group[$opLevel]['tid'][$item['value']] = $item;
            } else {
                $group[$opLevel]['normal'][] = $item;
            }
        }
        self::line('keyword loaded');
        self::tick();
        self::line('keyword statics:');
        self::line('trust:', 1);
        self::line('normal    :' . sizeof($group['trust']['normal']));
//        self::line('nickname  :' . sizeof($group['trust']['nickname']));
//        self::line('username  :' . sizeof($group['trust']['username']));
//        self::line('portrait  :' . sizeof($group['trust']['portrait']));
//        self::line('userid    :' . sizeof($group['trust']['userid']));
        self::line('tid       :' . sizeof($group['trust']['tid']));
        self::line('uid       :' . sizeof($group['trust']['uid']));
        self::line('normal:', 1);
        self::line('normal   :' . sizeof($group['normal']['normal']));
//        self::line('nickname :' . sizeof($group['normal']['nickname']));
//        self::line('username :' . sizeof($group['normal']['username']));
//        self::line('portrait :' . sizeof($group['normal']['portrait']));
//        self::line('userid   :' . sizeof($group['normal']['userid']));
        self::line('tid       :' . sizeof($group['normal']['tid']));
        self::line('uid       :' . sizeof($group['normal']['uid']));
        self::line('alert:', 1);
        self::line('normal    :' . sizeof($group['alert']['normal']));
//        self::line('nickname  :' . sizeof($group['alert']['nickname']));
//        self::line('username  :' . sizeof($group['alert']['username']));
//        self::line('portrait  :' . sizeof($group['alert']['portrait']));
//        self::line('userid    :' . sizeof($group['alert']['userid']));
        self::line('tid       :' . sizeof($group['alert']['tid']));
        self::line('uid       :' . sizeof($group['alert']['uid']));
        self::line('group keywords finished');
        self::tick(true);
        return $group;
    }

    /**
     * @return array
     *
     * [[
     * 'id'         =>'',
     * 'tid'        =>'',
     * 'pid'        =>'',
     * 'cid'        =>'',
     * 'title'      =>'',
     * 'content'    =>'',
     * 'nickname'   =>'',
     * 'username'   =>'',
     * 'portrait'   =>'',
     * 'userid'     =>'',
     * ]]
     */
    public function loadPost() {
        self::line('load post start', 1);
        self::tick();
        $postList = DB::query('select 
sp.id,
sp.tid,
sp.pid,
sp.cid,
sp.uid,
-- 
spt.title,
-- 
sc.content
-- 
-- sus.nickname,
-- sus.username,
-- sus.portrait,
-- sus.userid

-- sp.index_p,
-- sp.index_c,
-- sp.is_lz,
-- sp.time_pub,
-- sp.time_scan,
-- sp.time_check,
from spd_post sp 
 force index (`time_check`)
left join spd_post_title spt on sp.tid=spt.tid and sp.index_p=1
left join spd_post_content sc on sp.cid=sc.cid
-- left join spd_user_signature sus on sp.uid=sus.id
where sp.time_check is null 
;');
        self::line('post content loaded');
        self::tick();
        self::line('post statics:');
        self::line('size  :' . sizeof($postList));
        self::tick(true);
        return $postList;
    }

    /**
     * @param $post array
     * @param $keywordGroup array
     * @return array
     */
    public function checkPost($post, $keywordGroup) {
        $matched = [];
        foreach ($keywordGroup as $keywordList) {
//			var_dump($post['user_name']);
            //id专用
            /*if (isset($keywordList['nickname'][$post['nickname']])) {
                $matched[] = $keywordList['nickname'][$post['nickname']];
                self::line('matched:nickname:' . $post['nickname']);
            }
            if (isset($keywordList['username'][$post['username']])) {
                $matched[] = $keywordList['username'][$post['username']];
                self::line('matched:username:' . $post['username']);
            }
            if (isset($keywordList['portrait'][$post['portrait']])) {
                $matched[] = $keywordList['portrait'][$post['portrait']];
                self::line('matched:portrait:' . $post['portrait']);
            }
            if (isset($keywordList['userid'][$post['userid']])) {
                $matched[] = $keywordList['userid'][$post['userid']];
                self::line('matched:userid:' . $post['userid']);
            }*/
            if (isset($keywordList['tid'][$post['tid']])) {
                $matched[] = $keywordList['tid'][$post['tid']];
                self::line('matched:tid:' . $post['tid']);
            }
            if (isset($keywordList['uid'][$post['uid']])) {
                $matched[] = $keywordList['uid'][$post['uid']];
                self::line('matched:userid:' . $post['uid']);
            }
            //通用
            foreach ($keywordList['normal'] as $keyword) {
                //位置
                foreach ($keyword['position'] as $position => $ifP) {
                    if (!$ifP) continue;
                    if (empty($post[$position])) continue;
                    //匹配类型
                    foreach ($keyword['type'] as $type => $ifT) {
                        if (!$ifT) continue;
                        if (!$this->matchVal($post[$position], $keyword['value'], $type)) continue;
                        $matched[$keyword['id']] = $keyword;
                        self::line('matched:keyword:' . $keyword['value']);
//                        var_dump($post);
//                        var_dump($keyword);
//                        exit();
                        //跳出并匹配下一个关键词
                        break(2);
                    }
                }
            }
            if (!empty($matched)) break;
        }
        return $matched;
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
    private function matchVal($value, $target, $matchType) {
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
                $result = preg_match('/' . $target . '/im', $value) === 1;
                break;
            case 'match':
            default:
                $result = $target == $value;
                break;
        }
        return $result;
    }

    public function writeUndoList($undo = []) {
        if (empty($undo)) return true;
        DB::query('update spd_post set time_check=CURRENT_TIMESTAMP where id in (:v)', [], $undo);
        return true;
    }

    public function writeDoId($doId = []) {
        DB::query('update spd_post set time_check=CURRENT_TIMESTAMP where id in (:v)', [], $doId);
        return true;
    }

    public function writeDo($post, $keywords) {
        $mergeOperate = [
            'operate'         => [],
            'operate_id_list' => [],
            'operate_reason'  => [],
        ];
        foreach ($keywords as $keyword) {
            $mergeOperate['operate']           += array_filter($keyword['operate']);
            $mergeOperate['operate_id_list'][] = $keyword['id'];
            //记录理由的时候获取一下用户名称，这边会拖慢性能，但是考虑到一般扫描的用户并不是太多，所以先就这样了
            //或者其实既然记录了关键词 id 那完全可以在获取的时候再去统一 left join 去这件事
            if ($keyword['position']['uid']) {
                $userInfo = DB::query(
                    'select nickname,username,portrait,userid from spd_user_signature where id=:uid',
                    ['uid' => $keyword['value']]
                );
                if (empty($userInfo)) continue;
                $userInfo                         = $userInfo[0];
                $userName                         = array_shift($userInfo);
                $mergeOperate['operate_reason'][] = $userName;
            } else {
                $mergeOperate['operate_reason'][] = $keyword['value'];
            }
        }
        DB::query(
            'insert ignore into spd_operate(post_id, operate, time_operate) value (:post_id, :operate, :time_operate);',
            [
                'post_id'      => $post['id'],
                'operate'      => SpdOpMap::writeBinary('operate', $mergeOperate['operate']),
                'time_operate' => date('Y-m-d H:i:s'),
            ]
        );
        $operateId = DB::lastInsertId();
        DB::query(
            'insert ignore into spd_operate_content(id, operate_id_list, operate_reason) value (:id, :operate_id_list, :operate_reason);',
            [
                'id'              => $operateId,
                'operate_id_list' => implode(',', $mergeOperate['operate_id_list']),
                'operate_reason'  => '命中：' . implode(' , ', $mergeOperate['operate_reason']),
            ]
        );
        return true;
    }

    //----------------------------------------------------------------
    //-- old
    //----------------------------------------------------------------

    /*private static function writePost($postList) {
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
    }*/

}