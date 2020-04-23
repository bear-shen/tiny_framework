<?php namespace Controller;

use Lib\Request;
use Lib\Response;
use Model\FileUpload;

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
        $result   = [];
        foreach ($fileList as $key => $file) {
            $file      += [
                'name'     => '',
                'type'     => '',
                'tmp_name' => '',
                'error'    => '',
                'size'     => '',
            ];
            $ifPartial = mb_strpos($file['name'], $this->chunkSignal['part'], 0, $encoding);
            $ifEnd     = mb_strpos($file['name'], $this->chunkSignal['end'], 0, $encoding);
            //
            $rowRes = [];
            if ($ifPartial === false && $ifEnd === false) {
                //不是拆分的文件
                $file   = new FileUpload($file);
                $rowRes += $file->save();
                $rowRes += $file->saveDB();
            } elseif ($ifPartial !== false) {
                //存在token的都是一个方法
                //$name  = mb_substr($file['name'], 0, $ifPartial, $encoding);
                $token  = mb_substr($file['name'], $ifPartial + mb_strlen($this->chunkSignal['part'], $encoding), null, $encoding);
//                var_dump($file['name']);
//                var_dump($ifPartial);
//                var_dump(mb_strlen($ifPartial, $encoding));
//                var_dump($token);
                $file   = new FileUpload($file);
                $rowRes += $file->saveTmp($token);
            } else {
                $name  = mb_substr($file['name'], 0, $ifEnd, $encoding);
                $token = mb_substr($file['name'], $ifEnd + mb_strlen($this->chunkSignal['end'], $encoding), null, $encoding);
                $file  = new FileUpload(
                    ['name' => $name] + $file
                );
                //这里不加tmp，因为tmp会影响输出
                $file->saveTmp($token);
                $rowRes += $file->save();
                $rowRes += $file->saveDB();
            }
            $result[] = $rowRes;
        }
//        var_dump($result);
        return $this->apiRet($result);
    }

    /**
     *
     */
    private function toPublic() {
    }

    private function fileInfo() {
    }
}