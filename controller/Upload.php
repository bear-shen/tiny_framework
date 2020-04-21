<?php namespace Controller;

use Lib\Request;
use Lib\Response;

class Upload extends Kernel {
    private $chunkSignal = [
        'part' => '__PART__',
        'end'  => '__END__',
    ];

    public function receiveAct() {
//        $post     = Request::data();
        $fileList = Request::file();
        if (empty($fileList)) return $this->apiRet([]);
//        $encoding = mb_internal_encoding();
        $encoding = 'UTF-8';
        $list     = [];
        foreach ($fileList as $key => $file) {
            $file      += [
                'name'     => '',
                'type'     => '',
                'tmp_name' => '',
                'error'    => '',
                'size'     => '',
            ];
            $ifPartial = mb_strpos($file['name'], $this->chunkSignal['part'], $encoding);
            $ifEnd     = mb_strpos($file['name'], $this->chunkSignal['end'], $encoding);
            if ($ifPartial === false && $ifEnd === false) {
                //不是拆分的文件

            } else {
                //存在token的都是一个方法
                if ($ifPartial !== false) {
//                    $name  = mb_substr($file['name'], 0, $ifPartial, $encoding);
                    $token = mb_substr($file['name'], $ifPartial + mb_strlen($ifPartial, $encoding), null, $encoding);
                } else {
                    $name  = mb_substr($file['name'], 0, $ifEnd, $encoding);
                    $token = mb_substr($file['name'], $ifEnd + mb_strlen($ifEnd, $encoding), null, $encoding);
                }
            }
        }
        return $this->apiRet($list);
    }

    /**
     *
     */
    private function toPublic() {
    }

    private function fileInfo() {
    }
}