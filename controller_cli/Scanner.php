<?php namespace ControllerCli;

use ControllerCli\Kernel as K;
use Lib\GenFunc;
use Model\Settings;
use Model\SpdScan;

class Scanner extends K {

    public function ScanAct() {
        self::line('scanner:scan', 2);
        $configList = Settings::get('tieba_conf');
        foreach ($configList as $config) {
            self::line('loaded:' . $config['name'], 1);
            $config  += [
                'name'    => '',
                'kw'      => '',
                'fid'     => '',
                'user'    => '',
                'cookie'  => '',
                'scan'    => true,
                'operate' => true,
            ];
            $scanner = new SpdScan($config);
//            $tidDataList  = $scanner->getTid();
//            $postDataList = $scanner->getPost($tidDataList);
            $postDataList = $scanner->getPost([['tid' => '2817780259', 'page' => '1',]]);
            $threadList   = $postDataList['thread'];
            $postList     = $postDataList['post'];
            //
            $nxtPostInfo = [];
            foreach ($threadList as $thread) {
                $pages = SpdScan::getAvailPageNo($thread['max']);
                foreach ($pages as $page) {
                    $nxtPostInfo[] = ['tid' => $thread['tid'], 'page' => $page,];
                }
            }
            //
            $nxtPostDataList = $scanner->getPost($nxtPostInfo);
            foreach ($nxtPostDataList['thread'] as $thread) {
                $threadList[] = $thread;
            }
            foreach ($nxtPostDataList['post'] as $post) {
                $postList[] = $post;
            }
            //

        }
        GenFunc::curlMulti();
    }

    public function CheckAct() {
        self::line('scanner:check', 2);
    }

    public function OperateAct() {
        self::line('scanner:operate', 2);
    }
}