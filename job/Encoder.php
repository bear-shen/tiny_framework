<?php namespace Job;

use Lib\DB;
use Lib\GenFunc;
use Lib\Request;
use Model\File;

class Encoder {
    //编码处理类
    public function handle($data) {
        $fileData      = File::where('id', $data)->selectOne();
        $previewSuffix = '';
        $normalSuffix  = '';
        $type          = $fileData['type'];
        switch ($type) {
            case 'image':
                list($previewSuffix, $normalSuffix, $alphaSuffix) = $this->image($fileData);
                break;
            case 'video':
                list($previewSuffix, $normalSuffix, $alphaSuffix) = $this->video($fileData);
                break;
            case 'audio':
                list($previewSuffix, $normalSuffix, $alphaSuffix) = $this->audio($fileData);
                break;
        }
        $suffixData = json_decode($fileData['suffix'], true);
        $suffixData += [
            'preview' => $previewSuffix,
            'normal'  => $normalSuffix,
            'alpha'   => $alphaSuffix,
        ];
        $updData    = [
            'status' => 1,
            'suffix' => json_encode($suffixData, JSON_UNESCAPED_UNICODE),
        ];
        File::where('id', $data)->update($updData);
    }

    public function image(File $file) {
        $config = [
            'normal'  => [
                'max_width' => 2560,
                'quality'   => 85,
            ],
            'preview' => [
                'max_width' => 360,
                'quality'   => 60,
            ],
        ];
        //
        $pathLs                    = $this->makePath($file);
        $originPath                = $pathLs['raw'];
        $config['normal']['path']  = $pathLs['normal'];
        $config['preview']['path'] = $pathLs['preview'];
//        $rawPreview                = false;
//        $rawNormal                 = false;
        //
        $img     = new \Imagick($originPath);
        $originW = $img->getImageWidth();
        $originH = $img->getImageHeight();
        //preview
        $previewImg = clone $img;
        $scaleRate  = max($originW, $originH) / $config['preview']['max_width'];
        if ($scaleRate > 1) {
            $previewImg->resizeImage($originW / $scaleRate, $originH / $scaleRate, \Imagick::FILTER_QUADRATIC, 1);
            $previewImg->writeImage($config['preview']['path']);
        } else {
            copy($originPath, $config['preview']['path']);
//            $rawPreview=true;
        }
        //normal
        $normalImg = clone $img;
        $scaleRate = max($originW, $originH) / $config['normal']['max_width'];
        if ($scaleRate > 1) {
            $normalImg->resizeImage($originW / $scaleRate, $originH / $scaleRate, \Imagick::FILTER_QUADRATIC, 1);
            $normalImg->writeImage($config['normal']['path']);
        } else {
            copy($originPath, $config['normal']['path']);
//            $rawNormal=true;
        }
        //
        return [
            File::$generatedSuffix['image'][0],
            File::$generatedSuffix['image'][1],
            '',
        ];
    }

