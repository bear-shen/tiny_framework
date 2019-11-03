<?php namespace ControllerCli;

use ControllerCli\Kernel as K;
use Lib\CliHelper;
use Lib\GenFunc;
use Model\Settings;
use Model\SpdScan;

class Scanner {
    use CliHelper;

    public function ScanAct() {
        self::line('scanner:scan', 2);
        go(function () {
            try {
                //
                self::tick();
                self::line('go initial');
                \Swlib\SaberGM::default(
                    [
                        'exception_report' => 0,
                        'max_coroutine'    => 8191,
                    ]);
                $tiebaConf = Settings::get('tieba_conf');
                foreach ($tiebaConf as $tieba) {
                    if (!$tieba['scan']) continue;
                    $scanner = new SpdScan($tieba);
                    //=================
                    $tidList = $scanner->getTid();
//                var_dump($tidList);
//                exit();
                    /*	$tidList  = [
                            '5739033322',
                            '4824528997',
                            '5948504167',
                            '6049614717',
                        ];*/
                    /*$postList = [
                        'thread' => [],
                        'post'   => [],
                    ];*/
                    $postList = $scanner->getPost($tidList);
//                var_dump($postList);
                    /*$postList['thread'] = [
                        [
                            'fid'         => '52',
                            'tid'         => '5739033322',
                            'poster_name' => '盗我原号的没J8',
                            'title'       => '大概7月中旬把贴吧管理器用Python重写一遍',
                            'page'        => '1',
                            'max'         => '1',
                        ],
                        [
                            'fid'         => '52',
                            'tid'         => '6049614717',
                            'poster_name' => '贴吧用户_76t5yb9',
                            'title'       => '无名测试',
                            'page'        => '1',
                            'max'         => '1',
                        ],
                        [
                            'fid'         => '52',
                            'tid'         => '5898048126',
                            'poster_name' => 'rkbdzd',
                            'title'       => '【180929】新到手的笔记本需要做什么？',
                            'page'        => '1',
                            'max'         => '17',
                        ],
                        [
                            'fid'         => '52',
                            'tid'         => '5948504167',
                            'poster_name' => '炙岳',
                            'title'       => '【吧务】笔记本吧禁止任何形式的二手交易',
                            'page'        => '1',
                            'max'         => '4',
                        ],
                    ];*/
                    $comment = [];
                    $comment = $scanner->getComment($postList['thread']);
                    $result  = [
                        'tid'     => $tidList,
                        'thread'  => $postList['thread'],
                        'post'    => $postList['post'],
                        'comment' => $comment,
                    ];
                    file_put_contents('./zzz.json',json_encode($result));
                    return;
                    SpdScan::writeThreads($result['thread']);
//				var_dump($scanData['post']);
                    SpdScan::writePost($result['post']);
                    SpdScan::writePost($result['comment']);
                    echo '=== scan finished ===' . "\n";
                    //
                    self::line('scan start');
                }
            } catch (\Throwable $exception) {
                self::line($exception->getMessage());
                self::line($exception->getFile() . ':' . $exception->getLine());
                self::line($exception->getTraceAsString());
            }
        });
    }

    public function CheckAct() {
        self::line('scanner:check', 2);
    }

    public function OperateAct() {
        self::line('scanner:operate', 2);
    }
}