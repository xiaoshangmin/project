<?php

namespace App\Http\Service;

use App\Contract\AnalysisInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;

class AnalysisService implements AnalysisInterface
{

    #[Inject]
    private ClientFactory $clientFactory;

    #[Inject]
    private StdoutLoggerInterface $logger;

    const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36';

    public function xhs(string $url): array
    {
        $ua = self::UA;
        $id = md5("{$ua}hasaki");
        $data = [
            'id' => $id,
            'sign' => $ua,
        ];

        $client = $this->clientFactory->create([
            'timeout' => 5,
            'verify' => false
        ]);
        $response = $client->post('https://www.xiaohongshu.com/fe_api/burdock/v2/shield/registerCanvas?p=cc', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($data, JSON_UNESCAPED_UNICODE)
        ]);
        $canvas = $response->getBody()->getContents();
//        print_r($canvas);
        $canvas = json_decode($canvas, true);
        if (strpos($url, 'xiaohongshu.com') === false) {
            $url = get_headers($url, 1)["Location"];
        }
//        print_r($url);
        $cookie = [
            "xhsTrackerId" => "54158ddf-d919-4ad4-ca6d-851988db95be",
            "timestamp2" => $canvas['data']['canvas'],
            'timestamp2.sig' => 'TRnLxBFN9GTAm9HOZaz1Oj1e_9W3zK67Oa_A1dGHy-c',
            'extra_exp_ids' => 'puarranchiv2_clt1,commentshow_exp1,gif_exp1,ques_clt1',
        ];
        $text = $this->xhsCurl($url, $cookie, 'xiaohongshu.com');

        $text = $text->getContents();
//        $this->logger->info($text);

