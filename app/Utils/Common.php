<?php
declare(strict_types=1);

namespace App\Util;

use GuzzleHttp\Cookie\CookieJar;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use GuzzleHttp\Client;

class Common
{
//    private ClientFactory $clientFactory;
//
//    public function __construct(ClientFactory $clientFactory)
//    {
//        $this->clientFactory = $clientFactory;
//    }

    /**
     * 合并多图
     * @param array $imagePath 图片数组
     * @param string $savePath 合并图片的保存路径
     * @param string $axis 合成方向
     * @param string $saveType 合成图片类型
     * @return bool
     */
    static public function CompositeImage(array $imagePath, string $savePath, string $axis = 'y', string $saveType = 'jpeg'): bool
    {
        if (count($imagePath) < 2) {
            return false;
        }
        //定义一个图片对象数组
        $imageObj = [];
        //获取图片信息
        $width = 0;
        $height = 0;
        foreach ($imagePath as $v) {
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
        $firstWidth = imagesx($imageObj[0]);
        $firstHeight = imagesy($imageObj[0]);
        //按轴生成画布方向
        if ($axis == 'x') {
            //TODO X轴无缝合成时请保证所有图片高度相同
            $img = imagecreatetruecolor($width, $firstHeight);
        } else {
            //TODO Y轴无缝合成时请保证所有图片宽度相同
            $img = imagecreatetruecolor($firstWidth, $height);
        }
        //为一幅图像分配颜色 这里是白色
//        $color = imagecolorallocate($img, 255, 0, 0);
//        imagefill($imageObj[0], 0, 0, $color);
//        //将 image 图像中的透明色设定为 color
//        imagecolortransparent($img, $color);
//        //重采样拷贝部分图像并调整大小
//        imagecopyresampled($img, $imageObj[0], 0, 0, 0, 0, imagesx($imageObj[0]), imagesy($imageObj[0]), imagesx($imageObj[0]), imagesy($imageObj[0]));
        $mergeX = 0;
        $mergeY = 0;
//        //循环生成图片
        for ($i = 0; $i <= count($imageObj) - 1; $i++) {
            if ($axis == 'x') {
                imagecopy($img, $imageObj[$i], $mergeX, 0, 0, 0, imagesx($imageObj[$i]), imagesy($imageObj[$i]));
                $mergeX += $firstWidth;
            } else {
                imagecopy($img, $imageObj[$i], 0, $mergeY, 0, 0, imagesx($imageObj[$i]), imagesy($imageObj[$i]));
                $mergeY += $firstHeight;
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
        imagedestroy($img);
        return true;

    }


    public function get_content($url, $headers = [], $decoded = True)
    {
        /**Gets the content of a URL via sending a HTTP GET request.
         *
         * Args:
         * url: A URL.
         * headers: Request headers used by the client.
         * decoded: Whether decode the response body using UTF-8 or the charset specified in Content-Type.
         *
         * Returns:
         * The content as a string.
         **/
        $client = new Client([
            'timeout' => 5,
            'verify' => false
        ]);
        $options = [
            'headers' => $headers,
            'decode_content' => 'gzip,deflate',
            'allow_redirects' => true,
        ];
        if (!empty($cookies)) {
            $cookies = CookieJar::fromArray($cookies, $url);
            $options['cookies'] = $cookies;
        }
        $response = $client->get($url, $options);
        return $response->getBody()->getContents();

    }
}