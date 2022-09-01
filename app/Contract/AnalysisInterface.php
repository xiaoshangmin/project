<?php
declare(strict_types=1);
namespace App\Contract;

interface AnalysisInterface
{

    public function xhs(string $url):array;

    public function douyin(string $url):array;

    public function weibo(string $url):array;

    public function bilibili(string $url):array;

    public function kuaishou(string $url):array;

}