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
        switch ($fileData['type']) {
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
            File::where('id', $data)->update(['status' => 1]);
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
        $config                    = [
            'normal'         => [
                'max_width' => 1280,
            ],
            'preview'        => [
                'max_width' => 360,
                'quality'   => 60,
            ],
            /**
             * ffmpeg -i input_video.mp4 -c:v libx265 -preset medium -x265-params crf=28 -c:a aac -strict experimental -b:a 128k output_video.mkv
             * ffmpeg\bin\ffmpeg -hwaccel cuda -t 20 -i dev.mkv -c:v h264_nvenc -pix_fmt yuv420p -c:a aac -b:a 256K -preset medium out.nvenc.420.mp4
             * ffmpeg\bin\ffmpeg               -t 20 -i dev.mkv -c:v libx264    -pix_fmt yuv420p -c:a aac -b:a 256K -preset medium out.x264.420.mp4
             * //https://gist.github.com/mikoim/27e4e0dc64e384adbcb91ff10a2d3678
             * ffmpeg\bin\ffmpeg -t 20 -i dev.mkv -c:v libx264 -profile:v high -bf 2 -g 30 -pix_fmt yuv420p -crf 18 -c:a aac -profile:a aac_low -b:a 384k out.x264.aac.mp4
             */
            'encode_normal'  => <<<BASH
ffmpeg -i :[inputVideo] \
-c:v libx264 -profile:v high \
-bf 2 -g 30 -pix_fmt yuv420p -crf 18 \
-vf scale=720:-1
-c:a aac -profile:a aac_low \
-b:a 384k :[outputVideo]
BASH,
            'encode_preview' => <<<BASH
ffmpeg -i :[inputVideo] \
-f image2 -t 0.01 -vf scale=720:-1 \
-y :[outputVideo]
BASH,
            'probe'          => <<<BASH
ffprobe -hide_banner -show_format \
-i :[inputVideo] 2>/dev/null
BASH,
        ];
        $originPath                = File::getPathFromHash($file->hash, $file->suffix, $file->type, 'raw', true);
        $config['normal']['path']  = File::getPathFromHash($file->hash, File::$generatedSuffix['video'][1], $file->type, 'normal', true);
        $config['preview']['path'] = File::getPathFromHash($file->hash, File::$generatedSuffix['video'][0], $file->type, 'preview', true);


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