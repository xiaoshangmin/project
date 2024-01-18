<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Model\Building;
use App\Model\Fdc;
use App\Model\Room;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

#[Controller(prefix: "api/fdc")]
class FdcController extends BaseController
{

    private string $serverUrl = 'http://standalone-chrome:4444';

    #[GetMapping(path: "tt")]
    public function tt()
    {
        return __METHOD__;
    }

    /**
     * 抓取项目列表
     * @return string
     */
    #[GetMapping(path: "syncList")]
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
            $driver->manage()->timeouts()->implicitlyWait(120);
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

    /**
     * 抓取项目列表
     * @return string
     */
    #[GetMapping(path: "getProject")]
    public function getProject()
    {
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments(['--headless']);
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $driver = RemoteWebDriver::create($this->serverUrl, $capabilities);
        $driver->manage()->timeouts()->implicitlyWait(10);
        $str = 'ok' . PHP_EOL;

        $skipIdList = [347, 335, 322, 314, 313, 289, 276, 268, 252, 235, 231, 224, 203, 191, 190, 183, 174, 171, 154, 146, 140, 139, 135, 132, 116, 115, 114, 113, 110, 103, 93, 91, 90, 86, 80, 70, 63, 62, 58, 46, 25, 18, 16, 3];

        $fdcList = Db::table("fdc")->select(['fdc.id'])
            ->leftJoin("project_detail", 'fdc.id', '=', 'project_detail.fdc_id')
            ->orderBy('fdc.id', 'desc')
            ->whereNotIn('fdc.id', $skipIdList)
            ->whereNull('project_detail.id')->get();

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
                $td = $table[1]->findElements(WebDriverBy::cssSelector('tr td'));
                $tdArr = array_chunk($td, 5);
                foreach ($tdArr as $item) {
                    $href = $item[4]->findElement(WebDriverBy::tagName("a"));
                    preg_match('/\?id=(\d+)/', $href->getAttribute('href'), $match);
                    $insert[] = [
                        'id' => $match[1],
                        'fdc_id' => $fdc->id,
                        'building' => trim($item[1]->getText()),
                        'url' => $href->getAttribute("href"),
                    ];
                }
                if (!empty($insert)) {
                    Db::table("project_detail")->insert($insert);
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
     * 抓取楼栋单元列表
     * @return string
     */
    #[GetMapping(path: "getUnits")]
    public function getUnits()
    {
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments(['--headless']);
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $driver = RemoteWebDriver::create($this->serverUrl, $capabilities);
        $driver->manage()->timeouts()->implicitlyWait(30);
        $str = 'ok' . PHP_EOL;

        $projectList = Db::select("SELECT
	project_detail.`id`,
	project_detail.`fdc_id`,
	project_detail.`url` ,
	building.project_id
FROM
	`project_detail` 
	LEFT JOIN building ON project_detail.id=building.project_id
	WHERE building.project_id IS NULL
ORDER BY
	`fdc_id` DESC");
        try {
            foreach ($projectList as $project) {
                $insert = [];
                $url = "http://zjj.sz.gov.cn/ris/bol/szfdc/{$project->url}";
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
                    Db::table("building")->insert($insert);
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
     * 抓取房间列表
     * @return string
     */
    #[GetMapping(path: "getRoom")]
    public function getRoom()
    {
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments(['--headless']);
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $driver = RemoteWebDriver::create($this->serverUrl, $capabilities);
        $driver->manage()->timeouts()->implicitlyWait(10);
        $str = 'ok' . PHP_EOL;

        $buildingList = Building::where('has_get_room', '=', 0)->orderBy('fdc_id', 'desc')->get();
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
     * 抓取房间详情列表
     * @return string
     */
    #[GetMapping(path: "getRoomDetail")]
    public function getRoomDetail()
    {
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments(['--headless']);
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $driver = RemoteWebDriver::create($this->serverUrl, $capabilities);
//        $driver->get('http://zjj.sz.gov.cn:8004/');
        $driver->manage()->timeouts()->implicitlyWait(5);
        $str = 'ok' . PHP_EOL;

        $roomList = Room::where('room_num', '=', '')->orderBy('id', 'desc')->limit(50000)->get();

        try {
            foreach ($roomList as $room) {
                $url = "http://zjj.sz.gov.cn/ris/bol/szfdc/{$room->url}";
                $this->logger->info('url:' . $url);
                $driver->get($url);
                $roomInfo = $driver->findElements(WebDriverBy::cssSelector('tr td'));
//                $sellingPrice = $roomInfo[7]->getText();//拟售价格
//                $floor = $roomInfo[9]->getText();//楼层
                $roomNum = $roomInfo[11]->getText();//房间号
//                $roomType = $roomInfo[13]->getText();//房间用途
//                $barrierFree = $roomInfo[15]->getText();//是否无障碍住房
//                $floorSpace = $roomInfo[17]->getText();//建筑面积(预售)
//                $roomSpace = $roomInfo[19]->getText();//户内面积(预售)
//                $shareSpace = $roomInfo[21]->getText();//分摊面积(预售)
//                $finalFloorSpace = $roomInfo[23]->getText();//建筑面积(竣工)
//                $finalRoomSpace = $roomInfo[25]->getText();//户内面积(竣工)
//                $finalShareSpace = $roomInfo[27]->getText();//分摊面积(竣工)
//                $str .= $sellingPrice . PHP_EOL . $floor . PHP_EOL . $roomNum . PHP_EOL . $roomType . PHP_EOL . $barrierFree . PHP_EOL . $floorSpace . PHP_EOL . $roomSpace
//                    . PHP_EOL . $shareSpace . PHP_EOL . $finalFloorSpace . PHP_EOL . $finalRoomSpace . PHP_EOL . $finalShareSpace . PHP_EOL;
                if (!empty($roomNum)) {
                    $room->selling_price = $roomInfo[7]->getText();//拟售价格
                    $room->floor = $roomInfo[9]->getText();//楼层
                    $room->room_num = $roomInfo[11]->getText();//房间号
                    $room->room_type = $roomInfo[13]->getText();//房间用途
                    $room->barrier_free =$roomInfo[15]->getText();//是否无障碍住房
                    $room->floor_space = $roomInfo[17]->getText();//建筑面积(预售)
                    $room->room_space = $roomInfo[19]->getText();//户内面积(预售)
                    $room->share_space =  $roomInfo[21]->getText();//分摊面积(预售)
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
}