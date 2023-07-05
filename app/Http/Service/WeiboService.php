<?php

namespace App\Http\Service;

class WeiboService extends Spider
{

    public function analysis(string $url)
    {
        //手机端
        if (strpos($url, 'm.weibo.cn')) {
            preg_match('/(\d+)$/', $url, $id);
            $url = "https://m.weibo.cn/status/" . $id[1];
            $arr = $this->curl($url);
            preg_match_all('/render_data = ([\s\S]+)\[0\]/', $arr, $jsonStr);
            if (!empty($jsonStr[1][0])) {
                $json = json_decode($jsonStr[1][0], true)[0];
                $status = $json['status'];
                if (isset($status['page_info']['media_info'])) {
                    return $this->video($status['status_title'], $status['page_info']['page_pic']['url'], $status['page_info']['media_info']['stream_url_hd']);
                } else if (isset($status['pic_ids'])) {
                    $picIds = $status['pic_ids'];
                    if (empty($status['pic_ids']) && isset($status['retweeted_status']['pic_ids']) && !empty($status['retweeted_status']['pic_ids'])) {
                        $picIds = $status['retweeted_status']['pic_ids'];
                    }
                    $pics = [];
                    foreach ($picIds as $ids) {
                        $pics[] = 'https://lz.sinaimg.cn/oslarge/' . $ids . '.jpg';
                    }
                    return $this->images($pics, $status['status_title']);
                }
            }
        } else {
            //pc端
            preg_match('/\/([\w]+)$/', $url, $id);
            $url = "https://weibo.com/ajax/statuses/show?id=" . $id[1];
            $json = $this->curl($url);
            $json = json_decode($json, true);
            if (isset($json['page_info']['media_info'])) {
                return $this->video($json['page_info']['media_info']['next_title'], $json['page_info']['page_pic'], $json['page_info']['media_info']['stream_url_hd']);
            } else if (isset($json['pic_ids'])) {
                $picIds = $json['pic_ids'];
                if (empty($json['pic_ids']) && isset($json['retweeted_status']['pic_ids']) && !empty($json['retweeted_status']['pic_ids'])) {
                    $picIds = $json['retweeted_status']['pic_ids'];
                }
                $pics = [];
                $picInfos = $json['pic_infos'];
                foreach ($picIds as $ids) {
                    if (isset($picInfos[$ids])){
                        $pics[] = $picInfos[$ids]['original']['url'];
                    }else {
                        $pics[] = 'https://lz.sinaimg.cn/oslarge/' . $ids . '.jpg';
                    }
                }
                return $this->images($pics, $json['text_raw']);
            }
        }
        return [];
    }
}