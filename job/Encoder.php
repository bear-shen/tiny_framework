<?php namespace Job;

use Lib\DB;
use Lib\GenFunc;
use Lib\Request;
use Model\File;

class Encoder {
    //编码处理类
    public function handle($data) {
        $fileData = File::where('id', $data)->selectOne();
        switch ($fileData['type']) {
            case 'image':
                $this->image($fileData);
                break;
            case 'video':
                $this->video($fileData);
                break;
            case 'audio':
                $this->audio($fileData);
                break;
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
        $config['normal']['path']  = File::getPathFromHash($file->hash, $file->suffix, $file->type, 'normal', true);
        $config['preview']['path'] = File::getPathFromHash($file->hash, $file->suffix, $file->type, 'preview', true);
        //
        $img     = new \Imagick($originPath);
        $originW = $img->getImageWidth();
        $originH = $img->getImageHeight();
        //preview
        $previewImg = $img->clone();
        $scaleRate  = max($originW, $originH) / $config['preview']['max_width'];
        if ($scaleRate > 1) {
            $previewImg->resizeImage($originH / $scaleRate, $originW / $scaleRate, \Imagick::FILTER_QUADRATIC, 1);
            $previewImg->writeImage($config['preview']['path']);
        } else {
            copy($originPath, $config['preview']['path']);
        }
        //normal
        $normalImg = $img->clone();
        $scaleRate = max($originW, $originH) / $config['normal']['max_width'];
        if ($scaleRate > 1) {
            $normalImg->resizeImage($originH / $scaleRate, $originW / $scaleRate, \Imagick::FILTER_QUADRATIC, 1);
            $normalImg->writeImage($config['normal']['path']);
        } else {
            copy($originPath, $config['normal']['path']);
        }
        //
        return true;
    }

    public function video(File $file) {
        $config = [
            'normal'  => [
                'max_width' => 1280,
            ],
            'preview' => [
                'max_width' => 360,
                'quality'   => 60,
            ],
        ];
    }

    public function audio(File $file) {
        $config = [
            'normal' => [
                'bit_rate' => 256,
            ],
        ];
    }
}