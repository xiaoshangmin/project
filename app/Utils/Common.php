<?php
declare(strict_types=1);
namespace App\Util;

class Common
{

    static public function CompositeImage(array $imagePath, string $savePath, string $axis = 'y', string $saveType = 'jpeg'):bool
    {
        if (count($imagePath) < 2) {
            return false;
        }
        //定义一个图片对象数组
        $imageObj = [];
        //获取图片信息
        $width = 0;
        $height = 0;
        foreach ($imagePath as $k => $v) {
            $picInfo = getimagesize($v);
            list($mime, $type) = explode('/', $picInfo['mime']);
            //获取宽高度
            $width += $picInfo[0];
            $height += $picInfo[1];
            if ($type == 'jpeg') {
                $imageObj[] = imagecreatefromjpeg($v);
            } elseif ($type == 'png') {
                $imageObj[] = imagecreatefrompng($v);
            } else {
                $imageObj[] = imagecreatefromgif($v);
            }
        }
        //按轴生成画布方向
        if ($axis == 'x') {
            //TODO X轴无缝合成时请保证所有图片高度相同
            $img = imageCreatetruecolor($width, imagesy($imageObj[0]));
        } else {
            //TODO Y轴无缝合成时请保证所有图片宽度相同
            $img = imageCreatetruecolor(imagesx($imageObj[0]), $height);
        }
        //创建画布颜色
        $color = imagecolorallocate($img, 255, 255, 255);
        imagefill($imageObj[0], 0, 0, $color);
        //创建画布
        imageColorTransparent($img, $color);
        imagecopyresampled($img, $imageObj[0], 0, 0, 0, 0, imagesx($imageObj[0]), imagesy($imageObj[0]), imagesx($imageObj[0]), imagesy($imageObj[0]));
        $yx = imagesx($imageObj[0]);
        $x = 0;
        $yy = imagesy($imageObj[0]);
        $y = 0;
        //循环生成图片
        for ($i = 1; $i <= count($imageObj) - 1; $i++) {
            if ($axis == 'x') {
                $x = $x + $yx;
                imagecopy($img, $imageObj[$i], $x, 0, 0, 0, imagesx($imageObj[$i]), imagesy($imageObj[$i]));
            } else {
                $y = $y + $yy;
                imagecopy($img, $imageObj[$i], 0, $y, 0, 0, imagesx($imageObj[$i]), imagesy($imageObj[$i]));
            }
        }
        //设置合成后图片保存类型
        if ($saveType == 'png') {
            imagepng($img, $savePath);
        } elseif ($saveType == 'jpg' || $saveType == 'jpeg') {
            imagejpeg($img, $savePath);
        } else {
            imagegif($img, $savePath);
        }
        return true;

    }
}