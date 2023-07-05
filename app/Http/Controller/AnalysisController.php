<?php
declare(strict_types=1);

namespace App\Http\Controller;

use App\Contract\AnalysisInterface;
use App\Http\Service\DouyinService;
use App\Http\Service\KuaishouService;
use App\Http\Service\QueueService;
use App\Http\Service\WeiboService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;

#[Controller(prefix: 'api/analysis')]
class AnalysisController extends BaseController
{

    #[Inject]
    private AnalysisInterface $analysisService;

    #[Inject]
    private QueueService $service;

    #[Inject]
    private DouyinService $douyinService;

    #[Inject]
    private KuaishouService $kuaishouService;

    #[Inject]
    private WeiboService $weiboService;

    #[PostMapping(path: 'media')]
    public function getMedia()
    {
        $url = $this->request->post('url');
        $vid = $this->request->post('vid');
        $basai_id = $this->request->post('basai_id');
        $api = null;//new \analysis\Video();
        if (strpos($url, 'pipix')) {
            $arr = $api->pipixia($url);
        } elseif (strpos($url, 'douyin')) {
            $arr = $this->douyinService->analysis($url);
        } elseif (strpos($url, 'weibo.com') || strpos($url, 'm.weibo.cn')) {
            $arr = $this->weiboService->analysis($url);
        } elseif (strpos($url, 'kuaishou')) {
            $arr = $this->kuaishouService->analysis($url);
        } elseif (strpos($url, 'bilibili.com') || strpos($url, 'b23.tv')) {
//            $arr = $this->analysisService->bilibili($url);
            //异步处理
            $this->service->youGetPush(['uid' => $this->request->header('auth'), 'url' => $url]);
        } elseif (strpos($url, 'xhslink') !== false || strpos($url, 'xiaohongshu.com') !== false) {
            $arr = $this->analysisService->xhs($url);
        } elseif (strpos($url, 'huoshan')) {
            $arr = $api->huoshan($url);
        } elseif (strpos($url, 'h5.weishi') || strpos($url, 'isee.weishi')) {
            $wb = new Ws();
            $arr = $wb->analyse($url);
        } elseif (strpos($url, 'instagram.com')) {
            $wb = new Ins();
            $arr = $wb->analyse($url);
        } elseif (strpos($url, 'oasis.weibo')) {
            $arr = $api->lvzhou($url);
        } elseif (strpos($url, 'zuiyou')) {
            $arr = $api->zuiyou($url);
        } elseif (strpos($url, 'xiaochuankeji')) {
            $arr = $api->zuiyou($url);
        } elseif (strpos($url, 'quanmin')) {
            if (empty($vid)) {
                $arr = $api->quanmin($url);
            } else {
                $arr = $api->quanmin($vid);
            }
        } elseif (strpos($url, 'moviebase')) {
            $arr = $api->basai($basai_id);
        } elseif (strpos($url, 'hanyuhl')) {
            $arr = $api->before($url);
        } elseif (strpos($url, 'eyepetizer')) {
            $arr = $api->kaiyan($url);
        } elseif (strpos($url, 'immomo')) {
            $arr = $api->momo($url);
        } elseif (strpos($url, 'vuevideo')) {
            $arr = $api->vuevlog($url);
        } elseif (strpos($url, 'xiaokaxiu')) {
            $arr = $api->xiaokaxiu($url);
        } elseif (strpos($url, 'ippzone') || strpos($url, 'pipigx')) {
            $arr = $api->pipigaoxiao($url);
        } elseif (strpos($url, 'qq.com')) {
            $arr = $api->quanminkge($url);
        } elseif (strpos($url, 'ixigua.com')) {
            $arr = $api->xigua($url);
        } elseif (strpos($url, 'doupai')) {
            $arr = $api->doupai($url);
        } elseif (strpos($url, '6.cn')) {
            $arr = $api->sixroom($url);
        } elseif (strpos($url, 'huya.com/play/')) {
            $arr = $api->huya($url);
        } elseif (strpos($url, 'pearvideo.com')) {
            $arr = $api->pear($url);
        } elseif (strpos($url, 'xinpianchang.com')) {
            $arr = $api->xinpianchang($url);
        } elseif (strpos($url, 'acfun.cn')) {
            $arr = $api->acfan($url);
        } elseif (strpos($url, 'meipai.com')) {
            $arr = $api->meipai($url);
        } else {
            return $this->error('不支持您输入的链接');
        }
        if (!empty($arr)) {
            return $this->success($arr);
        } else {
            return $this->fail();
        }
    }
}