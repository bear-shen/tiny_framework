<?php namespace Controller;

use Lib\DB;
use Lib\Request;
use Lib\Response;
use Model\FileUpload;

/**
 * @deprecated
 * 暂时感觉没有这个必要，先不处理这个
 */
class Upload extends Kernel {
    private $chunkSignal = [
        'part' => '__PART__',
        'end'  => '__END__',
    ];

    /**
     *
     */
    public function clearAct() {
//        DB::execute('truncate table assoc_node_file;');
//        DB::execute('truncate table file;');
//        DB::execute('truncate table node;');
//        DB::execute('truncate table node_index;');
//        DB::execute('truncate table node_info;');
//        DB::execute('truncate table node_tree;');
        return 'success';
    }

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
        $post = Request::data();

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
                $saveDb = $file->saveDB(isset($post['id']) ? $post['id'] : 0);
                if ($saveDb[0]) {
                    return $this->apiErr($saveDb[0], $saveDb[1], $rowRes);
                }
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
                    $token = md5(microtime(true) . mt_rand(10000, 99999) . $fileName);
                }
                $file   = new FileUpload(['name' => $file['name']] + $file);
                $rowRes += $file->saveTmp($token);
                if ($ifPartial === false) {

                    $rowRes += $file->save();
                    $saveDb = $file->saveDB(isset($post['id']) ? $post['id'] : 0);
                    if ($saveDb[0]) {
                        return $this->apiErr($saveDb[0], $saveDb[1], $rowRes);
                    }
                }
            }
            $result[$key] = $rowRes;
        }
//        var_dump($result);
        return $this->apiRet($result);
    }
}