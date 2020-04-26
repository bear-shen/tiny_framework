<?php namespace Model;


use Lib\DB;
use Lib\GenFunc;

/**
 * 上传的model
 * 本是想改成连续调用的形式，但是为了错误处理所以还是先放着吧。。。
 */
class FileUpload {
    /**
     * @see http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
     * @see https://www.php.net/manual/zh/function.mime-content-type.php
     *
     */
    public $knownMime   = [
        'video/mp4'       => ['type' => 'video', 'suffix' => 'mp4'],
        'video/ogg'       => ['type' => 'video', 'suffix' => 'ogg'],
        'video/3gpp'      => ['type' => 'video', 'suffix' => '3gp'],
        'video/mpeg'      => ['type' => 'video', 'suffix' => 'mpg'],
        'video/webm'      => ['type' => 'video', 'suffix' => 'webm'],
        'video/quicktime' => ['type' => 'video', 'suffix' => 'mov'],
        'audio/x-aac'     => ['type' => 'audio', 'suffix' => 'aac'],
        'audio/mp4'       => ['type' => 'audio', 'suffix' => 'm4a'],
        'audio/mpeg'      => ['type' => 'audio', 'suffix' => 'mp3'],
        'audio/x-flac'    => ['type' => 'audio', 'suffix' => 'flac'],
        'audio/webm'      => ['type' => 'audio', 'suffix' => 'weba'],
        'image/jpeg'      => ['type' => 'image', 'suffix' => 'jpg'],
        'image/gif'       => ['type' => 'image', 'suffix' => 'gif'],
        'image/png'       => ['type' => 'image', 'suffix' => 'png'],
        'image/bmp'       => ['type' => 'image', 'suffix' => 'bmp'],
        'image/apng'      => ['type' => 'image', 'suffix' => 'apng'],
        'image/webp'      => ['type' => 'image', 'suffix' => 'webp'],
    ];
    public $knownSuffix = [
        'log' => 'text',
        'txt' => 'text',
        'xml' => 'text',
        'zip' => 'binary',
        'rar' => 'binary',
        'iso' => 'binary',
    ];
    public $path        = [
        'dir' => '/file', /** append BASE_DIR on construct */
        'pub' => '/res',
        'tmp' => '/tmp',
    ];

    //输入数据
    private $data = [
        'name'     => '',
        'type'     => '',
        'tmp_name' => '',
        'error'    => '',
        'size'     => '',
    ];

    // --------------------------------
    // save 和 saveTmp 产生的中间数据

    private $fileInfo = [
        'type'   => '',
        'mime'   => '',
        'suffix' => '',
    ];

    private $filePath = [
        'hash' => '',
        'path' => '',
    ];

    private $targetPath = '';

