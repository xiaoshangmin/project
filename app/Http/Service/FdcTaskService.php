<?php

namespace App\Http\Service;

use App\Model\Fdc;
use App\Model\Room;
use App\Model\Units;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;

class FdcTaskService
{

    private string $serverUrl = 'http://standalone-chrome:4444';

    #[Inject]
    public ClientFactory $clientFactory;

    #[Inject]
    protected StdoutLoggerInterface $logger;


    /**
     * #1 第一步
     * 抓取项目列表
     * @return string
     */
    public function syncList()
    {
        $chromeOptions = new ChromeOptions();
//        $chromeOptions->addArguments(["--disable-web-security"]);
//        $chromeOptions->addArguments(['--start-maximized']);
//        $chromeOptions->addArguments(['--allow-http-background-page']);
        $chromeOptions->addArguments(['--headless']);
//        $chromeOptions->addArguments(['--allow-insecure-localhost','--ignore-certificate-errors','--ignore-certificate-errors-spki-list']);
//        $chromeOptions->addArguments(['--ignore-urlfetcher-cert-requests']);
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);
//        $capabilities->setCapability('acceptSslCerts',false);
//        $capabilities->setCapability('acceptInsecureCerts',true);
        $driver = RemoteWebDriver::create($this->serverUrl, $capabilities);
        $str = 'ok' . PHP_EOL;
        $index = 1;
        try {
            $driver->get('http://zjj.sz.gov.cn/ris/bol/szfdc/index.aspx');
            $driver->manage()->timeouts()->implicitlyWait(30);
//            while ($index <= 405) {
//                sleep(random_int(2, 8));
//                if ($index > 200) {
            $elements = $driver->findElements(WebDriverBy::cssSelector('td'));
            $eleArr = array_chunk($elements, 6);
            foreach ($eleArr as $element) {
                if (count($element) == 6) {
                    $a = $element[1]->findElement(WebDriverBy::cssSelector('a'));
                    preg_match('/id=(\d+)/', $a->getAttribute("href"), $match);
                    $fdc = Fdc::query()->find($match[1]);
                    if (!is_null($fdc)) {
                        $fdc->pre_sale_cert_name = $element[1]->getText();
                        $fdc->project_name = $element[2]->getText();
                        $fdc->ent = $element[3]->getText();
                        $fdc->area = $element[4]->getText();
                        if (!empty($element[5]->getText())) {
                            $fdc->approve_time = $element[5]->getText();
                        }
                        $fdc->save();
                    } else {
                        Fdc::create([
                            'id' => $match[1],
                            'pre_sale_cert_name' => $element[1]->getText(),
                            'project_name' => $element[2]->getText(),
                            'ent' => $element[3]->getText(),
                            'area' => $element[4]->getText(),
                            'approve_time' => !empty($element[5]->getText()) ? $element[5]->getText() : null
                        ]);
                    }
                }

//                    }
//                }
//                $pages = $driver->findElements(WebDriverBy::cssSelector("#AspNetPager1 a"));
//                $page = end($pages);
//                $page->click();
//                $index++;
            }

        } catch (\Exception $e) {
            return $e->getMessage();
        } finally {
            $driver->quit();
        }
        return $str;
    }


    //{
    //    "status": 200,
    //    "msg": "成功",
    //    "data": {
    //        "total": 196,
    //        "pageSize": 1,
    //        "list": [
    //            {
    //                "id": 826974,
    //                "preSellId": null,
    //                "type": 2,
    //                "project": "中航格澜阳光花园11栋",
    //                "organName": "深圳市宅猫房地产有限公司",
    //                "serialNo": "243592067847宅猫找房",
    //                "zone": "龙华",
    //                "publishDate": "2024-01-25",
    //                "contractDate": "2024-01-25 ~ 2024-03-26",
    //                "address": "宝安区观澜街道大和路西侧",
    //                "coordinateX": "504410.4775357939",
    //                "coordinateY": "2510971.8083608835",
    //                "parcelNo": "A906-0104"
    //            }
    //        ]
    //    }
    //}

    /**
     * #2 第二步
     * 获取经纬度，备注，备案价
     * @return string
     */
    public function getProjectByApi()
    {
        try {
            $str = "ok" . PHP_EOL;
            $client = $this->clientFactory->create([
                'timeout' => 10,
                'verify' => false,
                'allow_redirects' => true,
            ]);
            $fdcList = Fdc::where('ys_project_id', '=', 0)->select(['id', 'project_name'])->orderByDesc('id')->get();
            foreach ($fdcList as $fdc) {
                //搜索
                $body = [
                    "pageIndex" => 1,
                    "pageSize" => 33,
                    "search" => $fdc->project_name,
                    "status" => "",
                    "type" => 1,//可以修改这个类型
                    "yearList" => [],
                    "zoneList" => []
                ];
                $response = $client->post(
                    'http://zjj.sz.gov.cn/szfdccommon/homeMap/getProjectList',
                    ['json' => $body]
                );
                $jsonStr = $response->getBody()->getContents();
                $resArr = json_decode($jsonStr, true);
                if (isset($resArr['data']['list']) && !empty($resArr['data']['list'])) {
                    foreach ($resArr['data']['list'] as $item) {
                        if ($item['preSellId'] == $fdc->id) {
                            if ($item['type'] == 1) {
                                $url = "http://zjj.sz.gov.cn/szfdccommon/homeMap/getYsProjectDetail?preSellId={$item['preSellId']}&ysProjectId={$item['id']}";
                                $this->logger->info($url);
                                $detail = $client->post($url);
                                $jsonStr = $detail->getBody()->getContents();
                                $resArr = json_decode($jsonStr, true);
                                if (isset($resArr['data']) && !empty($resArr['data'])) {
                                    $d = $resArr['data'];
                                    $fdc->average_price = $d['averagePrice'] ?: '';//备案均价
                                    $fdc->coordinatex = $d['coordinateX'] ?: '';
                                    $fdc->coordinatey = $d['coordinateY'] ?: '';
                                    if (!empty($d['coordinateX'])){
                                        $location = projTransform($d['coordinateX'],$d['coordinateY']);
                                        $fdc->lon = $location[0]?:'';
                                        $fdc->lat = $location[1]?:'';
                                    }
                                    $fdc->remark = $d['fpmemo'];
                                    $fdc->ys_project_id = $d['id'];
                                }
                                $fdc->save();
                                break;
                            } elseif ($item['type'] == 2) {
                                $url = "http://zjj.sz.gov.cn/szfdccommon/homeMap/getEsfSellProjectDetail?esfSelId={$item['id']}";
                                $this->logger->info($url);
                                $detail = $client->post($url);
                                $jsonStr = $detail->getBody()->getContents();
                                $resArr = json_decode($jsonStr, true);
                                if (isset($resArr['data']) && !empty($resArr['data'])) {
                                    $d = $resArr['data'];
                                    $fdc->price_reference = $d['priceReference'] ?: '';
                                    $fdc->coordinatex = $d['coordinateX'] ?: '';
                                    $fdc->coordinatey = $d['coordinateY'] ?: '';
                                }
                                $fdc->save();
                                break;
                            }
                        }
                    }
                }
            }
            return $str;
        } catch (RequestException $e) {
            $this->logger->info("getHouseDeal curl RequestException=" . $e->getMessage());
            return $e->getMessage();
        } catch (GuzzleException $e) {
            $this->logger->info("getHouseDeal curl GuzzleException=" . $e->getMessage());
            return $e->getMessage();
        }
    }



    /**
     * #3 第三步
     * 获取楼栋信息
     * @return string
     */
    public function getProjectDetailByApi()
    {
        try {
            $str = "ok" . PHP_EOL;
            $client = $this->clientFactory->create([
                'timeout' => 10,
                'verify' => false,
                'allow_redirects' => true,
            ]);
            $fdcList = Fdc::select(['id', 'ys_project_id'])->orderByDesc('id')->limit(5)->get();
            foreach ($fdcList as $fdc) {
                //获取楼栋信息
                $url = "http://zjj.sz.gov.cn/szfdcscjy/projectPublish/getBuildingNameListToPublicity?ysProjectId={$fdc->ys_project_id}&preSellId={$fdc->id}";
                $response = $client->post($url);
                $jsonStr = $response->getBody()->getContents();
                $resArr = json_decode($jsonStr, true);
                if (isset($resArr['data']) && !empty($resArr['data'])) {
                    foreach ($resArr['data'] as $item) {
                        Db::table('building')->updateOrInsert(
                            ['fdc_id' => $fdc->id, 'type' => 1, 'id' => $item['key']],
                            ['building' => $item['label']]
                        );
                    }
                }
            }
            return $str;
        } catch (RequestException $e) {
            $this->logger->info("getHouseDeal curl RequestException=" . $e->getMessage());
            return $e->getMessage();
        } catch (GuzzleException $e) {
            $this->logger->info("getHouseDeal curl GuzzleException=" . $e->getMessage());
            return $e->getMessage();
        }
    }


    /** #4 第四步
     * 抓取项目额外信息
     * @return string
     */
    public function getFdcExtra()
    {
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments(['--headless']);
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $driver = RemoteWebDriver::create($this->serverUrl, $capabilities);
        $driver->manage()->timeouts()->implicitlyWait(10);
        $str = 'ok' . PHP_EOL;


        $fdcList = Db::table("fdc")->select(['fdc.id','fdc.lon','fdc.coordinatex','fdc.coordinatey'])
            ->orderBy('fdc.id', 'desc')
            ->where('fdc.id', '>=', 105373)
            ->where('fdc.pmc', '=', '')
            ->get();

        try {
            foreach ($fdcList as $fdc) {
                $insert = [];
                $url = 'http://zjj.sz.gov.cn/ris/bol/szfdc/projectdetail.aspx?id=' . $fdc->id;
                $this->logger->info('url:' . $url);
                $driver->get($url);
                $table = $driver->findElements(WebDriverBy::tagName('table'));
                if (!isset($table[1])) {
                    continue;
                }
                $td = $table[0]->findElements(WebDriverBy::cssSelector('tr td'));
                $tdArr = array_chunk($td, 2);
                $update = [];
                foreach ($tdArr as $item) {
                    if ('宗地位置' == $item[0]->getText()) {
                        $update['address'] = $item[1]->getText();
                    }
                    if ('房屋用途' == $item[0]->getText()) {
                        $update['room_type'] = $item[1]->getText();
                    }
                    if ('预售总套数' == $item[0]->getText()) {
                        $update['ys_total_room'] = $item[1]->getText();
                    }
                    if ('物业管理公司' == $item[0]->getText()) {
                        $update['pmc'] = $item[1]->getText();
                    }
                }
                if (empty($fdc->lon) && !empty($fdc->coordinatex)){
                    $location = projTransform($fdc->coordinatex,$fdc->coordinatey);
                    $update['lon'] = $location[0]?:'';
                    $update['lat'] = $location[1]?:'';
                }

                if (!empty($update)) {
                    Db::table("fdc")->where('id', $fdc->id)->update($update);
                }
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        } finally {
            $driver->quit();
        }
        return $str;
    }

    /**
     * #5 第五步
     * 抓取楼栋单元列表
     * @return string
     */
    public function getUnits()
    {
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments(['--headless']);
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $driver = RemoteWebDriver::create($this->serverUrl, $capabilities);
        $driver->manage()->timeouts()->implicitlyWait(10);
        $str = 'ok' . PHP_EOL;

        $projectList = Db::select("SELECT
	building.`id`,
	building.`fdc_id`,
	building.`url` ,
	units.project_id
FROM
	`building` 
	LEFT JOIN units ON building.id=units.project_id
	WHERE units.project_id IS NULL AND building.`fdc_id`>17000
ORDER BY
	`fdc_id` DESC");
        try {
            foreach ($projectList as $project) {
                $insert = [];
                $url = "http://zjj.sz.gov.cn/ris/bol/szfdc/building.aspx?id={$project->id}&presellid={$project->fdc_id}";
                $this->logger->info('url:' . $url);
                $driver->get($url);
                $unitsList = $driver->findElements(WebDriverBy::cssSelector('#divShowBranch a'));
                foreach ($unitsList as $unit) {
                    $insert[] = [
                        'project_id' => $project->id,
                        'fdc_id' => $project->fdc_id,
                        'units' => trim($unit->getText()),
                        'url' => $unit->getAttribute("href"),
                    ];
                }
                if (!empty($insert)) {
                    Db::table("units")->insert($insert);
                }
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        } finally {
            $driver->quit();
        }
        return $str;
    }


    /**
     * #6 第六步
     * @return string
     */
    public function getRoomByApi()
    {
        try {
            $str = "ok" . PHP_EOL;
            $client = $this->clientFactory->create([
                'timeout' => 10,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $infoList = Db::select(
                "SELECT building.`id`,building.`fdc_id`,building.`building`, fdc.`ys_project_id` FROM building LEFT JOIN fdc ON building.`fdc_id`=fdc.`id` WHERE building.`has_get_room`=0 ORDER BY id DESC;");
            foreach ($infoList as $info) {
                $body = [
                    "buildingbranch" => "",
                    "floor" => "",
                    "fybId" => $info->id,
                    "housenb" => "",
                    "status" => -1,
                    "type" => "",
                    "ysProjectId" => $info->ys_project_id,
                    "preSellId" => $info->fdc_id
                ];
                //获取房间信息
                $url = "http://zjj.sz.gov.cn/szfdcscjy/projectPublish/getHouseInfoListToPublicity";
                $response = $client->post($url, ['json' => $body]);
                $jsonStr = $response->getBody()->getContents();
                $resArr = json_decode($jsonStr, true);
                if (isset($resArr['data']) && !empty($resArr['data'])) {
                    foreach ($resArr['data'] as $item) {
                        foreach ($item['list'] as $list) {
                            $room = Room::where(
                                ['fdc_id' => $info->fdc_id, 'type' => 1, 'building_id' => $info->id, 'room_num' => $list['housenb'], 'room_id' => $list['id']])
                                ->first();
                            $d = [
                                'fdc_id' => $info->fdc_id,
                                'type' => 1,
                                'building_id' => $info->id,
                                'room_num' => $list['housenb'],
                                'floor' => $list['floor'],
                                'status' => $list['lastStatusName'],
                                'units' => $list['buildingbranch'] ?: '未命名',
                                'room_id' => $list['id'],
                                'selling_price' => $list['askpriceeachB'] ?: 0,
                                'room_type' => $list['useage'] ?: '',
                                'floor_space' => $list['ysbuildingarea'] ?: 0,
                                'room_space' => $list['ysinsidearea'] ?: 0,
                                'share_space' => $list['ysexpandarea'] ?: 0,
                                'final_floor_space' => $list['jgbuildingarea'] ?: 0,
                                'final_room_space' => $list['jginsidearea'] ?: 0,
                                'final_share_space' => $list['jgexpandarea'] ?: 0
                            ];
                            if (!empty($room)) {
                                Db::table('room')->where('id', $room['id'])->update($d);
                            } else {
                                Db::table('room')->insert($d);
                            }
                        }
                        Db::table('building')->where('id', $info->id)->update(['has_get_room' => 1]);
                    }
                }
            }
            return $str;
        } catch (RequestException $e) {
            $this->logger->info("getHouseDeal curl RequestException=" . $e->getMessage());
            return $e->getMessage();
        } catch (GuzzleException $e) {
            $this->logger->info("getHouseDeal curl GuzzleException=" . $e->getMessage());
            return $e->getMessage();
        }
    }


    /**
     *
     * 抓取房间列表
     * @return string
     */
    public function getRoom()
    {
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments(['--headless']);
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $driver = RemoteWebDriver::create($this->serverUrl, $capabilities);
        $driver->manage()->timeouts()->implicitlyWait(10);
        $str = 'ok' . PHP_EOL;
        // select * from `building` where `has_get_room` = '0' order by `fdc_id` desc
        $buildingList = Units::where('has_get_room', '=', 0)->orderBy('fdc_id', 'desc')->get();
        //预售ys  销售xs
        $type = 'ys';

        try {
            foreach ($buildingList as $building) {
                $insert = [];
                $url = "http://zjj.sz.gov.cn/ris/bol/szfdc/{$building->url}{$type}";
                $this->logger->info('url:' . $url);
                $driver->get($url);
                $roomList = $driver->findElements(WebDriverBy::cssSelector('td>div>a.presale2like'));
                foreach ($roomList as $room) {
//                    $str .= $room->getText() . $room->getAttribute('href') . PHP_EOL;
                    $insert[] = [
                        'project_id' => $building->project_id,
                        'fdc_id' => $building->fdc_id,
                        'units' => $building->units,
                        'status' => $room->getText(),
                        'url' => $room->getAttribute("href"),
                    ];
                }
                if (!empty($insert)) {
                    Db::table("room")->insert($insert);
                    $building->has_get_room = 1;
                    $building->save();
                } else {
                    $building->has_get_room = 2;
                    $building->save();
                }
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        } finally {
            $driver->quit();
        }
        return $str;
    }


    /**
     * #7 第七步
     * 抓取房间详情列表
     * @return string
     */
    public function getRoomDetail()
    {
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments(['--headless']);
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $driver = RemoteWebDriver::create($this->serverUrl, $capabilities);
        $driver->manage()->timeouts()->implicitlyWait(5);
        $str = 'ok' . PHP_EOL;

        $roomList = Room::where('room_num', '=', '')->orderBy('id')->limit(100000)->get();

        try {
            foreach ($roomList as $room) {
                $url = "http://zjj.sz.gov.cn/ris/bol/szfdc/{$room->url}";
                $this->logger->info('url:' . $url);
                $driver->get($url);
                $roomInfo = $driver->findElements(WebDriverBy::cssSelector('tr td'));
                if (empty($roomInfo) || !isset($roomInfo[11])) {
                    continue;
                }
                $roomNum = $roomInfo[11]->getText();//房间号
                if (!empty($roomNum)) {
                    $room->selling_price = $roomInfo[7]->getText();//拟售价格
                    $room->floor = $roomInfo[9]->getText();//楼层
                    $room->room_num = $roomInfo[11]->getText();//房间号
                    $room->room_type = $roomInfo[13]->getText();//房间用途
                    $room->barrier_free = $roomInfo[15]->getText();//是否无障碍住房
                    $room->floor_space = $roomInfo[17]->getText();//建筑面积(预售)
                    $room->room_space = $roomInfo[19]->getText();//户内面积(预售)
                    $room->share_space = $roomInfo[21]->getText();//分摊面积(预售)
                    $room->final_floor_space = $roomInfo[23]->getText();//建筑面积(竣工)
                    $room->final_room_space = $roomInfo[25]->getText();//户内面积(竣工)
                    $room->final_share_space = $roomInfo[27]->getText();//分摊面积(竣工)
                    $room->save();
                }
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        } finally {
            $driver->quit();
        }
        return $str;

    }


    /**
     * 抓取成交量统计
     * @return string
     */
    public function getHouseDeal()
    {
        try {
            $client = $this->clientFactory->create([
                'timeout' => 10,
                'verify' => false,
                'allow_redirects' => true,
            ]);
            //二手房
            $response = $client->postAsync('http://zjj.sz.gov.cn:8004/api/marketInfoShow/getEsfCjxxGsData')->wait();
            $jsonStr = $response->getBody()->getContents();
            $resArr = json_decode($jsonStr, true);
            if (isset($resArr['status']) && $resArr['status'] == 1) {
                $xmlDateDay = strtotime(str_replace(['年', '月', '日'], "", $resArr['data']['xmlDateDay']));
                $mj = $resArr['data']['dataMj'];
                $ts = $resArr['data']['dataTs'];
                Db::table('house_deal')->updateOrInsert(
                    ['xml_date_day' => $xmlDateDay, 'type' => 1],
                    ['data' => json_encode(['mj' => $mj, 'ts' => $ts])]
                );
            }
            //一手房
            $response = $client->postAsync('http://zjj.sz.gov.cn:8004/api/marketInfoShow/getYsfCjxxGsData')->wait();
            $jsonStr = $response->getBody()->getContents();
            $resArr = json_decode($jsonStr, true);
            if (isset($resArr['status']) && $resArr['status'] == 1) {
                $xmlDateDay = strtotime(str_replace(['年', '月', '日'], "", $resArr['data']['xmlDateDay']));
                $mj = $resArr['data']['dataMj'];
                $ts = $resArr['data']['dataTs'];
                Db::table('house_deal')->updateOrInsert(
                    ['xml_date_day' => $xmlDateDay, 'type' => 2],
                    ['data' => json_encode(['mj' => $mj, 'ts' => $ts])]
                );
            }
            return "ok" . PHP_EOL;
        } catch (RequestException $e) {
            $this->logger->info("getHouseDeal curl RequestException=" . $e->getMessage());
            return $e->getMessage();
        } catch (GuzzleException $e) {
            $this->logger->info("getHouseDeal curl GuzzleException=" . $e->getMessage());
            return $e->getMessage();
        }
    }


    /**
     * 抓取成交量详细
     * @return string
     */
    public function getHouseDealDetail()
    {
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments(['--headless']);
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $driver = RemoteWebDriver::create($this->serverUrl, $capabilities);
        $driver->manage()->timeouts()->implicitlyWait(5);
        $str = 'ok' . PHP_EOL;

        try {
            //二手
            $url = "http://zjj.sz.gov.cn/ris/szfdc/showcjgs/esfcjgs.aspx";
            $this->logger->info('url:' . $url);
            $driver->get($url);
            $xmlDateDay = $driver->findElement(WebDriverBy::id("lblCurTime1"));
            $str .= $xmlDateDay->getText() . PHP_EOL;
            $xmlDateDay = strtotime(str_replace(['年', '月', '日'], "", $xmlDateDay->getText()));
            $oldDetail = Db::table('house_deal_detail')
                ->where('xml_date_day', '=', $xmlDateDay)
                ->where('type', '=', 2)
                ->first();
            if (empty($oldDetail)) {
                $idList = ['hypAll', 'hypBa', 'hypFt', 'hypLg', 'hypLh', 'hypNs', 'hypQh', 'hypYt', 'hypLhQ', 'hypGm', 'hypPs', 'hypDp', 'hypSSHZ'];
                $index = 0;
                $insertList = [];
                foreach ($idList as $item) {
                    $index++;
                    $area = $driver->findElement(WebDriverBy::xpath('//a[@style="color:Red;"]'));//div[class="left recordLink"]
//                $str .= $area->getText() . PHP_EOL;
                    $tableList = $driver->findElements(WebDriverBy::tagName("table"));
                    if (!empty($tableList) && count($tableList) == 2) {
                        $spanList = $tableList[0]->findElements(WebDriverBy::cssSelector("td span"));
                        $spanArr = array_chunk($spanList, 3);
                        foreach ($spanArr as $span) {
//                        $str .= $span[0]->getText() . "---" . $span[1]->getText() . "---" . $span[2]->getText() . PHP_EOL;
                            $insertList[] = [
                                'area' => $area->getText(),
                                'xml_date_day' => $xmlDateDay,
                                'type' => 2,
                                'use' => $span[0]->getText(),
                                'deal_area' => $span[1]->getText(),
                                'deal_num' => $span[2]->getText()
                            ];
                        }

                    }
                    if (isset($idList[$index])) {
                        $ele = $driver->findElement(WebDriverBy::id($idList[$index]));
                        $ele->click();
                        sleep(2);
                    }
                }
                if (!empty($insertList)) {
                    Db::table("house_deal_detail")->insert($insertList);
                }
            }
            $newDetail = Db::table('house_deal_detail')
                ->where('xml_date_day', '=', $xmlDateDay)
                ->where('type', '=', 1)
                ->first();
            if (empty($newDetail)) {
                $idList = ['hypAll', 'hypBa', 'hypFt', 'hypLg', 'hypLh', 'hypNs', 'hypYt', 'hypLongHua', 'hypGm', 'hypPs', 'hypDP', 'hypsshz'];
                //一手
                $url = "http://zjj.sz.gov.cn/ris/szfdc/showcjgs/ysfcjgs.aspx";
                $this->logger->info('url:' . $url);
                $driver->get($url);

                $index = 0;
                $insertList = [];
                foreach ($idList as $item) {
                    $index++;
                    $area = $driver->findElement(WebDriverBy::id('ctl03_lbldistrict2'));//div[class="left recordLink"]
//                    $str .= $area->getText() . PHP_EOL;
                    $tableList = $driver->findElements(WebDriverBy::tagName("table"));
                    if (!empty($tableList) && count($tableList) == 2) {
                        $spanList = $tableList[0]->findElements(WebDriverBy::cssSelector("td span"));
                        $spanArr = array_chunk($spanList, 5);
                        foreach ($spanArr as $span) {
//                            $str .= $span[0]->getText() . "---" . $span[1]->getText() . "---" . $span[2]->getText() . PHP_EOL;
                            $insertList[] = [
                                'area' => $area->getText(),
                                'xml_date_day' => $xmlDateDay,
                                'type' => 1,
                                'use' => $span[0]->getText(),
                                'deal_num' => $span[1]->getText(),
                                'deal_area' => $span[2]->getText(),
                                'sellable' => $span[3]->getText(),
                                'sellable_area' => $span[4]->getText(),
                            ];
                        }

                    }
                    if (isset($idList[$index])) {
                        $ele = $driver->findElement(WebDriverBy::id($idList[$index]));
                        $ele->click();
                        sleep(2);
                    }
                }
                if (!empty($insertList)) {
                    Db::table("house_deal_detail")->insert($insertList);
                }
            }

        } catch (\Exception $e) {
            return $e->getMessage();
        } finally {
            $driver->quit();
        }
        return $str;
    }


    /**
     * 抓取指导价
     * @return string
     */
    public function getGuidePrice()
    {
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments(['--headless']);
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $driver = RemoteWebDriver::create($this->serverUrl, $capabilities);
        $driver->manage()->timeouts()->implicitlyWait(5);
        $str = 'ok' . PHP_EOL;

        try {
            $url = "http://zjj.sz.gov.cn:8004/houseCondition/four";
            $this->logger->info('url:' . $url);
            $driver->get($url);
            $priceInfo = $driver->findElements(WebDriverBy::cssSelector('tr[style="height:16pt"] p'));
            $arr = array_chunk($priceInfo, 5);
            $insert = [];
            foreach ($arr as $item) {
                $str .= $item[0]->getText() . $item[1]->getText() . $item[2]->getText() . $item[3]->getText() . $item[4]->getText() . PHP_EOL;
                $insert[] = [
                    'id' => $item[0]->getText(),
                    'area' => $item[1]->getText(),
                    'street' => $item[2]->getText(),
                    'name' => $item[3]->getText(),
                    'price' => $item[4]->getText()
                ];
            }

            if (!empty($insert)) {
                Db::table("guide_price")->insert($insert);
            }

        } catch (\Exception $e) {
            return $e->getMessage();
        } finally {
            $driver->quit();
        }
        return $str;
    }

}