<?php namespace Model;


use Lib\DB;
use Lib\FileHelper;
use Lib\GenFunc;

/**
 * 上传的model
 * 本是想改成连续调用的形式，但是为了错误处理所以还是先放着吧。。。
 */
class FileUpload {
    use FileHelper;

    //输入数据
    private $data = [
        'name'     => '',
        'type'     => '',//mime传入的type，非实用type
        'tmp_name' => '',
        'error'    => '',
        'size'     => '',
    ];

    private $fileInfo = [
        'hash'   => '',
        'suffix' => '',
        'size'   => '',
        'type'   => '',
        'name'   => '',
    ];

    public $saved = false;
    // --------------------------------

    public $tmpName    = false;
    public $tmpHash    = false;
    public $targetPath = '';

    // --------------------------------

    /**
     * @param $uploadData array
     *
     * 这里不修改数据结构是为了方便上传
     * [
     *      'name'     =>'',//上传的文件名
     *      'type'     =>'',//mime
     *      'tmp_name' =>'',//源文件路径
     *      'error'    =>'',
     *      'size'     =>'',
     * ]
     */
    public function __construct($uploadData, $uid = 0) {
        $uploadData += [
            'name'     => '',
            'type'     => '',
            'tmp_name' => '',
            'error'    => '',
            'size'     => '',
        ];
        $this->data = $uploadData;
    }

    /**
     * @return array
     * [
     *      'code'   =>'',
     *      'msg'    =>'',
     *      'path'   =>'',
     *      'suffix'   =>'',
     *      'size'   =>'',
     *      'type'   =>'',
     *      'name'   =>'',
     * ]
     */
    public function save() {
        $res = [
            'code' => 0,
            'msg'  => 'success',
            'path' => '',
            'suffix' => '',
            'size' => '',
            'type' => '',
            'name' => '',
        ];
        if ($this->tmpHash) {
            //临时文件的话，修改tmp_name到临时文件位置
            $this->data['tmp_name'] = $this->targetPath;
            $this->data['size']     = filesize($this->data['tmp_name']);
        }
//        var_dump($this->fileInfo);
        $hash = self::getHash($this->data['tmp_name']);
        $type = self::getType($this->data['name']);
        $path = self::getPath($hash, $type[1], $type[0], 'raw', true);
        $size = $this->data['tmp_name'];
        //
        if (!file_exists($path)) {
            try {
                $dir = dirname($path);
                if (!file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }
                rename($this->data['tmp_name'], $path);
            } catch (\Throwable $e) {
            }
        } else {
            unlink($this->data['tmp_name']);
        }
        if (!file_exists($path)) return
            ['code' => 1, 'msg' => 'error occurred in move files'] + $res;
        //
        $this->saved    = true;
        $this->fileInfo = [
            'hash'   => $hash,
            'suffix' => $type[1],
            'size'   => $size,
            'type'   => $type[0],
            'name'   => $this->data['name'],
        ];
//        var_dump($this->data);
//        var_dump($this->fileInfo);
        return [
                   'path' => self::getPath($hash, $type[1], $type[0], 'raw', false),
               ] + $this->fileInfo + $res;
    }

    /**
     * @param int $dirId
     * @return array
     * [
     *      'id'    =>'',
     * ]
     */
    public function saveDB($dirId = 0) {
        //先过一次查重
        $node   = new Node();
        $result = $node->mod(
            [
                'id'            => 0,
                'id_parent'     => $dirId,
                // when mod file
                'hash'          => $this->fileInfo[''],
                'suffix'        => $this->fileInfo[''],
                'size'          => $this->fileInfo[''],
                'type'          => $this->fileInfo[''],//'audio','video','image','binary','text','folder'
                // when mod node info
                'name'          => $this->fileInfo[''],
                'description'   => '',
                'id_file_cover' => '',
            ]);
        return $result;
    }

    /**
     * @param string $fileToken
     * @return array
     */
    public function saveTmp($fileToken = '') {
//        var_dump($token);
        $res              = [
            'code' => 0,
            'msg'  => 'success',
            'path' => '',
            'size' => '',
            'type' => '',
            'name' => '',
        ];
        $this->tmpName    = $this->data['name'];
        $this->tmpHash    = $fileToken;
        $this->targetPath = self::getPath($fileToken, '', 'binary', 'temp', true);
        //
        $content = file_get_contents($this->data['tmp_name']);
        file_put_contents($this->targetPath, $content, FILE_APPEND);
        //临时文件路径换成临时文件的目标路径，用于下一步的处理
        $this->data['tmp_name'] = $this->targetPath;
        $this->data['size']     = filesize($this->targetPath);

        $this->fileInfo = [
            'hash'   => $this->tmpHash,
            'suffix' => '',
            'size'   => $this->data['size'],
            'type'   => '',
            'name'   => $this->data['name'],
        ];
        return [
                   //'path' => $this->targetPath,
                   'size' => $this->data['size'],
                   'name' => $this->data['name'],
                   'type' => self::getType($this->data['name'])[0],
               ] + $res;
    }
}