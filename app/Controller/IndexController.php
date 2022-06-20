<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller;

use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;

#[Controller()]
class IndexController extends AbstractController
{
    public function index()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }

    #[GetMapping(path: "analysis")]
    public function analysis(FilesystemFactory $factory){
        $local = $factory->get('local');
        $local->read();

    }

    #[PostMapping(path: "upload")]
    public function upload(FilesystemFactory $factory){
        $file = $this->request->file('file');
        $resource = fopen($file->getRealPath(),'r+');
        $local = $factory->get('local');
        try {
            $local->writeStream("test.pptx",$resource);
            fclose($resource);
        }catch (FilesystemException|UnableToWriteFile $exception){
            echo $exception;
        }
    }

    public function pdf2pic(){
        foreach($fileList as $checkNo=> $file){
            $pdf2img = new \Spatie\PdfToImage\Pdf($file);
            $rs = $pdf2img->saveAllPagesAsImages($pdfPath,$checkNo);
            $savePath = "{$pdfPath}{$checkNo}-merge.jpg";
            if(count($rs) > 1){
                if(true === CompositeImage($rs, $savePath)){
                    $mergeList[] = $savePath;
                }
            }else{
                $mergeList[] = $rs[0];
            }
        }
    }
}
