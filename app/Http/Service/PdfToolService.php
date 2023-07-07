<?php
declare(strict_types=1);

namespace App\Http\Service;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\HttpMessage\Upload\UploadedFile;
use League\Flysystem\Config;
use League\Flysystem\Visibility;

class PdfToolService
{

    private string $subDir = 'pdf';

    public static array $paper = [
        ['width' => 8.5, 'height' => 11],
        ['width' => 8.5, 'height' => 14],
        ['width' => 11, 'height' => 17],
        ['width' => 17, 'height' => 11],
        ['width' => 33.1, 'height' => 46.8],
        ['width' => 23.4, 'height' => 33.1],
        ['width' => 16.54, 'height' => 23.4],
        ['width' => 11.7, 'height' => 16.54],
        ['width' => 8.27, 'height' => 11.7],
        ['width' => 5.83, 'height' => 8.27],
        ['width' => 4.13, 'height' => 5.83],
    ];


    #[Inject]
    private FilesystemFactory $factory;

    public function uploadFile(UploadedFile $file): array
    {
        $local = $this->factory->get('local');
        $tmpFile = $file->getRealPath();
        $md5 = md5_file($tmpFile);
        $resource = fopen($tmpFile, 'r+');
        $relativePath = $this->subDir . DIRECTORY_SEPARATOR . $md5 . DIRECTORY_SEPARATOR . uniqid() . '.' . $file->getExtension();
        $local->writeStream($relativePath, $resource, [Config::OPTION_DIRECTORY_VISIBILITY => Visibility::PUBLIC]);
        fclose($resource);
        return ['key' => $md5, 'relativePath' => $relativePath,];
    }

    /**
     * @param string $dir
     * @return $this
     */
    public function setSubDir(string $dir): self
    {
        $this->subDir = $dir;
        return $this;
    }
}