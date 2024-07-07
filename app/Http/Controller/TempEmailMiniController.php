<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Middleware\Auth\MiniAuthMiddleware;
use App\Model\Bullet;
use DateTime;
use EasyWeChat\Kernel\Exceptions\HttpException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\Redis\Redis;
use Qiniu\Auth;


#[Controller(prefix: "api/mini/temp/email")]
#[Middleware(MiniAuthMiddleware::class)]
//#[RateLimit(limitCallback: [TempEmailMiniController::class, "limitCallback"])]
class TempEmailMiniController extends BaseController
{

    const  TOKEN = "eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCJ9.eyJhIjoicnciLCJpYXQiOjE3MTExNzY4NTcsImlkIjoiYzgzZmQyYjgtNjQ3Yi00MDMwLTkyMWYtOTU2ZmQxMWM2MGNkIn0.j5vx0pBUYkyvfQqndYhPpAYDThoJrH_Y6MxfLZRunnXlEY57H5DA8-JYD1sHHIn8Ah9NvpHRCnEqJWtzPoYBBg";
    const BASEURL = "https://femail-shawn.turso.io/v2/pipeline";

    #[Inject]
    protected Redis $cache;

    #[Inject]
    public ClientFactory $clientFactory;

    private array $config = [
        'app_id' => 'wx8af8c68b292996dc',
        'secret' => 'e54e042ee36c2f3e9cc641863ce9eceb',
        'token' => 'wowyou',
        'aes_key' => '',

        /**
         * 接口请求相关配置，超时时间等，具体可用参数请参考：
         * https://github.com/symfony/symfony/blob/5.3/src/Symfony/Contracts/HttpClient/HttpClientInterface.php
         */
        'http' => [
            'throw' => true, // 状态码非 200、300 时是否抛出异常，默认为开启
            'timeout' => 5.0,
            'retry' => true, // 使用默认重试配置
        ],
    ];

    #[PostMapping(path: "list")]
//    #[RateLimit(create: 1, capacity: 3,)]
    public function list()
    {
        $keyword = $this->request->post("email", "");

        $list = [];
        if (empty($keyword)) {
            return $this->success($list);
        }
        $todayStartTimestamp = strtotime("today");
        $stmt = "select id,`from`,subject,date from emails where message_to='{$keyword}' and created_at >={$todayStartTimestamp} order by created_at desc";
        $requestData['requests'][] = ['type' => 'execute', 'stmt' => ['sql' => $stmt]];
        $requestData['requests'][] = ['type' => "close"];
        $rs = $this->makeRequest('POST', self::BASEURL, self::TOKEN, $requestData);
        if (isset($rs['results'][0]['response']['result'])) {
            $rows = $rs['results'][0]['response']['result']['rows'];
            $cols = $rs['results'][0]['response']['result']['cols'];
            $colList = array_column($cols, 'name');

            foreach ($rows as $row) {
                $rowList = array_column($row, 'value');
                $data = array_combine($colList, $rowList);
                if (!empty($data['from'])) {
                    $data['from'] = json_decode($data['from']);
                }
                $dateTime = new DateTime($data['date']);
                $data['date'] = $dateTime->format('H:i');

                $list[] = $data;
            }

            return $this->success($list);
        }
    }

    #[PostMapping(path: "detail")]
    public function detail()
    {
        $id = $this->request->post("id", "");
        $list = [];
        if (empty($id)) {
            return $this->success($list);
        }
        $stmt = "select `from`,message_from,message_to,subject,html,text,date from emails where id = '{$id}'";
        $requestData['requests'][] = ['type' => 'execute', 'stmt' => ['sql' => $stmt]];
        $requestData['requests'][] = ['type' => "close"];
        $rs = $this->makeRequest('POST', self::BASEURL, self::TOKEN, $requestData);
        if (isset($rs['results'][0]['response']['result'])) {
            $rows = $rs['results'][0]['response']['result']['rows'];
            $cols = $rs['results'][0]['response']['result']['cols'];
            $colList = array_column($cols, 'name');
            foreach ($rows as $row) {
                $data = [];
                foreach ($colList as $index => $col) {
                    if (isset($row[$index])) {
                        if ('from' == $col && !empty($row[$index]['value'])) {
                            $data['from'] = json_decode($row[$index]['value']);
                        } elseif ('date' == $col && !empty($row[$index]['value'])) {
                            $dateTime = new DateTime($row[$index]['value']);
                            $data['date'] = $dateTime->format('H:i');
                        } else {
                            $data[$col] = $row[$index]['value'] ?? "";
                        }
                    }
                }
                $list = $data;
            }

            return $this->success($list);
        }
    }

    #[PostMapping(path: "record")]
    public function record()
    {
        $model = $this->request->post("model", "");
        $system = $this->request->post("system", "");
        $text = $this->request->post("text", "");
        $wxVersion = $this->request->post("wxversion", "");
        $sdkVersion = $this->request->post("sdkversion", "");
        $type = $this->request->post("type", 1);
        $bullet = new Bullet();
        $bullet->model = $model;
        $bullet->system = $system;
        $bullet->text = $text;
        $bullet->wx_version = $wxVersion;
        $bullet->sdk_version = $sdkVersion;
        $bullet->type = $type;
        $bullet->save();

    }

