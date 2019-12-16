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
            $config       += [
                'name'    => '',
                'kw'      => '',
                'fid'     => '',
                'user'    => '',
                'cookie'  => '',
                'scan'    => true,
                'operate' => true,
            ];
            $tidDataList  = SpdScan::getTid($config['kw']);
            $postDataList = SpdScan::getPost($config['fid'], $tidDataList);

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