    public function video(File $file) {
        //@todo 考虑一下让直接上传的小文件免二压
        $config = [
            'normal'         => [
                'input'    => '',
                'output'   => '',
                'width'    => 1280,
                'height'   => 1280,
                //conf
                'max_size' => 1280,
            ],
            'preview'        => [
                'input'     => '',
                'output'    => '',
                'timestamp' => 5,
                'width'     => 1280,
                'height'    => 1280,
                //conf
                'max_size'  => 360,
                //@see https://stackoverflow.com/questions/10225403/how-can-i-extract-a-good-quality-jpeg-image-from-a-video-file-with-ffmpeg
                //qscale:v [2-31] , configure 1 (best) with -qmin 1
                'quality'   => 2,
            ],
            'alpha'          => [
                'input'     => '',
                'output'    => '',
                'timestamp' => 5,
                'width'     => 1280,
                'height'    => 1280,
                //conf
                'max_size'  => 1280,
                //@see https://stackoverflow.com/questions/10225403/how-can-i-extract-a-good-quality-jpeg-image-from-a-video-file-with-ffmpeg
                //qscale:v [2-31] , configure 1 (best) with -qmin 1
                'quality'   => 2,
            ],
            /**
             * ffmpeg -i input_video.mp4 -c:v libx265 -preset medium -x265-params crf=28 -c:a aac -strict experimental -b:a 128k output_video.mkv
             * ffmpeg\bin\ffmpeg -hwaccel cuda -t 20 -i dev.mkv -c:v h264_nvenc -pix_fmt yuv420p -c:a aac -b:a 256K -preset medium out.nvenc.420.mp4
             * ffmpeg\bin\ffmpeg               -t 20 -i dev.mkv -c:v libx264    -pix_fmt yuv420p -c:a aac -b:a 256K -preset medium out.x264.420.mp4
             * //https://gist.github.com/mikoim/27e4e0dc64e384adbcb91ff10a2d3678
             * ffmpeg\bin\ffmpeg -t 20 -i dev.mkv -c:v libx264 -profile:v high -bf 2 -g 30 -pix_fmt yuv420p -crf 18 -c:a aac -profile:a aac_low -b:a 384k out.x264.aac.mp4
             */
            'encode_normal'  => <<<BASH
ffmpeg -i (input) \
-c:v libx264 -profile:v high \
-bf 2 -g 30 -pix_fmt yuv420p -crf 18 \
-vf scale=(width):(height) \
-c:a aac -profile:a aac_low -b:a 384k \
-y (output)
BASH,
            'encode_preview' => <<<BASH
ffmpeg -i (input) \
-f image2 -vframes 1 -ss (timestamp) \
-vf scale=(width):(height) \
-qscale:v (quality) \
-y (output)
BASH,
            'probe'          => <<<BASH
ffprobe -hide_banner -show_format -show_streams \
-i (input) 2>/dev/null
BASH,
        ];

        $pathLs     = $this->makePath($file);
        $originPath = $pathLs['raw'];
        //
        $config['normal']['input']   = $originPath;
        $config['preview']['input']  = $originPath;
        $config['alpha']['input']    = $originPath;
        $config['normal']['output']  = $pathLs['normal'];
        $config['preview']['output'] = $pathLs['preview'];
        $config['alpha']['output']   = $pathLs['alpha'];
//        var_dump($config);
//        var_dump($pathLs);
//        exit();
        //
        $out      = $this->exec($config['probe'], ['input' => $originPath]);
        $probeArr = [];
        foreach ($out as $str) {
            $arr = explode('=', $str);
            if (empty($arr)) continue;
            if (sizeof($arr) < 2) continue;
            $probeArr[$arr[0]] = $arr[1];
        }
//        var_dump($probeStr);
//        var_dump($outArr);
        $probeArr           += [
            'start_time' => 0,
            'duration'   => 0,
            'size'       => 0,
            'width'      => 0,
            'height'     => 0,
        ];
        $probeArr['width']  = intval($probeArr['width']);
        $probeArr['height'] = intval($probeArr['height']);
        //--------------------------------------------
        $normalRate = max($probeArr['width'], $probeArr['height']) / $config['normal']['max_size'];
        if ($normalRate > 1) {
            $config['normal']['width']  = intval($probeArr['width'] * $normalRate / 2) * 2;
            $config['normal']['height'] = intval($probeArr['height'] * $normalRate / 2) * 2;
        } else {
            $config['normal']['width']  = intval($probeArr['width'] / 2) * 2;
            $config['normal']['height'] = intval($probeArr['height'] / 2) * 2;
        }
        $this->exec($config['encode_normal'], $config['normal']);
//        exit();
        //--------------------------------------------
        $alphaRate = max($probeArr['width'], $probeArr['height']) / $config['alpha']['max_size'];
        if ($alphaRate > 1) {
            $config['alpha']['width']  = intval($probeArr['width'] * $alphaRate / 2) * 2;
            $config['alpha']['height'] = intval($probeArr['height'] * $alphaRate / 2) * 2;
        } else {
            $config['alpha']['width']  = intval($probeArr['width'] / 2) * 2;
            $config['alpha']['height'] = intval($probeArr['height'] / 2) * 2;
        }
        if (intval($probeArr['duration']) < $config['alpha']['timestamp'] * 2) {
            $config['alpha']['timestamp'] = 0;
        }
        $this->exec($config['encode_preview'], $config['alpha']);
        //--------------------------------------------
        $previewRate = max($probeArr['width'], $probeArr['height']) / $config['preview']['max_size'];
        if ($previewRate > 1) {
            $config['preview']['width']  = intval($probeArr['width'] * $previewRate / 2) * 2;
            $config['preview']['height'] = intval($probeArr['height'] * $previewRate / 2) * 2;
        } else {
            $config['preview']['width']  = intval($probeArr['width'] / 2) * 2;
            $config['preview']['height'] = intval($probeArr['height'] / 2) * 2;
        }
        if (intval($probeArr['duration']) < $config['preview']['timestamp'] * 2) {
            $config['preview']['timestamp'] = 0;
        }
        $this->exec($config['encode_preview'], $config['preview']);
        //
        return [
            File::$generatedSuffix['video'][0],
            File::$generatedSuffix['video'][1],
            File::$generatedSuffix['video'][2],
        ];
    }

