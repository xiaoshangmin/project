<?php
declare(strict_types=1);

namespace App\Job;

use Hyperf\AsyncQueue\Job;
use Hyperf\Contract\StdoutLoggerInterface;
use function Hyperf\Support\make;
use Hyperf\Redis\Redis;


class GifJob extends Job
{
    public $params;

    protected int $maxAttempts = 2;



    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $auth = $this->params['auth'];
        $taskId = $this->params['taskId'];
        $logger = make(StdoutLoggerInterface::class);
        $cache = make(Redis::class);
        $path = BASE_PATH . '/storage/' . date("Ymd") . DIRECTORY_SEPARATOR . $auth . DIRECTORY_SEPARATOR;
        $finalFileName = $path . $taskId . '.gif';
        $images = [];
        for ($i = 0; $i < 30; $i++) {
            $images[] = $path . "{$i}.png";
        }
        try {
            $this->createAnimatedGif($images, $finalFileName);
            $cache->set($taskId,1);
        } catch (\Throwable $e) {
            // 错误处理
            $logger->error("GIF生成失败:[{$auth}':[{$taskId}]:" . $e->getMessage());
        }
    } 

     /**
     * 使用ImageMagick将PNG图片转换为动态GIF
     * 
     * @param array $imagePaths PNG图片路径数组
     * @param string $outputPath 输出的GIF文件路径
     * @param int $delay 每帧延迟时间(毫秒) 2约等于60帧
     */
    private function createAnimatedGif($imagePaths, $outputPath, $delay = 2)
    {
        // 检查ImageMagick扩展是否可用
        if (!extension_loaded('imagick')) {
            throw new \Exception('ImageMagick扩展未安装');
        }

        try {
            // 创建Imagick对象
            $imagick = new \Imagick();

            // 遍历图片并添加到gif
            foreach ($imagePaths as $path) {
                $frame = new \Imagick($path);

                // 设置帧延迟 (ImageMagick使用1/100秒为单位)
                $frame->setImageDelay($delay);

                // 添加帧
                $imagick->addImage($frame);

                // 及时清理内存
                $frame->clear();
            }

            // 合成gif
            $imagick->setImageFormat('gif');

            // 适度压缩
            $imagick->setImageCompressionQuality(75);

            // 优化gif
            $imagick->optimizeImageLayers();

            // 写入文件
            $imagick->writeImages($outputPath, true);

            // 清理资源
            $imagick->clear();
            $imagick->destroy();
        } catch (\Exception $e) {
            throw new \Exception('创建GIF失败：' . $e->getMessage());
        }
    }

}