    #[GetMapping(path: "show")]
    public function show()
    {
//        return $this->fail();
        return $this->success();

    }

    #[GetMapping(path: "ads")]
    public function ads()
    {
        return $this->success(['downloadAd'=>0]);

    }

    #[PostMapping(path: "code2Session")]
    public function code2Session()
    {
        $code = $this->request->post("code", "");
        $response = $this->doCode2Session($code);

        return $this->success($response);

    }

    #[PostMapping(path: "check")]
    public function check()
    {
        return $this->success([$this->getAccessToken()]);

    }

    #[GetMapping(path: "qntoken")]
    public function qntoken()
    {
        $ak = 'Gw_qPq0NzE8gC_uDR7swbrJ0x-Y2re7zj_3FNZVJ';
        $sk = '2w3NVofTJYnb1VZ6uo6e9y8OvJ41T1LfQRl3NR05';
        // 初始化Auth状态
        $auth = new Auth($ak, $sk);
        $expires = 3600;
        $policy = null;
        $upToken = $auth->uploadToken('minproject', null, $expires, $policy, true);
        $rs = ['uptoken' => $upToken];
        return json_encode($rs);
    }

    public static function limitCallback(float $seconds, ProceedingJoinPoint $proceedingJoinPoint)
    {
        // $seconds 下次生成Token 的间隔, 单位为秒
        // $proceedingJoinPoint 此次请求执行的切入点
        // 可以通过调用 `$proceedingJoinPoint->process()` 继续完成执行，或者自行处理
        return $proceedingJoinPoint->process();
    }


    //https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/sec-center/sec-check/mediaCheckAsync.html
    private function doCheck(string $mediaUrl,string $openid)
    {
        try {
            $client = $this->clientFactory->create([
                'timeout' => 10,
                'verify' => false,
                'allow_redirects' => true,
                'base_uri' => 'https://api.weixin.qq.com/',
            ]);
            $uri = 'wxa/media_check_async?access_token=' . $this->getAccessToken();
            $response = $client->request(
                'POST',
                $uri,
                [
                    'media_url' => $mediaUrl,
                    'media_type' => 2,
                    'version' => 2,
                    'scene' => 2,
                    'openid' => $openid,

                ]
            )->getBody()->getContents();
            $response = json_decode($response, true);


            return $response;
        } catch (RequestException $e) {
            $this->logger->info("getAccessToken curl RequestException=" . $e->getMessage());
            return null;
        } catch (GuzzleException $e) {
            $this->logger->info("getAccessToken curl GuzzleException=" . $e->getMessage());
            return null;
        }
    }

    private function getAccessToken()
    {
        $token = $this->cache->get('access_token');

        if ((bool)$token && is_string($token)) {
            return $token;
        }
        try {
            $client = $this->clientFactory->create([
                'timeout' => 10,
                'verify' => false,
                'allow_redirects' => true,
                'base_uri' => 'https://api.weixin.qq.com/',
            ]);

            $response = $client->request(
                'GET',
                'cgi-bin/token',
                [
                    'query' => [
                        'grant_type' => 'client_credential',
                        'appid' => $this->config['app_id'],
                        'secret' => $this->config['secret'],
                    ],
                ]
            )->getBody()->getContents();
            $response = json_decode($response, true);
            if (empty($response['access_token'])) {
                throw new HttpException('Failed to get access_token: ' . json_encode($response, JSON_UNESCAPED_UNICODE));
            }
            $this->cache->set('access_token', $response['access_token'], 7200);
            return $response['access_token'];
        } catch (RequestException $e) {
            $this->logger->info("getAccessToken curl RequestException=" . $e->getMessage());
            return null;
        } catch (GuzzleException $e) {
            $this->logger->info("getAccessToken curl GuzzleException=" . $e->getMessage());
            return null;
        }

    }

    private function doCode2Session(string $code): array|null
    {
        try {
            $client = $this->clientFactory->create([
                'timeout' => 10,
                'verify' => false,
                'allow_redirects' => true,
                'base_uri' => 'https://api.weixin.qq.com/',
            ]);

            $response = $client->request('GET', '/sns/jscode2session', [
                'query' => [
                    'appid' => $this->config['app_id'],
                    'secret' => $this->config['secret'],
                    'js_code' => $code,
                    'grant_type' => 'authorization_code',
                ],
            ])->getBody()->getContents();
            $response = json_decode($response, true);
            if (empty($response['openid'])) {
                throw new HttpException('code2Session error: ' . json_encode($response, JSON_UNESCAPED_UNICODE));
            }

            return $response;
        } catch (RequestException $e) {
            $this->logger->info("code2Session curl RequestException=" . $e->getMessage());
            return null;
        } catch (GuzzleException $e) {
            $this->logger->info("code2Session curl GuzzleException=" . $e->getMessage());
            return null;
        }

    }

    private function makeRequest(string $method, string $url, string $authToken, array $data = []): mixed
    {
        $headers = [
            'Authorization: Bearer ' . $authToken,
            'Content-Type: application/json',
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error: " . $err;
        } else {
            return json_decode($response, true);
        }
    }
}
