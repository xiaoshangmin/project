<?php

namespace App\Http\Service;

class XhsService extends Spider
{

    public function analysis(string $url)
    {
        $rs = $this->curl($url);
        preg_match('/__INITIAL_STATE__=([^<]+)</', $rs, $jsonStr);
        $jsonStr = str_replace('undefined', 'null', trim($jsonStr[1], '='));
        $this->logger->info($jsonStr);
        $json = json_decode($jsonStr, true);
        $noteMap = $json['note']['noteDetailMap'];
        foreach ($noteMap as $item) {
            if ($item['note']['type'] == 'video') {
                $url = '';
                $stream = $item['note']['video']['media']['stream'];
                if (isset($stream['h264'][0])) {
                    $url = $stream['h264'][0]['masterUrl'];
                }
                if (isset($stream['h265'][0])) {
                    $url = $stream['h265'][0]['masterUrl'];
                }
                return $this->video($item['note']['desc'], '', $url);
            }else{
                $imageList = $item['note']['imageList'];
                $urlList = array_column($imageList,'urlDefault');
                return $this->images($urlList,$item['note']['desc']);
            }
        }
        return [];
    }

}