    public $saved    = false;
    public $tmpToken = false;

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
    public function __construct($uploadData, $tmp = false) {
        $this->path['dir'] = BASE_PATH . $this->path['dir'];
        //
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
     *      'code'  =>'',
     *      'msg'   =>'',
     *      'path'   =>'',
     *      'size'   =>'',
     *      'type'   =>'',
     *      'name'   =>'',
     * ]
     */
    public function save() {
        $res            = [
            'code' => 0,
            'msg'  => 'success',
            'path' => '',
            'size' => '',
            'type' => '',
            'name' => '',
        ];
        $this->fileInfo = $this->getFileInfo();
        $this->filePath = $this->path();
        if ($this->tmpToken) {
            //临时文件的话，修改tmp_name到临时文件位置
            $this->data['tmp_name'] =
                $this->path['dir'] .
                $this->path['tmp'] . DIRECTORY_SEPARATOR . $this->tmpToken;
            $this->data['size']     = filesize($this->data['tmp_name']);
        }
//        var_dump($this->fileInfo);
        $this->targetPath =
            $this->path['pub'] .
            DIRECTORY_SEPARATOR .
            $this->filePath['path'] .
            (
            $this->fileInfo['suffix'] ? '.' . $this->fileInfo['suffix'] : ''
            );
        $targetFilePath   = $this->path['dir'] . $this->targetPath;
        if (!file_exists($targetFilePath)) {
            try {
                $dir = dirname($targetFilePath);
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }
                rename(
                    $this->data['tmp_name'],
                    $targetFilePath
                );
            } catch (\Throwable $e) {
            }
        }
        if (!file_exists($targetFilePath)) return
            ['code' => 1, 'msg' => 'error occurred in move files'] + $res;
        //
        $this->saved = true;
//        var_dump($this->data);
//        var_dump($this->fileInfo);
        return [
                   'path' => $this->targetPath,
                   'size' => $this->data['size'],
                   'type' => $this->fileInfo['type'],
                   'name' => $this->data['name'],
               ] + $res;
    }

    /**
     * @return array
     * [
     *      'id'    =>'',
     * ]
     */
    public function saveDB() {
        $result = [
            'id' => 0
        ];
        if (!$this->saved) $this->save();
        DB::$logging = true;
        DB::query('insert ignore into 
file_basic (hash, path, type, size) 
value (:hash, :path, :type, :size);', [
            'hash' => $this->filePath['hash'],
            'path' => $this->targetPath,
            'type' => $this->fileInfo['type'],
            'size' => $this->data['size'],
        ]);
        $row = DB::query('select id from file_basic where hash = :hash',
                         ['hash' => $this->filePath['hash']]
        );
        if (empty($row)) return [/*'log' => DB::$log*/] + $result;
        $row = $row[0];
        DB::query('insert ignore into file_name (id, name) VALUE (:id,:name)',
                  ['id' => $row['id'], 'name' => $this->data['name'],]
        );
        return ['id' => $row['id']/*,'log'=>DB::$log*/] + $result;
    }

    /**
     * @param string $token
     * @return array
     */
    public function saveTmp($token = '') {
//        var_dump($token);
        $res = [
            'code' => 0,
            'msg'  => 'success',
            'path' => '',
            'size' => '',
            'type' => '',
            'name' => '',
        ];
        if (
            preg_match(
                '/[a-z0-9]/i',
                $token
            ) !== 1) return ['code' => 1, 'msg' => 'invalid token'] + $res;

        /*$fileHash   = $this->hash() + [
                'hash' => '',
                'path' => '',
            ];*/
        $this->tmpToken   = $token;
        $this->targetPath =
            $this->path['dir'] .
            $this->path['tmp'] . DIRECTORY_SEPARATOR . $token;
        //
        $content = file_get_contents($this->data['tmp_name']);
        file_put_contents($this->targetPath, $content, FILE_APPEND);
        return [
                   //'path' => $this->targetPath,
                   'size' => $this->data['size'],
                   'type' => $this->fileInfo['type'],
                   'name' => $this->data['name'],
               ] + $res;
    }

    private function log() {
    }


    /**
     * 生成新的文件路径
     * @uses hash
     * @return array
     *
     * [
     *      'hash'   =>'',
     *      'path'   =>'asd/asd/asd/asdasdasd',
     * ]
     */
    private function path() {
        $hash    = $this->hash();
        $subPath =
            substr($hash, 0, 1) . '/' .
            substr($hash, 1, 2) . '/' .
            substr($hash, 3, 2) . '/' .
            substr($hash, 5) .
            '';
        return [
            'hash' => $hash,
            'path' => $subPath,
        ];
    }

    /**
     * 文件hash
     * @internal
     */
    private function hash() {
        /*$fMd5 = substr(md5_file($file['tmp_name'],true),4,8);
        var_dump($fMd5);
        var_dump(base64_encode($fMd5));
        exit();
        $fMd5S = str_split($fMd5, 8);
        $hUp   =
            [
                pack('N*', hexdec($fMd5S[0])),
                pack('N*', hexdec($fMd5S[1])),
                pack('N*', hexdec($fMd5S[2])),
                pack('N*', hexdec($fMd5S[3])),
            ];
        var_dump($hUp);
        var_dump(implode('',$hUp));
        var_dump(base64_encode(implode('',$hUp)));
        exit();*/
        $md5 = md5_file($this->data['tmp_name']);
        return substr($md5, 8, 16);
    }

    /**
     * @uses mimeInfo
     * @uses extensionInfo
     *
     * @return array
     *
     * [
     *      'type'   =>'video|audio|...',
     *      'mime'   =>'image/jpeg|...',
     *      'suffix' =>'jpg|...',
     * ]
     */
    public function getFileInfo() {
        $res = [
            'type'   => 'binary',
            'mime'   => 'application/octet-stream',
            'suffix' => '',
        ];
        //
        $mime = $this->mimeInfo();
        if (!empty($mime)) return $mime + $res;
        //
        list($res['suffix'], $res['type']) = $this->extensionInfo();
//        var_dump($res);
        return $res;
    }

    /**
     * @internal $this->getFileInfo()
     *
     * @return array|boolean
     *
     * [
     *      'type'   =>'video|audio|...',
     *      'mime'   =>'image/jpeg|...',
     *      'suffix' =>'jpg|...',
     * ]
     */
    private function mimeInfo() {
        $mime = mime_content_type($this->data['tmp_name']);
        if (empty($this->knownMime[$mime])) return false;
        $res = [
            'type'   => $this->knownMime[$mime]['type'],
            'mime'   => $mime,
            'suffix' => $this->knownMime[$mime]['suffix'],
        ];
        return $res;
    }

    /**
     * @internal $this->getFileInfo()
     * @return array
     *
     * ['jpg|...','binary|image']
     */
    private function extensionInfo() {
        $res = ['', 'binary'];
//        var_dump($this->data);
        $pos = strrpos($this->data['name'], '.');
        if ($pos === false) return $res;
        $suffix = substr($this->data['name'], $pos + 1);
        if (empty($suffix)) return $res;
        if (empty($this->knownSuffix[$suffix])) return [$suffix] + $res;
        return [$suffix, $this->knownSuffix[$suffix]];
    }
}