        preg_match('/__INITIAL_SSR_STATE__([^<]+)</', $text, $jsonStr);
        $jsonStr = str_replace('undefined', 'null', trim($jsonStr[1], '='));
//         print_r($jsonStr);exit;
        $json = json_decode($jsonStr, true);
        $noteInfo = $json['NoteView']['noteInfo'];
        $noteType = $json['NoteView']['noteType'];
        if ('video' == $noteType) {
//            print_r($noteInfo);exit;
            $cover = 'https://ci.xiaohongshu.com/' . $noteInfo['cover']['fileId'];//['traceId'];
            return $this->video($noteInfo['title'], $cover, $noteInfo['video']['url']);
        } else {
            $pics = [];
            foreach ($noteInfo['imageList'] as $image) {
                $traceId = $image['fileId'];
                $pics[] = 'https://ci.xiaohongshu.com/' . $traceId;
            }
            return $this->images($pics, $noteInfo['title']);
        }
        return [];
    }


    public function pipixia($url)
    {
        $loc = get_headers($url, true)['Location'];
        preg_match('/item\/(.*)\?/', $loc, $id);
        $arr = json_decode($this->curl('https://is.snssdk.com/bds/cell/detail/?cell_type=1&aid=1319&app_name=super&cell_id=' . $id[1]), true);
        $video_url = $arr['data']['data']['item']['origin_video_download']['url_list'][0]['url'];
        if ($video_url) {
            $arr = [
                'code' => 200,
                'data' => [
                    'author' => $arr['data']['data']['item']['author']['name'],
                    'avatar' => $arr['data']['data']['item']['author']['avatar']['download_list'][0]['url'],
                    'time' => $arr['data']['data']['display_time'],
                    'title' => $arr['data']['data']['item']['content'],
                    'cover' => $arr['data']['data']['item']['cover']['url_list'][0]['url'],
                    'url' => $video_url
                ]
            ];
            return $arr;
        }
    }

    public function douyin($url): array
    {
        if (strpos($url, 'iesdouyin')) {
            preg_match('/\/(\d+)\//', $url, $id);
        } else {
            $header = get_headers($url, true);
            if (is_string($header['Location'])) {
                $loc = $header['Location'];
            } else {
                if (false !== strpos($header['Location'][1], 'video')) {
                    $loc = $header['Location'][1];
                } else {
                    $loc = $header['Location'][0];
                }
            }
            preg_match('/video\/(.*)\?/', $loc, $id);
        }
        $gourl = 'https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids=' . rtrim($id[1], '/');
        $jsonStr = $this->curl($gourl);
        $arr = json_decode($jsonStr, true);
        $item = $arr['item_list'][0];

        if ($item['aweme_type'] == 2) {
            $imagesList = $item["images"];
            $pics = [];
            foreach ($imagesList as $url) {
                $pics[] = $url['url_list'][0];
            }
            return $this->images($pics, $item['share_info']['share_title']);
        } else {
            $video_url = str_replace('playwm', 'play', $item["video"]["play_addr"]["url_list"][0]);
            return $this->video($item['share_info']['share_title'], $item['video']['origin_cover']['url_list'][0], $video_url);
        }
    }

    public function huoshan($url)
    {
        $loc = get_headers($url, true)['location'];
        preg_match('/item_id=(.*)&tag/', $loc, $id);
        $arr = json_decode($this->curl('https://share.huoshan.com/api/item/info?item_id=' . $id[1]), true);
        $url = $arr['data']['item_info']['url'];
        preg_match('/video_id=(.*)&line/', $url, $id);
        if ($id) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'cover' => $arr["data"]["item_info"]["cover"],
                    'url' => 'https://api-hl.huoshan.com/hotsoon/item/video/_playback/?video_id=' . $id[1]
                ]
            ];
            return $arr;
        }
    }

    public function weishi($url)
    {
        preg_match('/feed\/(.*)\b/', $url, $id);
        if (strpos($url, 'h5.weishi') != false) {
            $arr = json_decode($this->curl('https://h5.weishi.qq.com/webapp/json/weishi/WSH5GetPlayPage?feedid=' . $id[1]), true);
        } else {
            $arr = json_decode(
                $this->curl('https://h5.weishi.qq.com/webapp/json/weishi/WSH5GetPlayPage?feedid=' . $url),
                true
            );
        }
        $video_url = $arr['data']['feeds'][0]['video_url'];
        if ($video_url) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'author' => $arr['data']['feeds'][0]['poster']['nick'],
                    'avatar' => $arr['data']['feeds'][0]['poster']['avatar'],
                    'time' => $arr['data']['feeds'][0]['poster']['createtime'],
                    'title' => $arr['data']['feeds'][0]['feed_desc_withat'],
                    'cover' => $arr['data']['feeds'][0]['images'][0]['url'],
                    'url' => $video_url
                ]
            ];
            return $arr;
        }
    }

    public function weibo(string $url): array
    {
        //手机端
        if (strpos($url, 'm.weibo.cn') != false) {
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
                foreach ($picIds as $ids) {
                    if ($json['isLongText']) {
                        //长文本
                    }
                    $pics[] = 'https://lz.sinaimg.cn/oslarge/' . $ids . '.jpg';
                }
                return $this->images($pics, $json['text_raw']);
            }
        }
        return [];
    }

    public function lvzhou($url)
    {
        $text = $this->curl($url);
        preg_match('/<div class=\"text\">(.*)<\/div>/', $text, $video_title);
        preg_match('/<div style=\"background-image:url\((.*)\)/', $text, $video_cover);
        preg_match('/<video src=\"([^\"]*)\"/', $text, $video_url);
        preg_match('/<div class=\"nickname\">(.*)<\/div>/', $text, $video_author);
        preg_match('/<a class=\"avatar\"><img src=\"(.*)\?/', $text, $video_author_img);
        preg_match('/<div class=\"like-count\">(.*)次点赞<\/div>/', $text, $video_like);
        if ($video_url[1]) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'author' => $video_author[1],
                    'avatar' => str_replace('1080.180', '1080.680', $video_author_img)[1],
                    'like' => $video_like[1],
                    'title' => $video_title[1],
                    'cover' => $video_cover[1],
                    'url' => $video_url[1],
                ]
            ];
            return $arr;
        }
    }

    public function zuiyou($url)
    {
        $text = $this->curl($url);
        preg_match('/fullscreen=\"false\" src=\"(.*?)\"/', $text, $video);
        preg_match('/:<\/span><h1>(.*?)<\/h1><\/div><\/div><div class=\"ImageBoxII\">/', $text, $video_title);
        preg_match('/poster=\"(.*?)\">/', $text, $video_cover);
        $video_url = str_replace('\\', '/', str_replace('u002F', '', $video[1]));
        preg_match('/<span class=\"SharePostCard__name\">(.*?)<\/span>/', $text, $video_author);
        if ($video_url) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'author' => $video_author[1],
                    'title' => $video_title[1],
                    'cover' => $video_cover[1],
                    'url' => $video_url,
                ]
            ];
            return $arr;
        }
    }

    public function bbq($url)
    {
        preg_match('/id=(.*)\b/', $url, $id);
        $arr = json_decode($this->curl('https://bbq.bilibili.com/bbq/app-bbq/sv/detail?svid=' . $id[1]), true);
        $video_url = $arr['data']['play']['file_info'][0]['url'];
        if ($video_url) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'author' => $arr['data']['user_info']['uname'],
                    'avatar' => $arr['data']['user_info']['face'],
                    'time' => $arr['data']['pubtime'],
                    'like' => $arr['data']['like'],
                    'title' => $arr['data']['title'],
                    'cover' => $arr['data']['cover_url'],
                    'url' => $video_url,
                ]
            ];
            return $arr;
        }
    }

    public function bilibili($url): array
    {
        $text = $this->curl($url);
        preg_match('/<script>window.__INITIAL_STATE__=([^;]+)/', $text, $response);
        $response = json_decode($response[1], true);
        $aid = $response['videoData']['aid'];
        $cid = $response['videoData']['cid'];
        $cover = $response['videoData']['pic'];
        $title = $response['videoData']['title'];
        $jsonStr = $this->curl("https://api.bilibili.com/x/player/playurl?avid={$aid}&cid={$cid}&qn=1&type=&otype=json&platform=html5&high_quality=1");
        $json = json_decode($jsonStr, true);
        return $this->video($title, $cover, $json['data']['durl'][0]['url']);
    }

    //快手
    function decodeUnicode($str)
    {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', function ($matches) {
            return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");
        }, $str);
    }

    public function kuaishou($url): array
    {
        $locs = get_headers($url, true)['Location'];//[1];
        if (is_array($locs)) {
            $locs = $locs[1];
        } elseif (is_string($locs)) {

        }
        preg_match('/photoId=(.*?)\&/', $locs, $matches);
        $headers = array('Cookie: did=web_c816580c352e5333790f5e2e7da9b151; didv=1655992503000;',
            'Referer: ' . $locs, 'Content-Type: application/json');
        $post_data = '{"photoId": "' . str_replace(['video/', '?'], '', $matches[1]) . '","isLongVideo": false}';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://v.m.chenzhongtech.com/rest/wd/photo/info');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_NOBODY, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        $data = curl_exec($curl);
        curl_close($curl);
        $json = json_decode($data, true);
        if (isset($json['atlas'])) {
            $cdn = $json['atlas']['cdn'][0];
            $list = $json['atlas']['list'];
            $pics = array_map(fn($item) => "https://{$cdn}{$item}", $list);
            return $this->images($pics, '');
        } else {
            $res = [
                'avatar' => $json['photo']['headUrl'],
                'author' => $json['photo']['userName'],
                'time' => $json['photo']['timestamp'],
                'title' => $json['photo']['caption'],
                'cover' => $json['photo']['coverUrls'][key($json['photo']['coverUrls'])]['url'],
                'url' => $json['photo']['mainMvUrls'][key($json['photo']['mainMvUrls'])]['url'],
            ];
            return $this->video($res['title'], $res['cover'], $res['url']);
        }
    }

    public function quanmin($id)
    {
        if (strpos($id, 'quanmin.baidu.com/v/')) {
            preg_match('/v\/(.*?)\?/', $id, $vid);
            $id = $vid[1];
        }
        $arr = json_decode($this->curl('https://quanmin.hao222.com/wise/growth/api/sv/immerse?source=share-h5&pd=qm_share_mvideo&vid=' . $id . '&_format=json'), true);
        if ($arr) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'author' => $arr["data"]["author"]['name'],
                    'avatar' => $arr["data"]["author"]["icon"],
                    "title" => $arr["data"]["meta"]["title"],
                    "cover" => $arr["data"]["meta"]["image"],
                    "url" => $arr["data"]["meta"]["video_info"]["clarityUrl"][0]['url']
                ]
            ];
            return $arr;
        }
    }

    public function basai($id)
    {
        $arr = json_decode($this->curl('http://www.moviebase.cn/uread/api/m/video/' . $id . '?actionkey=300303'), true);
        $video_url = $arr[0]['data']['videoUrl'];
        if ($video_url) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'time' => $arr[0]['data']['createDate'],
                    'title' => $arr[0]['data']['title'],
                    "cover" => $arr[0]['data']['coverUrl'],
                    "url" => $video_url
                ]
            ];
            return $arr;
        }
    }

    public function before($url)
    {
        preg_match('/detail\/(.*)\?/', $url, $id);
        $arr = json_decode($this->curl('https://hlg.xiatou.com/h5/feed/detail?id=' . $id[1]), true);
        $video_url = $arr['data'][0]['mediaInfoList'][0]['videoInfo']['url'];
        if ($video_url) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'author' => $arr['data'][0]['author']['nickName'],
                    'avatar' => $arr['data'][0]['author']['avatar']['url'],
                    'like' => $arr['data'][0]['diggCount'],
                    'time' => $arr['recTimeStamp'],
                    'title' => $arr['data'][0]['title'],
                    "cover" => $arr['data'][0]['staticCover'][0]['url'],
                    "url" => $video_url
                ]
            ];
            return $arr;
        }
    }

    public function kaiyan($url)
    {
        preg_match('/\?vid=(.*)\b/', $url, $id);
        $arr = json_decode($this->curl('https://baobab.kaiyanapp.com/api/v1/video/' . $id[1] . '?f=web'), true);
        $video = 'https://baobab.kaiyanapp.com/api/v1/playUrl?vid=' . $id[1] . '&resourceType=video&editionType=default&source=aliyun&playUrlType=url_oss&ptl=true';
        $video_url = get_headers($video, true)["Location"];
        if ($video_url) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'title' => $arr['title'],
                    "cover" => $arr['coverForFeed'],
                    "url" => $video_url
                ]
            ];
            return $arr;
        }
    }

    public function momo($url)
    {
        preg_match('/new-share-v2\/(.*)\.html/', $url, $id);
        if (count($id) < 1) {
            preg_match('/momentids=(\w+)/', $url, $id);
        }
        $post_data = ["feedids" => $id[1],];
        $arr = json_decode($this->post_curl('https://m.immomo.com/inc/microvideo/share/profiles', $post_data), true);
        $video_url = $arr['data']['list'][0]['video']['video_url'];
        if ($video_url) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'author' => $arr['data']['list'][0]['user']['name'],
                    'avatar' => $arr['data']['list'][0]['user']['img'],
                    'uid' => $arr['data']['list'][0]['user']['momoid'],
                    'sex' => $arr['data']['list'][0]['user']['sex'],
                    'age' => $arr['data']['list'][0]['user']['age'],
                    'city' => $arr['data']['list'][0]['video']['city'],
                    'like' => $arr['data']['list'][0]['video']['like_cnt'],
                    'title' => $arr['data']['list'][0]['content'],
                    "cover" => $arr['data']['list'][0]['video']['cover']['l'],
                    "url" => $video_url
                ]
            ];
            return $arr;
        }
    }

    public function vuevlog($url)
    {
        $text = $this->curl($url);
        preg_match('/<title>(.*?)<\/title>/', $text, $video_title);
        preg_match('/<meta name=\"twitter:image\" content=\"(.*?)\">/', $text, $video_cover);
        preg_match('/<meta property=\"og:video:url\" content=\"(.*?)\">/', $text, $video_url);
        preg_match('/<div class=\"infoItem name\">(.*?)<\/div>/', $text, $video_author);
        preg_match('/<div class="avatarContainer"><img src="(.*?)\"/', $text, $video_avatar);
        preg_match('/<div class=\"likeTitle\">(.*) friends/', $text, $video_like);
        $video_url = $video_url[1];
        if ($video_url) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'author' => $video_author[1],
                    'avatar' => $video_avatar[1],
                    'like' => $video_like[1],
                    'title' => $video_title[1],
                    "cover" => $video_cover[1],
                    "url" => $video_url,
                ]
            ];
            return $arr;
        }
    }

    public function xiaokaxiu($url)
    {
        preg_match('/id=(.*)\b/', $url, $id);
        $sign = md5('S14OnTD#Qvdv3L=3vm&time=' . time());
        $arr = json_decode($this->curl('https://appapi.xiaokaxiu.com/api/v1/web/share/video/' . $id[1] . '?time=' . time(), ["x-sign : $sign"]), true);
        if ($arr['code'] != -2002) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'author' => $arr['data']['video']['user']['nickname'],
                    'avatar' => $arr['data']['video']['user']['avatar'],
                    'like' => $arr['data']['video']['likedCount'],
                    'time' => $arr['data']['video']['createdAt'],
                    'title' => $arr['data']['video']['title'],
                    'cover' => $arr['data']['video']['cover'],
                    'url' => $arr['data']['video']['url'][0]
                ]
            ];
            return $arr;
        }
    }

    public function pipigaoxiao($url)
    {
        preg_match('/post\/(.*)/', $url, $id);
        $arr = json_decode($this->pipigaoxiao_curl($id[1]), true);
        $id = $arr["data"]["post"]["imgs"][0]["id"];
        if ($id) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'title' => $arr["data"]["post"]["content"],
                    'cover' => 'https://file.ippzone.com/img/view/id/' . $arr["data"]["post"]["imgs"][0]["id"],
                    'url' => $arr["data"]["post"]["videos"]["$id"]["url"]
                ]
            ];
            return $arr;
        }
    }

    public function quanminkge($url)
    {
        preg_match('/\?s=(.*)/', $url, $id);
        $text = $this->curl('https://kg.qq.com/node/play?s=' . $id[1]);
        preg_match('/<title>(.*?)-(.*?)-/', $text, $video_title);
        preg_match('/cover\":\"(.*?)\"/', $text, $video_cover);
        preg_match('/playurl_video\":\"(.*?)\"/', $text, $video_url);
        preg_match('/{\"activity_id\":0\,\"avatar\":\"(.*?)\"/', $text, $video_avatar);
        preg_match('/<p class=\"singer_more__time\">(.*?)<\/p>/', $text, $video_time);
        if ($video_url[1]) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'title' => $video_title[2],
                    'cover' => $video_cover[1],
                    'url' => $video_url[1],
                    'author' => $video_title[1],
                    'avatar' => $video_avatar[1],
                    'time' => $video_time[1],
                ]
            ];
            return $arr;
        }
    }

    public function xigua($url)
    {
        if (strpos($url, 'v.ixigua.com') != false) {
            $loc = get_headers($url, true)['Location'];
            preg_match('/video\/(.*)\//', $loc, $id);
            $url = 'https://www.ixigua.com/' . $id[1];
        }
        $headers = [
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Safari/537.36 ",
            "cookie:MONITOR_WEB_ID=7892c49b-296e-4499-8704-e47c1b150c18; ixigua-a-s=1; ttcid=af99669b6304453480454f150701d5c226; BD_REF=1; __ac_nonce=060d88ff000a75e8d17eb; __ac_signature=_02B4Z6wo00f01kX9ZpgAAIDAKIBBQUIPYT5F2WIAAPG2ad; ttwid=1%7CcIsVF_3vqSIk4XErhPB0H2VaTxT0tdsTMRbMjrJOPN8%7C1624806049%7C08ce7dd6f7d20506a41ba0a331ef96a6505d96731e6ad9f6c8c709f53f227ab1"
        ];
        $text = $this->curl($url, $headers);
        preg_match('/<script id=\"SSR_HYDRATED_DATA\">window._SSR_HYDRATED_DATA=(.*?)<\/script>/', $text, $jsondata);
        $data = json_decode(str_replace('undefined', 'null', $jsondata[1]), 1);
        $result = $data["anyVideo"]["gidInformation"]["packerData"]["video"];
        $video = $result["videoResource"]["dash"]["dynamic_video"]["dynamic_video_list"][2]["main_url"];
        preg_match('/(.*?)=&vr=/', base64_decode($video), $video_url);
        $music = $result["videoResource"]["dash"]["dynamic_video"]["dynamic_audio_list"][0]["main_url"];
        preg_match('/(.*?)=&vr=/', base64_decode($music), $music_url);
        $video_author = $result['user_info']['name'];
        $video_avatar = str_replace('300x300.image', '300x300.jpg', $result['user_info']['avatar_url']);
        $video_cover = $data["anyVideo"]["gidInformation"]["packerData"]["pSeries"]["firstVideo"]["middle_image"]["url"];
        $video_title = $result["title"];
        if ($url) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'author' => $video_author,
                    'avatar' => $video_avatar,
                    'like' => $result['video_like_count'],
                    'time' => $result['video_publish_time'],
                    'title' => $video_title,
                    'cover' => $video_cover,
                    'url' => $video_url[0],
                    'music' => [
                        'url' => $music_url[0]
                    ]
                ]
            ];
            return $arr;
        }
    }

    public function doupai($url)
    {
        preg_match("/topic\/(.*?).html/", $url, $d_url);
        $vid = $d_url[1];
        $base_url = "https://v2.doupai.cc/topic/" . $vid . ".json";
        $data = json_decode($this->curl($base_url), true);
        $url = $data["data"]["videoUrl"];
        $title = $data["data"]["name"];
        $cover = $data["data"]["imageUrl"];
        $time = $data['data']['createdAt'];
        $author = $data['data']['userId'];
        if ($url) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    "title" => $title,
                    "cover" => $cover,
                    'time' => $time,
                    'author' => $author['name'],
                    'avatar' => $author['avatar'],
                    "url" => $url
                ]
            ];
            return $arr;
        }
    }

    public function sixroom($url)
    {
        preg_match("/http[s]?:\/\/(?:[a-zA-Z]|[0-9]|[$-_@.&+]|[!*\(\),]|(?:%[0-9a-fA-F][0-9a-fA-F]))+/", $url, $deal_url);
        $headers = [
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.125 Safari/537.36',
            'x-requested-with' => 'XMLHttpRequest'
        ];
        $rows = $this->curl($deal_url[0], $headers);
        preg_match('/tid: \'(\w+)\',/', $rows, $tid);
        $base_url = 'https://v.6.cn/message/message_home_get_one.php';
        $content = $this->curl($base_url . '?tid=' . $tid[1], $headers);
        $content = json_decode($content, 1);
        if ($content) {
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'title' => $content["content"]["content"][0]["content"]['title'],
                    'cover' => $content["content"]["content"][0]["content"]['url'],
                    'url' => $content["content"]["content"][0]["content"]['playurl'],
                    'author' => $content["content"]["content"][0]['alias'],
                    'avatar' => $content["content"]["content"][0]['userpic'],
                ]
            ];
            return $arr;
        }
    }

    public function huya($url)
    {
        preg_match('/\/(\d+).html/', $url, $vid);
        $api = 'https://liveapi.huya.com/moment/getMomentContent';
        $response = $this->curl(
            $api . '?videoId=' . $vid[1],
            [
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.102 Safari/537.36',
                'referer' => 'https://v.huya.com/',
            ]
        );
        $content = json_decode($response, 1);
        if ($content['status'] === 200) {
            $url = $content["data"]["moment"]["videoInfo"]["definitions"][0]["url"];
            $cover = $content["data"]["moment"]["videoInfo"]["videoCover"];
            $title = $content["data"]["moment"]["videoInfo"]["videoTitle"];
            $avatarUrl = $content["data"]["moment"]["videoInfo"]["avatarUrl"];
            $author = $content["data"]["moment"]["videoInfo"]["nickName"];
            $time = $content["data"]["moment"]["cTime"];
            $like = $content["data"]["moment"]["favorCount"];
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'title' => $title,
                    'cover' => $cover,
                    'url' => $url,
                    'time' => $time,
                    'like' => $like,
                    'author' => $author,
                    'avatar' => $avatarUrl
                ]
            ];
            return $arr;
        }
    }

    public function pear($url)
    {
        $html = $this->curl($url);
        preg_match('/<h1 class=\"video-tt\">(.*?)<\/h1>/', $html, $title);
        preg_match('/_(\d+)/', $url, $feed_id);
        $base_url = sprintf("https://www.pearvideo.com/videoStatus.jsp?contId=%s&mrd=%s", $feed_id[1], time());
        $response = $this->pear_curl($base_url, $url);
        $content = json_decode($response, 1);
        if ($content['resultCode'] == 1) {
            $video = $content["videoInfo"]["videos"]["srcUrl"];
            $cover = $content["videoInfo"]["video_image"];
            $timer = $content["systemTime"];
            $video_url = str_replace($timer, "cont-" . $feed_id[1], $video);
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'title' => $title[1],
                    'cover' => $cover,
                    'url' => $video_url,
                    'time' => $timer,
                ]
            ];
            return $arr;
        }
    }

    public function xinpianchang($url)
    {
        $api_headers = [
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.125 Safari/537.36",
            "referer" => $url,
            "origin" => "https://www.xinpianchang.com",
            "content-type" => "application/json"
        ];
        $home_headers = [
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.125 Safari/537.36",
            "upgrade-insecure-requests" => "1"
        ];
        $html = $this->curl($url, $home_headers);
        preg_match('/var modeServerAppKey = "(.*?)";/', $html, $key);
        preg_match('/var vid = "(.*?)";/', $html, $vid);
        $base_url = sprintf("https://mod-api.xinpianchang.com/mod/api/v2/media/%s?appKey=%s&extend=%s", $vid[1], $key[1], "userInfo,userStatus");
        $response = $this->xinpianchang_curl($base_url, $api_headers, $url);
        $content = json_decode($response, 1);
        if ($content['status'] == 0) {
            $cover = $content['data']["cover"];
            $title = $content['data']["title"];
            $videos = $content['data']["resource"]["progressive"];
            $author = $content['data']['owner']['username'];
            $avatar = $content['data']['owner']['avatar'];
            $video = [];
            foreach ($videos as $v) {
                $video[] = ['profile' => $v['profile'], 'url' => $v['url']];
            }
            $arr = [
                'code' => 200,
                'msg' => '解析成功',
                'data' => [
                    'author' => $author,
                    'avatar' => $avatar,
                    'cover' => $cover,
                    'title' => $title,
                    'url' => $video
                ]
            ];
            return $arr;
        }
    }

    public function acfan($url)
    {
        $headers = [
            'User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1'
        ];
        $html = $this->acfun_curl($url, $headers);
        preg_match('/var videoInfo =\s(.*?);/', $html, $info);
        $videoInfo = json_decode(trim($info[1]), 1);
        preg_match('/var playInfo =\s(.*?);/', $html, $play);
        $playInfo = json_decode(trim($play[1]), 1);
        if ($html) {
            $arr = [
                'code' => 200,
                'title' => $videoInfo['title'],
                'cover' => $videoInfo['cover'],
                'url' => $playInfo['streams'][0]['playUrls'][0],
            ];
            return $arr;
        }
    }

    public function meipai($url)
    {
        $headers = [
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Safari/537.36 ",
        ];
        $html = $this->curl($url, $headers);
        preg_match('/data-video="(.*?)"/', $html, $content);
        preg_match('/<meta name=\"description\" content="(.*?)"/', $html, $title);
        $video_bs64 = $content[1];
        $hex = $this->getHex($video_bs64);
        $dec = $this->getDec($hex['hex_1']);
        $d = $this->sub_str($hex['str_1'], $dec['pre']);
        $p = $this->getPos($d, $dec['tail']);
        $kk = $this->sub_str($d, $p);
        $video = 'https:' . base64_decode($kk);
        if ($video_bs64) {
            $arr = [
                'code' => 200,
                "title" => $title[1],
                "url" => $video
            ];
            return $arr;
        }
    }


    private function acfun_curl($url, $headers = [])
    {
        $header = ['User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1'];
        $con = curl_init((string)$url);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        if (!empty($headers)) {
            curl_setopt($con, CURLOPT_HTTPHEADER, $headers);
        } else {
            curl_setopt($con, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($con, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($con, CURLOPT_TIMEOUT, 5000);
        return curl_exec($con);
    }


    //自动302重定向
    private function curl($url, $headers = [])
    {
        $header = [
            'User-Agent' => self::UA
        ];
        if (!empty($header)) {
            $header = array_merge($headers);
        }
        try {
            $client = $this->clientFactory->create([
                'timeout' => 5,
                'verify' => false
            ]);
            $response = $client->get($url, [
                'headers' => $header,
                'decode_content' => 'gzip,deflate',
                'allow_redirects' => true,
                // 'cookies' => $cookieJar,
            ]);
            return $response->getBody();
        } catch (RequestException $e) {
            echo $e->getMessage();
        }

    }


    private function xhsCurl(string $url, array $cookies = [], string $domain = '')
    {

        $header = [
            'User-Agent' => self::UA
        ];
        try {
            $client = $this->clientFactory->create([
                'timeout' => 5,
                'verify' => false
            ]);
            $options = [
                'headers' => $header,
                'decode_content' => 'gzip,deflate',
                'allow_redirects' => true,
            ];
            if (!empty($cookies)) {
                $cookies = CookieJar::fromArray($cookies, $domain);
                $options['cookies'] = $cookies;
            }
            $response = $client->get($url, $options);
            return $response->getBody();
        } catch (RequestException $e) {
            echo $e->getMessage();
        }

    }


    private function post_curl($url, $post_data)
    {
        $postdata = http_build_query($post_data);
        $options = [
            'http' => [
                'method' => 'POST',
                'content' => $postdata,
            ]
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        return $result;
    }

    private function pipigaoxiao_curl($id)
    {
        $post_data = "{\"pid\":" . $id . ",\"type\":\"post\",\"mid\":null}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://share.ippzone.com/ppapi/share/fetch_content");
        curl_setopt($ch, CURLOPT_REFERER, "http://share.ippzone.com/ppapi/share/fetch_content");
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }


    private function pear_curl($url, $referer)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $referer);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    private function xinpianchang_curl($url, $headers, $referer)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $referer);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    protected function getHex($url)
    {
        $length = strlen($url);
        $hex_1 = substr($url, 0, 4);
        $str_1 = substr($url, 4, $length);
        return [
            'hex_1' => strrev($hex_1),
            'str_1' => $str_1
        ];
    }

    protected function getDec($hex)
    {
        $b = hexdec($hex);
        $length = strlen($b);
        $c = str_split(substr($b, 0, 2));
        $d = str_split(substr($b, 2, $length));
        return [
            'pre' => $c,
            'tail' => $d,
        ];
    }

    protected function sub_str($a, $b)
    {
        $length = strlen($a);
        $k = $b[0];
        $c = substr($a, 0, $k);
        $d = substr($a, $k, $b[1]);
        $temp = str_replace($d, '', substr($a, $k, $length));
        return $c . $temp;
    }

    protected function getPos($a, $b)
    {
        $b[0] = strlen($a) - (int)$b[0] - (int)$b[1];
        return $b;
    }

    public function video(string $title, string $cover, string $url): array
    {
        return ['type' => 'video', 'title' => $title, 'cover' => $cover, 'videoUrl' => $url];
    }

    public function images(array $pics, string $title): array
    {
        return ['type' => 'images', 'pics' => $pics, 'title' => $title,];
    }
}