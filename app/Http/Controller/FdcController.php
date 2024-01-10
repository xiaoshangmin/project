<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Model\Building;
use App\Model\Fdc;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

#[Controller(prefix: "api/fdc")]
class FdcController extends BaseController
{
    #[Inject]
    private ClientFactory $clientFactory;

    private string $serverUrl = 'http://standalone-chrome:4444';

    #[GetMapping(path: "tt")]
    public function tt()
    {
        return __METHOD__;
    }

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

    #[GetMapping(path: "getDetail")]
    public function getDetail()
    {
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments(['--headless']);
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $driver = RemoteWebDriver::create($this->serverUrl, $capabilities);
        $driver->manage()->timeouts()->implicitlyWait(30);
        $str = 'ok' . PHP_EOL;
//        $fdcList = Fdc::select(['id'])->orderBy('id', 'desc')->get();
//        $fdcList = Fdc::select('SELECT f.id FROM fdc f LEFT JOIN building b ON f.id=b.fdc_id WHERE b.id IS NULL ORDER BY f.id DESC;')->get();
        $fdcList = Db::table("fdc")->select(['fdc.id'])
            ->leftJoin("building", 'fdc.id', '=', 'building.fdc_id')
            ->orderBy('fdc.id', 'desc')
//            ->where('fdc.id', '>', '5200')
            ->whereNull('building.id')->get();
        $url = '';
        try {
            foreach ($fdcList as $fdc) {
                $insert = [];
                $url = 'http://zjj.sz.gov.cn/ris/bol/szfdc/projectdetail.aspx?id=' . $fdc->id;
                $this->logger->info('url:' . $url);
                $driver->get($url);
                $table = $driver->findElements(WebDriverBy::tagName('table'));
                if (!isset($table[1])){
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
                    Db::table("building")->insert($insert);
                }
//                sleep(random_int(1, 6));
//            $elements = $driver->findElements(WebDriverBy::cssSelector('#divShowBranch > a'));
//            foreach ($elements as $element) {
//                $str .= $element->getAttribute("href") . "--" . $element->getText() . PHP_EOL;
//            }
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        } finally {
            $driver->quit();
        }
        return $str;
    }
}