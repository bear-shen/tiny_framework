<?php namespace Lib;

/**
 */
class Captcha {
    private $code     = '';
    private $gd       = false;
    private $charList = '1234567CEFGHJKLMNPQSTUVWXYZ';

    private $config = [
        'width'       => 100,
        'height'      => 40,
        'char_length' => 5,
        'char'        => [
            'size'          => 42,
            'size_shuffle'  => 8,
            'shift'         => 0.2,
            'rotate'        => 0.2,
            'color'         => [32, 128, 128],
            'color_shuffle' => 72,
        ],
        'bk'          => [
            'color'         => [8, 8, 16],
            'color_shuffle' => 16,
        ],
        'shuffle'     => [
            [
                'type'          => 'retangle',
                'size'          => 5,
                'size_shuffle'  => 2,
                'color'         => [64, 32, 32],
                'color_shuffle' => 30,
            ],
            [
                'type'          => 'circle',
                'size'          => 5,
                'size_shuffle'  => 2,
                'color'         => [64, 32, 32],
                'color_shuffle' => 30,
            ],
            [
                'type'          => 'line',
                'size'          => 10,
                'size_shuffle'  => 5,
                'color'         => [64, 32, 32],
                'color_shuffle' => 30,
            ],
        ],
    ];

    public function __construct($length = 5, $width = 200, $height = 80) {
        $this->config['width']       = $width ?: 100;
        $this->config['height']      = $height ?: 100;
        $this->config['char_length'] = $length ?: 5;
        $this->gd                    = imagecreatetruecolor($this->config['width'], $this->config['height']);
        imagealphablending($this->gd, false);
        imagesavealpha($this->gd, true);
        $this->charList = str_split($this->charList);
    }

    public function getImg() {
        $this->genBk();
        $this->genShuffle();
        $this->genChar();
        ob_start();
        imagepng($this->gd);
        $imgStr = ob_get_clean();
        return $imgStr;
    }

    public function getCode() {
        return $this->code;
    }

    //-----------------------------------------

    private function genBk() {
        imagefilledrectangle(
            $this->gd,
            0,
            0,
            $this->config['width'],
            $this->config['height'],
//            $this->genColor([255, 255, 255], 0)
            $this->genColor($this->config['bk']['color'], $this->config['bk']['color_shuffle'])
        );
    }

    private function genChar() {
        $charList = [];
        $optLen   = sizeof($this->charList);
        for ($i1 = 0; $i1 < $this->config['char_length']; $i1++) {
            $charList[] = $this->charList[mt_rand(0, $optLen - 1)];
        }
        //碍于中心旋转的问题，创建新图片类之后贴上去
        $fontPath = base_path('lib/captcha1.ttf');
        foreach ($charList as $index => $char) {
            $fontSize = max(mt_rand(
                                $this->config['char']['size'] - $this->config['char']['size_shuffle'],
                                $this->config['char']['size'] + $this->config['char']['size_shuffle']
                            ), 1);
            $rotate   = mt_rand(
                -1 * 90 * $this->config['char']['rotate'],
                90 * $this->config['char']['rotate']
            );
            $box      = imagettfbbox(
                $fontSize,
                $rotate,
                $fontPath,
                $char
            );
            $wh       = [
                max($box[0], $box[2], $box[4], $box[6]) - min($box[0], $box[2], $box[4], $box[6]),
                max($box[1], $box[3], $box[5], $box[7]) - min($box[1], $box[3], $box[5], $box[7]),
            ];
//            $fontSize = [
//                'w' => imagettfbbox($font),
//                'h' => imagefontheight($font),
//            ];
            $dstX = ($this->config['width'] / $this->config['char_length']) *
                    ($index + 0.5) - $wh[0] / 2;
            $dstY = $this->config['height'] *
                    (0.5) + $wh[1] / 2;
            imagettftext(
                $this->gd,
                $fontSize,
                $rotate,
//                0,
                $dstX, $dstY,
                $this->genColor(
                    $this->config['char']['color'], $this->config['char']['color_shuffle']
                ),
                $fontPath,
                $char
            );
            /*$sub = imagecreatetruecolor($subL, $subL);
            imagecolorallocatealpha($sub, 255, 255, 255, 0xFF);
            imagesavealpha($sub, true);
            imagealphablending($sub, false);
            imagecolortransparent($sub, $this->genColor([0, 0, 0], 0, $sub));
            imagechar(
                $sub, 1, $subL, $subL,
                $char,
                $this->genColor(
                    $this->config['char']['color'],
                    $this->config['char']['color_shuffle'],
                    $sub
                )
            );
            imagecopymerge(
                $this->gd,
                $sub,
                $dstX,
                $dstY,
                0,
                0,
                $subL,
                $subL,
                100
            );*/
        }
        $this->code = implode('', $charList);
        return true;
    }

    private function genShuffle() {
        foreach ($this->config['shuffle'] as $item) {
            $size = max(mt_rand($item['size'] - $item['size_shuffle'], $item['size'] + $item['size_shuffle']), 0);
            switch ($item['type']) {
                case 'line':
                    for ($i1 = 0; $i1 < $size; $i1++) {
                        $plot = [
                            $this->genOffset(),
                            $this->genOffset(),
                        ];
                        imageline(
                            $this->gd,
                            $plot[0][0],
                            $plot[0][1],
                            $plot[1][0],
                            $plot[1][1],
                            $this->genColor($item['color'], $item['color_shuffle'])
                        );
                    }
                    break;
                case 'circle':
                    for ($i1 = 0; $i1 < $size; $i1++) {
                        $plot = [
                            $this->genOffset(),
                            $this->genOffset(),
                        ];
                        imagefilledellipse(
                            $this->gd,
                            $plot[0][0],
                            $plot[0][1],
                            $plot[1][0],
                            $plot[1][1],
                            $this->genColor($item['color'], $item['color_shuffle'])
                        );
                    }
                    break;
                case 'retangle':
                    for ($i1 = 0; $i1 < $size; $i1++) {
                        $plot = [
                            $this->genOffset(),
                            $this->genOffset(),
                        ];
                        imagefilledrectangle(
                            $this->gd,
                            $plot[0][0],
                            $plot[0][1],
                            $plot[1][0],
                            $plot[1][1],
                            $this->genColor($item['color'], $item['color_shuffle'])
                        );
                    }
                    break;
            }
        }
    }

    private function genOffset() {
        $x = mt_rand(0, $this->config['width']);
        $y = mt_rand(0, $this->config['height']);
        return [$x, $y];
    }

    private function genColor($rgb, $shuffle, $source = false) {
        $r      = min(max($rgb[0] + mt_rand(-1 * $shuffle, $shuffle), 0), 255);
        $g      = min(max($rgb[1] + mt_rand(-1 * $shuffle, $shuffle), 0), 255);
        $b      = min(max($rgb[2] + mt_rand(-1 * $shuffle, $shuffle), 0), 255);
        $target = imagecolorallocate($source ? $source : $this->gd, $r, $g, $b);
        return $target;
    }

    //-----------------------------------------

    public function setChar($config = []) {
        $this->config['char'] = $config + $this->config['char'];
    }

    public function appendShuffle($config = []) {
        $this->config['shuffle'][] = $config;
    }

    public function setBk($config = []) {
        $this->config['bk'] = $config + $this->config['bk'];
    }
}