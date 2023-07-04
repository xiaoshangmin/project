<?php

namespace App\Http\Service;

class KuaishouService extends Spider
{

    public function analysis(string $url)
    {
        $locs = get_headers($url, true)['Location'];
        if (is_array($locs)) {
            $locs = $locs[1];
        } elseif (is_string($locs)) {

        }
        preg_match('/photoId=(.*?)\&/', $locs, $matches);
        $headers = [
            'Cookie' => 'did=web_c816580c352e5333790f5e2e7da9b151; didv=1655992503000;',
            'Referer' => $locs,
            'Content-Type' => 'application/json'
        ];
        $post_data = [
            "photoId" => str_replace(['video/', '?'], '', $matches[1]),
            "isLongVideo" => false
        ];
        $data = $this->curl('https://v.m.chenzhongtech.com/rest/wd/photo/info', $headers, $post_data);
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