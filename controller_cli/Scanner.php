<?php namespace ControllerCli;

use ControllerCli\Kernel as K;
use Lib\DB;
use Lib\GenFunc;
use Model\Settings;
use Model\SpdCheck;
use Model\SpdOperate;
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
                $pages = $scanner->getAvailPageNo($thread['max']);
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
        $configList = Settings::get('tieba_conf');
        foreach ($configList as $config) {
            $checker = new SpdCheck($config);
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
                    $checkResult['do'][]    = [
                        'post'     => $post,
                        'keywords' => $ifMatch,
                    ];
                    $checkResult['do_id'][] = $post['id'];
                }
//            var_dump($ifMatch);
            }
            self::line('post check finished');
            self::line('passed:' . sizeof($checkResult['undo']));
            self::line('to operate:' . sizeof($checkResult['do']));
            self::tick();
            self::line('write result to db');
            self::tick();
            $checker->writeUndoList($checkResult['undo']);
            if (!empty($checkResult['do'])) {
                $checker->writeDoId($checkResult['do_id']);

                foreach ($checkResult['do'] as $item) {
                    $checker->writeDo($item['post'], $item['keywords']);
                }
            }
            self::line('check result write finished');
            self::tick();
            self::tick(true);
        }
    }

    /**
     * @todo no test
     */
    public function OperateAct() {
        self::line('scanner:operate', 2);
        $configList = Settings::get('tieba_conf');
        foreach ($configList as $config) {
            $operator   = new SpdOperate($config);
            $postList   = $operator->loadPost();
            $configData = Settings::get('tieba_conf');
            $config     = [];
            foreach ($configData as $confItem) {
                $config[(string)$confItem['fid']] = $confItem;
            }
            foreach ($postList as $post) {
                //无法匹配fid的不处理
                if (empty($config[(string)$post['fid']])) continue;
                $operateResult = $operator->execute($post);
                $operator->writeExecuteResult($post, $operateResult);
            }
        }
    }

    public function LoopAct() {
        $configList = Settings::get('tieba_conf');
        foreach ($configList as $config) {
            $operate  = new SpdOperate($config);
            $loopList = $operate->getLoopList();
            foreach ($loopList as $loopUser) {
                //填充cid
                $operate->fillLoopCid($loopUser);
                $operate->loop($loopUser);
            }
        }

    }
}