    public function audio(File $file) {
        $config = [
            'normal'         => [
                'input'    => '',
                'output'   => '',
                //conf
                'bit_rate' => '256k',
            ],
            'preview'        => [
                'input'    => '',
                'output'   => '',
                'width'    => 1280,
                'height'   => 1280,
                //conf
                'max_size' => 360,
                'quality'  => 2,
            ],
            'alpha'          => [
                'input'    => '',
                'output'   => '',
                'width'    => 1280,
                'height'   => 1280,
                //conf
                'max_size' => 1280,
                'quality'  => 2,
            ],
            'encode_normal'  => <<<BASH
ffmpeg -i (input) \
-acodec aac -ab (bit_rate) \
-y (output)
BASH,
            'encode_preview' => <<<BASH
ffmpeg -i (input) \
-f image2 -an \
-vf scale=(width):(height) \
-qscale:v (quality) \
-y (output)
BASH,
            'probe'          => <<<BASH
ffprobe -hide_banner -show_format -show_streams \
-i (input) 2>/dev/null
BASH,

        ];
//        var_dump($file->toArray());
        $pathLs     = $this->makePath($file);
        $originPath = $pathLs['raw'];
        //
        $config['normal']['input']   = $originPath;
        $config['preview']['input']  = $originPath;
        $config['alpha']['input']    = $originPath;
        $config['normal']['output']  = $pathLs['normal'];
        $config['preview']['output'] = $pathLs['preview'];
        $config['alpha']['output']   = $pathLs['alpha'];
        //
        $out      = $this->exec($config['probe'], ['input' => $originPath]);
        $probeArr = [];
        foreach ($out as $str) {
            $arr = explode('=', $str);
            if (empty($arr)) continue;
            if (sizeof($arr) < 2) continue;
            $probeArr[$arr[0]] = $arr[1];
        }
        $hasPreview = !empty($probeArr['width']);
        if ($hasPreview) {
            $w         = intval($probeArr['width']);
            $h         = intval($probeArr['height']);
            $scaleRate = max($w, $h) / $config['preview']['max_size'];
            if ($scaleRate > 1) {
                $config['preview']['width']  = intval($w * $scaleRate / 2) * 2;
                $config['preview']['height'] = intval($h * $scaleRate / 2) * 2;
            } else {
                $config['preview']['width']  = $w;
                $config['preview']['height'] = $h;
            }
            $this->exec($config['encode_preview'], $config['preview']);
            //
            $scaleRate = max($w, $h) / $config['alpha']['max_size'];
            if ($scaleRate > 1) {
                $config['alpha']['width']  = intval($w * $scaleRate / 2) * 2;
                $config['alpha']['height'] = intval($h * $scaleRate / 2) * 2;
            } else {
                $config['alpha']['width']  = $w;
                $config['alpha']['height'] = $h;
            }
            $this->exec($config['encode_preview'], $config['alpha']);
        }
        $this->exec($config['encode_normal'], $config['normal']);
        return [
            $hasPreview ? File::$generatedSuffix['audio'][0] : '',
            File::$generatedSuffix['audio'][1],
            $hasPreview ? File::$generatedSuffix['audio'][2] : '',
        ];
    }

    private function exec($command, $param = []) {
        $repArr = [[], []];
        foreach ($param as $key => $val) {
            $repArr[0][] = '(' . $key . ')';
            $repArr[1][] = $val;
        }
        $command = str_replace($repArr[0], $repArr[1], $command);
        $out     = [];
//        var_dump($command);
        exec($command, $out);
        return $out;

    }

    private function makePath(File $file) {
        $raw = $file->getPath('raw', true);
        if (empty(File::$generatedSuffix[$file->type])) {
            return [
                'raw'     => '',
                'normal'  => '',
                'preview' => '',
                'alpha'   => '',
            ];
        }
        $preview = File::getPathFromHash($file->hash, File::$generatedSuffix[$file->type][0], $file->type, 'preview', true);
        if ($preview) {
            $dir = dirname($preview);
            if (!file_exists($dir)) {
                mkdir($dir, 0664, true);
            }
        }
        $normal = File::getPathFromHash($file->hash, File::$generatedSuffix[$file->type][1], $file->type, 'normal', true);
        if ($normal) {
            $dir = dirname($normal);
            if (!file_exists($dir)) {
                mkdir($dir, 0664, true);
            }
        }
        $alpha = File::getPathFromHash($file->hash, File::$generatedSuffix[$file->type][2], $file->type, 'alpha', true);
        if ($alpha) {
            $dir = dirname($alpha);
            if (!file_exists($dir)) {
                mkdir($dir, 0664, true);
            }
        }
        return [
            'raw'     => $raw,
            'normal'  => $normal,
            'preview' => $preview,
            'alpha'   => $alpha,
        ];
    }
}