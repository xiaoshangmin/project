<?php

namespace App\Http\Service;

class DouyinService extends Spider
{

    //https://vercel.com/dashboard
    private string $xbogusApiUrl = 'https://dy.wowyou.cc/';

    public function analysis(string $url): array
    {
//        if (strpos($url, 'iesdouyin')) {
//            preg_match('/\/(\d+)\//', $url, $id);
//        } else {
        $header = get_headers($url, true);
        if (is_string($header['Location'])) {
            $loc = $header['Location'];
        } else {
            if (str_contains($header['Location'][1], 'video')) {
                $loc = $header['Location'][1];
            } else {
                $loc = $header['Location'][0];
            }
        }
        if (strpos($loc, "video")) {
            preg_match('/video\/(.*)\/\?/', $loc, $id);
        }
        if (strpos($loc, "note")) {
            preg_match('/note\/(.*)\/\?/', $loc, $id);
        }

//        }
        $dyUrl = 'https://www.douyin.com/aweme/v1/web/aweme/detail/?aweme_id=' . $id[1] . '&aid=1128&version_name=23.5.0&device_platform=android&os_version=2333';
        $data = ['url' => $dyUrl, 'userAgent' => self::UA];
        $header = ['Content-Type' => 'application/json'];
        $rs = $this->curl($this->xbogusApiUrl, $header, $data);
        $xbogusData = json_decode($rs, true);
        if (!isset($xbogusData['data'])) {
            return [];
        }
        $dyApiUrl = $xbogusData['data']['url'];
        $msToken = $xbogusData['data']['mstoken'];
        $ttwid = $xbogusData['data']['ttwid'];
        $header = [
            "Referer" => "https://www.douyin.com/",
            "Cookie" => "msToken={$msToken};odin_tt=324fb4ea4a89c0c05827e18a1ed9cf9bf8a17f7705fcc793fec935b637867e2a5a9b8168c885554d029919117a18ba69; ttwid={$ttwid}; bd_ticket_guard_client_data=eyJiZC10aWNrZXQtZ3VhcmQtdmVyc2lvbiI6MiwiYmQtdGlja2V0LWd1YXJkLWNsaWVudC1jc3IiOiItLS0tLUJFR0lOIENFUlRJRklDQVRFIFJFUVVFU1QtLS0tLVxyXG5NSUlCRFRDQnRRSUJBREFuTVFzd0NRWURWUVFHRXdKRFRqRVlNQllHQTFVRUF3d1BZbVJmZEdsamEyVjBYMmQxXHJcbllYSmtNRmt3RXdZSEtvWkl6ajBDQVFZSUtvWkl6ajBEQVFjRFFnQUVKUDZzbjNLRlFBNUROSEcyK2F4bXAwNG5cclxud1hBSTZDU1IyZW1sVUE5QTZ4aGQzbVlPUlI4NVRLZ2tXd1FJSmp3Nyszdnc0Z2NNRG5iOTRoS3MvSjFJc3FBc1xyXG5NQ29HQ1NxR1NJYjNEUUVKRGpFZE1Cc3dHUVlEVlIwUkJCSXdFSUlPZDNkM0xtUnZkWGxwYmk1amIyMHdDZ1lJXHJcbktvWkl6ajBFQXdJRFJ3QXdSQUlnVmJkWTI0c0RYS0c0S2h3WlBmOHpxVDRBU0ROamNUb2FFRi9MQnd2QS8xSUNcclxuSURiVmZCUk1PQVB5cWJkcytld1QwSDZqdDg1czZZTVNVZEo5Z2dmOWlmeTBcclxuLS0tLS1FTkQgQ0VSVElGSUNBVEUgUkVRVUVTVC0tLS0tXHJcbiJ9"
        ];
        $jsonStr = $this->curl($dyApiUrl, $header);
        $dyDataArr = json_decode($jsonStr, true);
        $item = $dyDataArr['aweme_detail'];
        //                    # 抖音/Douyin
        //                    2: 'image',
        //                    4: 'video',
        //                    68: 'image',
        //                    # TikTok
        //                    0: 'video',
        //                    51: 'video',
        //                    55: 'video',
        //                    58: 'video',
        //                    61: 'video',
        //                    150: 'image'
        //                }
        if ($item['aweme_type'] == 2 || $item['aweme_type'] == 68) {
            $imagesList = $item["images"];
            $pics = [];
            foreach ($imagesList as $url) {
                $pics[] = $url['url_list'][0];
            }
            return $this->images($pics, $item['desc']);
        } else {
            $video_url = $item["video"]["play_addr"]["url_list"][0];
            return $this->video($item['desc'], $item['video']['origin_cover']['url_list'][0], $video_url);
        }
    }
}