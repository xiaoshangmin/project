<?php

namespace App\Http\Service;

class KuaishouService extends Spider
{


    /**
     * 抓包提取下载小助手接口
     * @param string $url
     * @return array|string[]
     */
    public function analysis(string $url)
    {
        $data = [
            "token" => "",
            "link" => $url
        ];
        $rs = $this->curl('https://spxz.doudoukeji.club/video/getVideo', [
            'Content-Type' => 'application/json'
        ], $data);
        $jsonStr = json_decode($rs, true);
        if ($jsonStr['code'] == 1) {
            $info = $this->curl("https://spxz.doudoukeji.club/video/videoInfo?token=184669&id={$jsonStr['data']}");
            $this->logger->info('ks', [$info]);
            $res = json_decode($info, true);
            if ($res['code'] == 1) {
                if ($res['data']['videoInfo']['type'] == 1) {
                    return $this->video('', '', $res['data']['videoInfo']['url']);
                } else {
                    $imageList = explode(',', $res['data']['videoInfo']['url']);
                    return $this->images($imageList, '');
                }
            }
        }
        return [];
    }


    public function analysis2(string $url)
    {
        $text = $this->curl($url);
//        $this->logger->info("st", [$text]);
        preg_match('/<script>window.__APOLLO_STATE__=([^;]+)/', $text, $response);

        $this->logger->info($response[1]);
        $json = json_decode($response[1], true);
        foreach ($json['defaultClient'] as $key => $item) {
            if (str_starts_with($key, 'VisionVideoDetailPhoto')) {
                return $this->video($item['caption'], '', $item['photoUrl']);
            }
        }
        return [];
    }

    /**
     * 本地可以 腾讯云上不行
     * @param string $url
     * @return array|string[]
     */
    public function analysis1(string $url)
    {
        $locs = get_headers($url, true)['Location'];
        if (is_array($locs)) {
            $locs = $locs[1];
        } elseif (is_string($locs)) {

        }

        $this->logger->info(json_encode($locs));
        preg_match('/photoId=(.*?)\&/', $locs, $matches);
        $time = time() * 1000;
        $headers = [
            'Cookie' => "did=web_f1114dcab7c9403a87a65dbd2574137a; didv={$time};",
            'Referer' => $locs,
            'Origin' => 'https://v.m.chenzhongtech.com',
            'Content-Type' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        ];
        $post_data = [
            "photoId" => str_replace(['video/', '?'], '', $matches[1]),
            "isLongVideo" => false
        ];
        $data = $this->curl('https://v.m.chenzhongtech.com/rest/wd/photo/info?kpn=KUAISHOU&captchaToken=', $headers, $post_data);
        $this->logger->info($data);
        $json = json_decode($data, true);
        if (isset($json['atlas'])) {
            $cdn = $json['atlas']['cdn'][0];
            $list = $json['atlas']['list'];
            $pics = array_map(fn($item) => "https://{$cdn}{$item}", $list);
            return $this->images($pics, '');
        } else {
            $res = [
                'title' => $json['photo']['caption'],
                'cover' => $json['photo']['coverUrls'][key($json['photo']['coverUrls'])]['url'],
                'url' => $json['photo']['mainMvUrls'][key($json['photo']['mainMvUrls'])]['url'],
            ];
            return $this->video($res['title'], $res['cover'], $json['mp4Url']);
        }
    }
}