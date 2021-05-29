<?php namespace Job;

use Lib\DB;
use Lib\GenFunc;
use Lib\Request;
use Model\File;

class Encoder {
    //编码处理类
    public function handle($data) {
        $fileData       = File::where('id', $data)->selectOne();
        $processSuccess = false;
        $type           = $fileData['type'];
        switch ($type) {
            case 'image':
                $processSuccess = $this->image($fileData);
                break;
            case 'video':
                $processSuccess = $this->video($fileData);
                break;
            case 'audio':
                $processSuccess = $this->audio($fileData);
                break;
        }
        if ($processSuccess) {
            File::where('id', $data)->update(
                [
                    'status'         => 1,
                    'suffix_normal'  => isset(File::$generatedSuffix[$type]) ? File::$generatedSuffix[$type][1] : '',
                    'suffix_preview' => isset(File::$generatedSuffix[$type]) ? File::$generatedSuffix[$type][0] : '',
                ]);
        }
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
        $originPath                = File::getPathFromHash($file->hash, $file->suffix, $file->type, 'raw', true);
        $config['normal']['path']  = File::getPathFromHash($file->hash, File::$generatedSuffix['image'][1], $file->type, 'normal', true);
        $config['preview']['path'] = File::getPathFromHash($file->hash, File::$generatedSuffix['image'][0], $file->type, 'preview', true);
        //
        $img     = new \Imagick($originPath);
        $originW = $img->getImageWidth();
        $originH = $img->getImageHeight();
        //preview
        $previewImg = clone $img;
        $scaleRate  = max($originW, $originH) / $config['preview']['max_width'];
        $dir        = dirname($config['preview']['path']);
        if (!file_exists($dir)) mkdir($dir, 0664, true);
        if ($scaleRate > 1) {
            $previewImg->resizeImage($originW / $scaleRate, $originH / $scaleRate, \Imagick::FILTER_QUADRATIC, 1);
            $previewImg->writeImage($config['preview']['path']);
        } else {
            copy($originPath, $config['preview']['path']);
        }
        //normal
        $normalImg = clone $img;
        $scaleRate = max($originW, $originH) / $config['normal']['max_width'];
        $dir       = dirname($config['normal']['path']);
        if (!file_exists($dir)) mkdir($dir, 0664, true);
        if ($scaleRate > 1) {
            $normalImg->resizeImage($originW / $scaleRate, $originH / $scaleRate, \Imagick::FILTER_QUADRATIC, 1);
            $normalImg->writeImage($config['normal']['path']);
        } else {
            copy($originPath, $config['normal']['path']);
        }
        //
        return true;
    }

    public function video(File $file) {
        //@todo 考虑一下让直接上传的小文件免二压
        $config = [
            'normal'         => [
                'input'    => '',
                'output'   => '',
                'max_size' => 1280,
                'width'    => 1280,
                'height'   => 1280,
            ],
            'preview'        => [
                'input'     => '',
                'output'    => '',
                'max_size'  => 360,
                'timestamp' => 5,
                'width'     => 1280,
                'height'    => 1280,
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
-qscale:v 2 \
-y (output)
BASH,
            'probe'          => <<<BASH
ffprobe -hide_banner -show_format -show_streams \
-i (input) 2>/dev/null
BASH,
        ];

        $originPath                 = File::getPathFromHash($file->hash, $file->suffix, $file->type, 'raw', true);
        $config['normal']['input']  = $originPath;
        $config['preview']['input'] = $originPath;
        //
        $config['normal']['output']  = File::getPathFromHash($file->hash, File::$generatedSuffix['video'][1], $file->type, 'normal', true);
        $config['preview']['output'] = File::getPathFromHash($file->hash, File::$generatedSuffix['video'][0], $file->type, 'preview', true);
        //
        $dir = dirname($config['normal']['output']);
        if (!file_exists($dir)) mkdir($dir, 0664, true);
        $dir = dirname($config['preview']['output']);
        if (!file_exists($dir)) mkdir($dir, 0664, true);
        //
        $probeStr = str_replace(['(input)'], [$originPath], $config['probe']);
        $out      = [];
        exec($probeStr, $out);
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
        //
        $repArr = [[], []];
        foreach ($config['normal'] as $key => $val) {
            $repArr[0][] = '(' . $key . ')';
            $repArr[1][] = $val;
        }
        $normalStr = str_replace($repArr[0], $repArr[1], $config['encode_normal']);
//        var_dump($normalStr);
        exec($normalStr, $out);
//        exit();
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
        $repArr = [[], []];
        foreach ($config['preview'] as $key => $val) {
            $repArr[0][] = '(' . $key . ')';
            $repArr[1][] = $val;
        }
        $previewStr = str_replace($repArr[0], $repArr[1], $config['encode_preview']);
//        var_dump($previewStr);
        exec($previewStr, $out);
        //
        return true;
    }

    public function audio(File $file) {
        $config = [
            'normal' => [
                'bit_rate' => 256,
            ],
        ];
        //
        return true;
    }
}