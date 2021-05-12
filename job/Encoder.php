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
        $config['normal']['path']  = File::getPathFromHash($file->hash, $file->suffix, $file->type, 'normal', true);
        $config['preview']['path'] = File::getPathFromHash($file->hash, $file->suffix, $file->type, 'preview', true);
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
        $config = [
            'normal'  => [
                'max_width' => 1280,
            ],
            'preview' => [
                'max_width' => 360,
                'quality'   => 60,
            ],
        ];
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