<?php namespace Model;


use Lib\GenFunc;

class File {
    /**
     * @see http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
     * @see https://www.php.net/manual/zh/function.mime-content-type.php
     *
     */
    public static $knownType = [
        'video' => [
            'video/mp4'       => ['suffix' => 'mp4'],
            'video/ogg'       => ['suffix' => 'ogg'],
            'video/3gpp'      => ['suffix' => '3gp'],
            'video/mpeg'      => ['suffix' => 'mpg'],
            'video/webm'      => ['suffix' => 'webm'],
            'video/quicktime' => ['suffix' => 'mov'],
        ],
        'audio' => [
            'audio/x-aac'  => ['suffix' => 'aac'],
            'audio/mp4'    => ['suffix' => 'm4a'],
            'audio/mpeg'   => ['suffix' => 'mp3'],
            'audio/x-flac' => ['suffix' => 'flac'],
            'audio/webm'   => ['suffix' => 'weba'],
        ],
        'image' => [
            'image/jpeg' => ['suffix' => 'jpg'],
            'image/gif'  => ['suffix' => 'gif'],
            'image/png'  => ['suffix' => 'png'],
            'image/bmp'  => ['suffix' => 'bmp'],
            'image/apng' => ['suffix' => 'apng'],
            'image/webp' => ['suffix' => 'webp'],
        ],
        'text'  => [],
        'res'   => [],
    ];
    public static $path      = '/file/';

    public static function save() {

    }


    /**
     * @param $path string
     * @return array
     *
     * [
     *      'hash'   =>'',
     *      'path'   =>'asd/asd/asd/asdasdasd',
     * ]
     */
    public static function hash($path) {
        $md5     = md5_file($path);
        $subPath =
            substr($md5, 0, 1) . '/' .
            substr($md5, 1, 2) . '/' .
            substr($md5, 3, 2) . '/' .
            substr($md5, 5) .
            '';
        return [
            'hash' => $md5,
            'path' => $subPath,
        ];
    }

    /**
     * @param $path string
     * @return array
     *
     * [
     *      'type'   =>'',
     *      'mime'   =>'',
     *      'suffix' =>'',
     * ]
     */
    public static function mimeInfo($path) {
        //$pathInfo = pathinfo($path, PATHINFO_EXTENSION);
        $res  = [
            'type'   => 'res',
            'mime'   => 'application/octet-stream',
            'suffix' => '',
        ];
        $mime = mime_content_type($path);
        foreach (self::$knownType as $type => $r1) {
            if (empty($r1[$mime])) continue;
            $res = [
                'type'   => $type,
                'mime'   => $mime,
                'suffix' => $r1['suffix'],
            ];
            break;
        }
        return $res;
    }
}