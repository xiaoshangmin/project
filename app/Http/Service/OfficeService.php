<?php
declare(strict_types=1);

namespace App\Http\Service;

use App\Contract\OfficeInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Filesystem\FilesystemFactory;
use League\Flysystem\Config;
use League\Flysystem\Visibility;

class OfficeService implements OfficeInterface
{

    private string $subDir = 'office';

    #[Inject]
    private FilesystemFactory $factory;

    public function uploadFile($file): array
    {
        $local = $this->factory->get('local');
        $tmpFile = $file->getRealPath();
        $md5 = md5_file($tmpFile);
        $resource = fopen($tmpFile, 'r+');
        $relativePath = $this->subDir . DIRECTORY_SEPARATOR . $md5 . DIRECTORY_SEPARATOR . $file->getClientFilename();
        $local->writeStream($relativePath, $resource,[Config::OPTION_DIRECTORY_VISIBILITY => Visibility::PUBLIC]);
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