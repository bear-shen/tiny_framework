<?php namespace ControllerCli;

use ControllerCli\Kernel as K;
use Lib\DB;
use Lib\GenFunc;
use Model\Settings;
use Model\SpdCheck;
use Model\SpdOpMap;
use Model\SpdScan;

class Scanner extends K {

    public function ScanAct() {
        self::line('scanner:scan', 2);
        $configList = Settings::get('tieba_conf');
        foreach ($configList as $config) {
            self::line('loaded:' . $config['name'], 2);
            //读取贴吧数据的时候可能会有120s的超时，这个还没确定怎么调试
            $config      += [
                'name'    => '',
                'kw'      => '',
                'fid'     => '',
                'user'    => '',
                'cookie'  => '',
                'scan'    => true,
                'operate' => true,
            ];
            $scanner     = new SpdScan($config);
            $tidDataList = $scanner->getTid();
//            var_dump($tidDataList);
//            continue;
            $postDataList = $scanner->getPost($tidDataList);
//            $postDataList = $scanner->getPost([['tid' => '2817780259',], ['tid' => '6509264271',]]);
//            var_dump($postDataList);
//            exit();
            $threadList = $postDataList['thread'];
            $postList   = $postDataList['post'];
            //
            $nxtPostInfo = [];
            foreach ($threadList as $thread) {
                $pages = SpdScan::getAvailPageNo($thread['max']);
                foreach ($pages as $page) {
                    $nxtPostInfo[] = ['tid' => $thread['tid'], 'page' => $page,];
                }
            }
//            var_dump($nxtPostInfo);
//            exit();
            //
            $nxtPostDataList = $scanner->getPost($nxtPostInfo);
            foreach ($nxtPostDataList['thread'] as $thread) {
                $threadList[] = $thread;
            }
            foreach ($nxtPostDataList['post'] as $post) {
                $postList[] = $post;
            }
            //
//            var_dump($threadList);
//            exit();
            $commentList = $scanner->getComment($threadList);
            foreach ($commentList as $comment) {
                $postList[] = $comment;
            }
            //
            $scanner->writeThread($threadList);
            $scanner->writePost($postList);
            self::line('scanner:scan finished', 2);
        }
    }

    public function CheckAct() {
        self::line('scanner:check', 2);
        $checker = new SpdCheck();
        $checker->loadKeywords();
        $keywordGroup = $checker->groupKeyword();
        $postGroup    = $checker->loadPost();
//        var_dump($keywordGroup['trust']['tid']);
        $checkResult = [
            'undo' => [],
            'do'   => [],
        ];
        self::line('post check start');
        self::tick();
        foreach ($postGroup as $post) {
            $ifMatch = $checker->checkPost($post, $keywordGroup);
            if (empty($ifMatch)) {
                $checkResult['undo'][] = $post['id'];
            } else {
                $checkResult['do'][] = [
                    'post'     => $post,
                    'keywords' => $ifMatch,
                ];
            }
//            var_dump($ifMatch);
        }
        self::line('post check finished');
        self::line('passed:' . sizeof($checkResult['undo']));
        self::line('to operate:' . sizeof($checkResult['do']));
        self::tick();
        if (!empty($checkResult['undo'])) {
            DB::query('update spd_post set time_check=CURRENT_TIMESTAMP where id in (:v)', [], $checkResult['do']);
        }
        if (!empty($checkResult['do'])) {
            foreach ($checkResult['do'] as $item) {
                $mergeOperate = [
                    'operate'         => [],
                    'operate_id_list' => [],
                    'operate_reason'  => [],
                ];
                foreach ($item['keywords'] as $keyword) {
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
                        'post_id'      => $item['post']['id'],
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
            }
        }
    }

    public function OperateAct() {
        self::line('scanner:operate', 2);
    }
}