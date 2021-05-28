<?php namespace Model;

use Lib\ORM;

/**
 * @property string $id
 * @property string $hash
 * @property string $type
 * @property string $suffix
 * @property string $suffix_normal
 * @property string $suffix_preview
 * @property string $size
 * @property string $status
 * @property string $time_create
 * @property string $time_update
 */
class File extends Kernel {
    public static $tableName = 'file';
    public static $params    = [
        'id',
        'hash',
        'type',
        'suffix',
        'suffix_normal',
        'suffix_preview',
        'size',
        'status',//2 处理中 1 正常 0 删除
        'time_create',
        'time_update',
    ];

    /**
     * @see http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
     * @see https://www.php.net/manual/zh/function.mime-content-type.php
     *
     * 根据 mime 判断文件类型
     *
     * 这里有一些问题， mime 是服务器自己获取的，实际上不具有校验的功能
     * 那么还不如干脆不要获取 mime ，而且过程式用法看起来也不够 “fashion”
     * 不过话说加入 spl 似乎让流程更麻烦了，还不如老办法一把梭
     */
    /*public static $mime   = [
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
    ];*/

    public static $availSuffix = [
        'log'  => 'text',
        'txt'  => 'text',
        'xml'  => 'text',
        //
        'zip'  => 'binary',
        'rar'  => 'binary',
        'iso'  => 'binary',
        //
        'mp4'  => 'video',
        'ogg'  => 'video',
        '3gp'  => 'video',
        'mpg'  => 'video',
        'webm' => 'video',
        'mov'  => 'video',
        'wmv'  => 'video',
        //
        'aac'  => 'audio',
        'm4a'  => 'audio',
        'mp3'  => 'audio',
        'wav'  => 'audio',
        'flac' => 'audio',
        'weba' => 'audio',
        //
        'jpg'  => 'image',
        'gif'  => 'image',
        'png'  => 'image',
        'bmp'  => 'image',
        'apng' => 'image',
        'webp' => 'image',
    ];
    //这个根据 \Job\Encoder 的配置配置
    //1 preview 2 normal
    public static $generatedSuffix = [
        'image' => ['jpg', 'jpg'],
        'audio' => ['', 'aac'],
        'video' => ['jpg', 'mp4'],
    ];
    /**
     * use with define(FILE_*)
     */
    public static $prePath = [
        'pub' => 'upload',
        'tmp' => 'tmp',
    ];

    /**
     * 获取文件类型，返回数组为 [文件类型,文件后缀]
     * @param $fileName string
     * @return array [type,suffix]
     */
    public static function getTypeFromName($fileName) {
        $suf = self::getSuffixFromName($fileName);
//        var_dump($suf);
        $suf = strtolower($suf);
        if (isset(self::$availSuffix[$suf])) return [self::$availSuffix[$suf], $suf];
        return ['binary', $suf];
    }

    /**
     * 提取文件后缀
     * 这里不做其他的处理了，就提取一下后缀，写入文件时有用
     * @param $fileName string
     * @return string
     */
    public static function getSuffixFromName($fileName) {
        $pos  = mb_strrpos($fileName, '.');
        $subS = mb_substr($fileName, $pos + 1);
        if (mb_strrpos($fileName, DIRECTORY_SEPARATOR) !== false) $subS = '';
        return $subS;
    }


    /**
     * 根据 hash 生成对应的文件路径
     * 与其叫 getPath 不如叫 makePath 。。。无所谓不过
     * @param string $hash 可以是文件的 hash 也可以是文件名的 hash ，方便临时文件写入
     * @param string $suffix
     * @param string $type 文件类型
     * @param boolean $local 本地文件标识
     * @param string $level 文件分层 preview normal raw temp
     *
     * 文件结构:
     * local/level/type/hashPath.suffix
     *
     * @return string
     */
    public static function getPathFromHash($hash, $suffix = '', $type = '', $level = 'raw', $local = false) {
        $s = $local ? DIRECTORY_SEPARATOR : '/';
        //
        $root = '';
        if ($local) {
            $root = FILE_ROOT;
        } else {
            $root = SCHEME . '://' . FILE_DOMAIN;
        }
        //
        $directory = '';
        switch ($level) {
            default:
            case 'temp':
                $directory = self::$prePath['tmp'];
                break;
            case 'preview':
            case 'normal':
            case 'raw':
                $directory = self::$prePath['pub'];
                break;
        }
        //
        $subPath =
            substr($hash, 0, 1) . $s .
            substr($hash, 1, 2) . $s .
            substr($hash, 3, 2) . $s .
            substr($hash, 5) .
            (empty($suffix) ? '' : ('.' . $suffix));
        //
        $target = rtrim($root, $s)
                  . $s . $directory
                  . $s . $level
                  . $s . $type
                  . $s . ltrim($subPath, $s);
        return $target;
    }

    /**
     * 生成文件 hash
     * @param string $path
     * @return false|string
     */
    public static function getHashFromFile($path) {
        $md5 = md5_file($path);
        return $md5;
//        return substr($md5, 8, 16);
    }
}