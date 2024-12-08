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

    private $logger;

    public function __construct($params)
    {
        $this->params = $params;
        $this->logger = make(StdoutLoggerInterface::class);
    }

    public function handle()
    {
        $auth = $this->params['auth'];
        $taskId = $this->params['taskId'];
        $cache = make(Redis::class);
        $path = BASE_PATH . '/storage/' . date("Ymd") . DIRECTORY_SEPARATOR . $auth . DIRECTORY_SEPARATOR;
        $finalFileName = $path . $taskId . '.gif';
        $images = [];
        for ($i = 0; $i < 30; $i++) {
            $images[] = $path . "{$i}.png";
        }
        try {
            $this->createGifFromImages($images, $finalFileName);
            $cache->set($taskId, 1,7200);
        } catch (\Throwable $e) {
            // 错误处理
            $this->logger->error("GIF生成失败:[{$auth}':[{$taskId}]:" . $e->getMessage());
        }
    }


    /**
     * 使用 FFmpeg 将多张图片合成 GIF 动画
     *
     * @param array  $imageFiles 图片文件路径数组
     * @param string $outputGif  输出 GIF 文件路径
     * @param int    $frameRate  每秒帧数
     * @return bool  成功返回 true，失败返回 false
     */
    function createGifFromImages(array $imageFiles, string $outputGif, int $frameRate = 60): bool
    {
        // 检查 FFmpeg 是否存在
        $ffmpegPath = '/usr/bin/ffmpeg'; // 根据实际安装路径调整

        // 获取图片宽高（以第一张图片为参考）
        [$width, $height] = getimagesize($imageFiles[0]);
        if (!$width || !$height) {
            throw new \Exception("无法获取图片宽高: {$imageFiles[0]}");
        }
        $imageDir = dirname($imageFiles[0]);
        // 创建临时文件名
        // $tempVideo = tempnam(sys_get_temp_dir(), 'temp_video') . '.mp4';
        // $tempVideo = $imageDir . '/temp_video.mp4';
        $tempPalette =  $imageDir . '/palette.png';


        try {
            // 合成视频命令
            $cmdVideo = sprintf(
                '%s -y -framerate %d -i %s -vf "scale=%d:%d:flags=lanczos,palettegen" %s',
                escapeshellcmd($ffmpegPath),
                $frameRate,
                escapeshellarg($imageDir . '/%d.png'),
                $width,
                $height,
                escapeshellarg($tempPalette)
            );
            $this->logger->info($cmdVideo);
            // 运行命令生成调色板
            exec($cmdVideo, $output, $resultCode);
            if ($resultCode !== 0) {
                throw new \Exception("生成调色板失败: " . implode("\n", $output));
            }
    
            // 用调色板生成 GIF
            $cmdGif = sprintf(
                '%s -y -framerate %d -i %s -i %s -lavfi "paletteuse" %s',
                escapeshellcmd($ffmpegPath),
                $frameRate,
                escapeshellarg($imageDir . '/%d.png'),
                escapeshellarg($tempPalette),
                escapeshellarg($outputGif)
            );
            $this->logger->info($cmdGif);
            exec($cmdGif, $output, $resultCode);
            if ($resultCode !== 0) {
                throw new \Exception("生成 GIF 失败: " . implode("\n", $output));
            }
    
            return true;
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            return false;
        } finally {
            // 删除临时文件
            if (file_exists($tempPalette)) {
                unlink($tempPalette);
            }
        }

        // try {
        //     // 合成视频命令
        //     $cmdVideo = sprintf(
        //         '%s -y -framerate %d -i %s -vf "scale=%d:%d" %s',
        //         escapeshellcmd($ffmpegPath),
        //         $frameRate,
        //         escapeshellarg(dirname($imageFiles[0]) . '/%d.png'), // 图片按顺序命名格式（image1.jpg, image2.jpg...）
        //         $width,
        //         $height,
        //         escapeshellarg($tempVideo)
        //     );
        //     $this->logger->info($cmdVideo);
        //     // 运行命令生成视频
        //     exec($cmdVideo, $output, $resultCode);
        //     if ($resultCode !== 0) {
        //         throw new \Exception("图片合成视频失败: " . implode("\n", $output));
        //     }

        //     // 视频转 GIF 命令
        //     $cmdGif = sprintf(
        //         '%s -y -i %s -vf "scale=%d:%d:flags=lanczos" %s',
        //         escapeshellcmd($ffmpegPath),
        //         escapeshellarg($tempVideo),
        //         $width,
        //         $height,
        //         escapeshellarg($outputGif)
        //     );
        //     $this->logger->info($cmdGif);
        //     // 运行命令生成 GIF
        //     exec($cmdGif, $output, $resultCode);
        //     if ($resultCode !== 0) {
        //         throw new \Exception("视频转 GIF 失败: " . implode("\n", $output));
        //     }

        //     return true;
        // } catch (\Exception $e) {
        //     // error_log($e->getMessage());
        //     return false;
        // } finally {
        //     // 删除临时视频文件
        //     if (file_exists($tempVideo)) {
        //         unlink($tempVideo);
        //     }
        // }
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
