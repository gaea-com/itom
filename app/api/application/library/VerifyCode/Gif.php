<?php
namespace VerifyCode;

use VerifyCode\GIFEncoder;

/**
 * 生成gif动态验证码类
 */
class Gif
{
    //可添加到配置中
    private $image;

    private $string;

    private $width = 75;

    private $height = 25;

    private $codes = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPRSTUVWXYZ23456789';

    private $useNoise = true;

    private $useLine = true;

    private $colorList = [];

    public function __construct($width = 75, $height = 25)
    {
        $width ? $this->width   = $width : $this->width;
        $height ? $this->height = $height : $this->height;
    }

    /**
     * ImageCode 生成包含验证码的GIF图片的函数
     *
     * @param $string 字符串
     * @param $width 宽度
     * @param $height 高度
     **/
    public function ImageCode()
    {
        //include_once dirname(__FILE__) . "/GIFEncoder.php";
        // 生成字符串操作
        $this->string = $this->generateCode();
        $authstr      = $this->string ? $this->string : ((time() % 2 == 0) ? mt_rand(1000, 9999) : mt_rand(10000, 99999));
        // 生成一个32帧的GIF动画
        for ($i = 0; $i < 32; $i++) {
            ob_start();
            $this->image = imagecreate($this->width, $this->height);
            imagecolorallocate($this->image, 0, 0, 0);
            // 设定文字颜色数组
            $this->colorList[] = ImageColorAllocate($this->image, 15, 73, 210);
            $this->colorList[] = ImageColorAllocate($this->image, 0, 64, 0);
            $this->colorList[] = ImageColorAllocate($this->image, 0, 0, 64);
            $this->colorList[] = ImageColorAllocate($this->image, 0, 128, 128);
            $this->colorList[] = ImageColorAllocate($this->image, 27, 52, 47);
            $this->colorList[] = ImageColorAllocate($this->image, 51, 0, 102);
            $this->colorList[] = ImageColorAllocate($this->image, 0, 0, 145);
            $this->colorList[] = ImageColorAllocate($this->image, 0, 0, 113);
            $this->colorList[] = ImageColorAllocate($this->image, 0, 51, 51);
            $this->colorList[] = ImageColorAllocate($this->image, 158, 180, 35);
            $this->colorList[] = ImageColorAllocate($this->image, 59, 59, 59);
            $this->colorList[] = ImageColorAllocate($this->image, 0, 0, 0);
            $this->colorList[] = ImageColorAllocate($this->image, 1, 128, 180);
            $this->colorList[] = ImageColorAllocate($this->image, 0, 153, 51);
            $this->colorList[] = ImageColorAllocate($this->image, 60, 131, 1);
            $this->colorList[] = ImageColorAllocate($this->image, 0, 0, 0);
            $fontcolor         = ImageColorAllocate($this->image, 0, 0, 0);
            $gray              = ImageColorAllocate($this->image, 245, 245, 245);
            $color             = imagecolorallocate($this->image, 255, 255, 255);
            $color2            = imagecolorallocate($this->image, 255, 0, 0);
            imagefill($this->image, 0, 0, $gray);
            $space = 15; // 字符间距
            if ($i > 0) {
                // 屏蔽第一帧
                $top = 0;
                for ($k = 0; $k < strlen($authstr); $k++) {
                    $colorRandom = mt_rand(0, sizeof($this->colorList) - 1);
                    $float_top   = rand(0, 4);
                    $float_left  = rand(0, 3);
                    imagestring($this->image, 6, $space * $k, $top + $float_top, substr($authstr, $k, 1), $this->colorList[$colorRandom]);
                }
            }

            if ($this->useNoise) {
                $this->_writeNoise();
            }

            if ($this->useLine) {
                $this->_writeLine();
            }

            imagegif($this->image);
            imagedestroy($this->image);
            $imagedata[] = ob_get_contents();
            ob_clean();
            ++$i;
        }
        $gif = new GIFEncoder($imagedata);
        //Header('Content-type:image/gif');
        return $gif->GetAnimation();
    }
    // 生成验证码字符串
    private function generateCode()
    {
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= substr($this->codes, mt_rand(1, strlen($this->codes) - 1), 1);
        }
        return $code;
    }
    // 添加干扰元素
    private function _writeNoise()
    {
        for ($k = 0; $k < 20; $k++) {
            $colorRandom = mt_rand(0, sizeof($this->colorList) - 1);
            imagesetpixel($this->image, rand() % 70, rand() % 15, $this->colorList[$colorRandom]); //画单个像素
        }
    }
    //添加干扰线
    private function _writeLine()
    {
        // 添加干扰线
        for ($k = 0; $k < 3; $k++) {
            $colorRandom = mt_rand(0, sizeof($this->colorList) - 1);
            $todrawline  = 1;
            if ($todrawline) {
                imageline($this->image, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $this->colorList[$colorRandom]);
            } else {
                $w = mt_rand(0, $this->width);
                $h = mt_rand(0, $this->height);
                imagearc($this->image, $this->width - floor($w / 2), floor($h / 2), $w, $h, rand(90, 180), rand(180, 270), $this->colorList[$colorRandom]);
            }
        }
    }

    // 外部获取验证码
    public function code()
    {
        return strtolower($this->string);
    }
}
