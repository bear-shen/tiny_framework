<?php namespace Controller;

use Lib\Request;
use Lib\Response;
use Model\FileUpload;

class Upload extends Kernel {
    private $chunkSignal = [
        'part' => '__PART__',
        'end'  => '__END__',
    ];

    /**
     * @return string|boolean
     * [[
     *      'code'  =>'',
     *      'msg'   =>'',
     *      'path'   =>'',
     *      'size'   =>'',
     *      'type'   =>'',
     *      'name'   =>'',
     *      // on save file
     *      // node id
     *      'id'    =>'',
     * ]]
     */
    public function receiveAct() {
        $post     = Request::data();

        $fileList = Request::file();
        if (empty($fileList)) return $this->apiRet([]);
//        $encoding = mb_internal_encoding();
        mb_internal_encoding('UTF-8');
        $result = [];
        ksort($fileList);
//        var_dump($fileList);
        foreach ($fileList as $key => $file) {
            $file += [
                'name'     => '',
                'type'     => '',
                'tmp_name' => '',
                'error'    => '',
                'size'     => '',
            ];
            if ($file['error']) {
                $result[] = [
                    'code' => 1,
                    'msg'  => 'transmit error',
                    'path' => '',
                    'size' => $file['size'],
                    'type' => $file['type'],
                    'name' => $file['name'],
                ];
            }
            $ifPartial = mb_strpos($file['name'], $this->chunkSignal['part'], 0);
            $ifEnd     = mb_strpos($file['name'], $this->chunkSignal['end'], 0);
            //
            $rowRes = [];
            if ($ifPartial === false && $ifEnd === false) {
                //不是拆分的文件
                $file   = new FileUpload($file);
                $rowRes += $file->save();
                $rowRes += $file->saveDB(isset($post['id'])?$post['id']:0);
            } else {
                /**
                 * send .part
                 *      receive partial token
                 * send .part + token
                 * send .part + token
                 * send .part + token
                 * send .end
                 * end
                 * */
                //如果是首次上传，直接生成 token
                //
                $fileName = mb_substr($file['name'], 0, $ifEnd);
                $token    = mb_substr($file['name'], $ifPartial + mb_strlen($this->chunkSignal['part']));
                if (empty($token)) {
                    $token = md5(microtime(true) . mt_rand(10000,99999) . $fileName);
                }
                $file   = new FileUpload(['name' => $file['name']] + $file);
                $rowRes += $file->saveTmp($token);
                if ($ifPartial === false) {

                    $rowRes += $file->save();
                    $rowRes += $file->saveDB(isset($post['id'])?$post['id']:0);
                }
            }
            $result[$key] = $rowRes;
        }
//        var_dump($result);
        return $this->apiRet($result);
    }
}