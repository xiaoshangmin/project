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

    private $cache;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $auth = $this->params['auth'];
        $taskId = $this->params['taskId'];
        $path = $this->params['path'];

        $this->logger = make(StdoutLoggerInterface::class);
        $this->cache = make(Redis::class);

        $finalFileName = $path . $taskId . '.gif';
        $finalOpFileName = $path . $taskId . '-op.gif';

        $images = [];
        for ($i = 0; $i < 10; $i++) {
            $images[] = $path . "{$i}.png";
        }
        $appendImage = [];
        foreach ($images as $index=>$image) {
            $newIndex = $index + 10;
            $destination =  $path . "{$newIndex}.png";
            copy($image, $destination);
            $appendImage[$index] = $destination;
        }
        $images = array_merge($images, $appendImage);
        try {
            $this->unlinkGifFile($path);
            $res = $this->createGifFromImages($images, $finalFileName,$finalOpFileName);
            $this->cache->set($taskId, $res, 3600);
        } catch (\Throwable $e) {
            // 错误处理
            $this->cache->set($taskId, 'err', 3600);
            $this->logger->error("GIF生成失败:[{$auth}':[{$taskId}]:" . $e->getMessage());
        }
    }


    /**
     * 使用 FFmpeg 将多张图片合成 GIF 动画
     *
     * @param array $imageFiles 图片文件路径数组
     * @param string $outputGif 输出 GIF 文件路径
     * @param string $finalOpFileName 输出 优化的GIF 文件路径
     * @param int $frameRate 每秒帧数
     * @return bool  成功返回 true，失败返回 false
     */
    function createGifFromImages(array $imageFiles, string $outputGif, string $finalOpFileName,int $frameRate = 20)
    {
        // 检查 FFmpeg 是否存在
        $ffmpegPath = '/usr/bin/ffmpeg'; // 根据实际安装路径调整

        // 获取图片宽高（以第一张图片为参考）
//        [$width, $height] = getimagesize($imageFiles[0]);
//        if (!$width || !$height) {
//            throw new \Exception("无法获取图片宽高: {$imageFiles[0]}");
//        }
        $imageDir = dirname($imageFiles[0]);
//        $outputDir = dirname($outputGif[0]);
        // 创建临时文件名
        // $tempVideo = tempnam(sys_get_temp_dir(), 'temp_video') . '.mp4';
        $tempVideo = $imageDir . '/temp_video.mp4';
        $tempPalette = $imageDir . '/palette.png';


        try {

            $cmdGif = sprintf(
                '%s -y -framerate %d -i %s -vf "scale=trunc(iw/2):trunc(ih/2),mpdecimate,split[s0][s1];[s0]palettegen[p];[s1][p]paletteuse,loop=0:32767:0,setpts=N/FRAME_RATE/TB" %s',
                escapeshellcmd($ffmpegPath),
                $frameRate,
                escapeshellarg($imageDir . '/%d.png'),
                escapeshellarg($outputGif)
            );
            $this->logger->info($cmdGif);
            exec($cmdGif, $output, $resultCode);
            if ($resultCode !== 0) {
                throw new \Exception("生成 GIF 失败: " . implode("\n", $output));
            }
            //优化
            $cmdOptimize =  sprintf("gifsicle --optimize=3 %s -o %s",
                escapeshellarg($outputGif),
                escapeshellarg($finalOpFileName)
            );
            $this->logger->info($cmdOptimize);
            exec($cmdOptimize, $output, $resultCode);
            if ($resultCode !== 0) {
                $msg = "优化 GIF 失败: " . implode("\n", $output);
                $this->logger->info($msg);
                return 'gif';
            }
            return 'op-gif';
        } catch (\Exception $e) {
            return false;
        } finally {
            // 删除临时文件
//            if (file_exists($tempPalette)) {
//                unlink($tempPalette);
//            }
        }


//        try {
//            // 合成视频命令
//            $cmdVideo = sprintf(
//                '%s -y -framerate %d -i %s -vf "scale=%d:%d:flags=lanczos,fps=%d,palettegen=max_colors=256:stats_mode=diff" %s',
//                escapeshellcmd($ffmpegPath),
//                $frameRate,
//                escapeshellarg($imageDir . '/%d.png'),
//                $width,
//                $height,
//                $frameRate,
//                escapeshellarg($tempPalette)
//            );
//            $this->logger->info($cmdVideo);
//            // 运行命令生成调色板
//            exec($cmdVideo, $output, $resultCode);
//            if ($resultCode !== 0) {
//                throw new \Exception("生成调色板失败: " . implode("\n", $output));
//            }
//
//            // 用调色板生成 GIF
//            $cmdGif = sprintf(
//                '%s -y -framerate %d -i %s -i %s -lavfi "fps=%d,paletteuse=dither=sierra2_4a" -q:v 10 -preset veryslow -gifflags +transdiff -f gif %s',
//                escapeshellcmd($ffmpegPath),
//                $frameRate,
//                escapeshellarg($imageDir . '/%d.png'),
//                escapeshellarg($tempPalette),
//                $frameRate,
//                escapeshellarg($outputGif)
//            );
//            $this->logger->info($cmdGif);
//            exec($cmdGif, $output, $resultCode);
//            if ($resultCode !== 0) {
//                throw new \Exception("生成 GIF 失败: " . implode("\n", $output));
//            }
//
//            return true;
//        } catch (\Exception $e) {
//            $this->logger->info($e->getMessage());
//            return false;
//        } finally {
//            // 删除临时文件
//            if (file_exists($tempPalette)) {
//                unlink($tempPalette);
//            }
//        }

//         try {
//             // 合成视频命令
//             $cmdVideo = sprintf(
//                 '%s -y -framerate %d -i %s -vf "scale=%d:%d" %s',
//                 escapeshellcmd($ffmpegPath),
//                 $frameRate,
//                 escapeshellarg(dirname($imageFiles[0]) . '/%d.png'), // 图片按顺序命名格式（image1.jpg, image2.jpg...）
//                 $width,
//                 $height,
//                 escapeshellarg($tempVideo)
//             );
//             $this->logger->info($cmdVideo);
//             // 运行命令生成视频
//             exec($cmdVideo, $output, $resultCode);
//             if ($resultCode !== 0) {
//                 throw new \Exception("图片合成视频失败: " . implode("\n", $output));
//             }
//
//             // 视频转 GIF 命令
//             $cmdGif = sprintf(
//                 '%s -y -i %s -vf "scale=%d:%d:flags=lanczos" %s',
//                 escapeshellcmd($ffmpegPath),
//                 escapeshellarg($tempVideo),
//                 $width,
//                 $height,
//                 escapeshellarg($outputGif)
//             );
//             $this->logger->info($cmdGif);
//             // 运行命令生成 GIF
//             exec($cmdGif, $output, $resultCode);
//             if ($resultCode !== 0) {
//                 throw new \Exception("视频转 GIF 失败: " . implode("\n", $output));
//             }
//
//             return true;
//         } catch (\Exception $e) {
//             // error_log($e->getMessage());
//             return false;
//         } finally {
//             // 删除临时视频文件
//             if (file_exists($tempVideo)) {
//                 unlink($tempVideo);
//             }
//         }


    }


    private function unlinkGifFile($directory): void
    {
        // 查找目录下所有.png和.gif文件
//        $pngFiles = glob($directory . '/*.png');
        $gifFiles = glob($directory . '/*.gif');

        // 合并两个数组
        $filesToDelete = $gifFiles;//array_merge($pngFiles, $gifFiles);

        // 循环删除文件
        foreach ($filesToDelete as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
