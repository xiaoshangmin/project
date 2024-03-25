<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Middleware\Auth\MiniAuthMiddleware;
use DateTime;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\RequestMapping;

#[Controller(prefix: "api/mini/temp/email")]
//#[Middleware(MiniAuthMiddleware::class)]
class TempEmailMiniController extends BaseController
{

    const  TOKEN              = "eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCJ9.eyJhIjoicnciLCJpYXQiOjE3MTExNzY4NTcsImlkIjoiYzgzZmQyYjgtNjQ3Yi00MDMwLTkyMWYtOTU2ZmQxMWM2MGNkIn0.j5vx0pBUYkyvfQqndYhPpAYDThoJrH_Y6MxfLZRunnXlEY57H5DA8-JYD1sHHIn8Ah9NvpHRCnEqJWtzPoYBBg";
const BASEURL = "https://femail-shawn.turso.io/v2/pipeline";

    #[PostMapping(path: "list")]
    public function list()
    {
        $keyword = $this->request->post("email", "");

        $list = [];
        if (empty($keyword)){
            return $this->success($list);
        }

        $stmt = "select id,`from`,subject,date from emails where message_to='{$keyword}' order by created_at desc";
        $requestData['requests'][] = ['type' => 'execute', 'stmt' => ['sql' => $stmt]];
        $requestData['requests'][] = ['type' => "close"];
        $rs = $this->makeRequest('POST',  self::BASEURL,  self::TOKEN  , $requestData);
        if (isset( $rs['results'][0]['response']['result'])) {
            $rows = $rs['results'][0]['response']['result']['rows'];
            $cols = $rs['results'][0]['response']['result']['cols'];
            $colList = array_column($cols,'name');

            foreach ($rows as $row) {
                $rowList = array_column($row,'value');
                $data = array_combine($colList,$rowList);
                if (!empty($data['from'])){
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
        $stmt = "select `from`,message_from,message_to,subject,html,text,date from emails where id = '{$id}'";
        $requestData['requests'][] = ['type' => 'execute', 'stmt' => ['sql' => $stmt]];
        $requestData['requests'][] = ['type' => "close"];
        $rs = $this->makeRequest('POST', self::BASEURL,  self::TOKEN , $requestData);
        if (isset( $rs['results'][0]['response']['result'])) {
            $rows = $rs['results'][0]['response']['result']['rows'];
            $cols = $rs['results'][0]['response']['result']['cols'];
            $colList = array_column($cols,'name');
            $list = [];
            foreach ($rows as $row) {
                $data = [];
                foreach ($colList as $index=>$col) {
                    if (isset($row[$index])){
                        if ('from' == $col && !empty($row[$index]['value'])){
                            $data['from'] = json_decode($row[$index]['value']);
                        }elseif ('date' == $col && !empty($row[$index]['value'])){
                            $dateTime = new DateTime($row[$index]['value']);
                            $data['date'] = $dateTime->format('H:i');
                        }
                        else{
                            $data[$col] = $row[$index]['value']??"";
                        }

                    }
                }
                $list = $data;

            }

            return $this->success($list);

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