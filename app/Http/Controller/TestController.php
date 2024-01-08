<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Model\Fdc;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use League\Flysystem\StorageAttributes;
use Facebook\WebDriver\Remote\RemoteWebDriver;

#[Controller(prefix: "api/test")]
class TestController extends BaseController
{
    #[Inject]
    private ClientFactory $clientFactory;

    #[GetMapping(path: "tt")]
    public function tt()
    {
        return __METHOD__;
    }

    #[GetMapping(path: "test")]
    public function test()
    {
        $waitSeconds = 5;
        $serverUrl = 'http://standalone-chrome:4444';
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
        $driver = RemoteWebDriver::create($serverUrl, $capabilities);
        $str = 'ok'.PHP_EOL;
        $insertArr = [];
        $index = 1;
        try {
            $driver->get('http://zjj.sz.gov.cn/ris/bol/szfdc/index.aspx');
            $driver->manage()->timeouts()->implicitlyWait(120);
//            while ($index <= 405) {
            sleep(random_int(2, 10));
//                $driver->wait($waitSeconds)->until(
//                    WebDriverExpectedCondition::visibilityOfAnyElementLocated(
//                        WebDriverBy::cssSelector('#AspNetPager1 a')
//                    )
//                );
            $elements = $driver->findElements(WebDriverBy::cssSelector('td'));
            $eleArr = array_chunk($elements, 6);

            foreach ($eleArr as $element) {
//                    Fdc::updateOrInsert(['id' => $match[1]], ['pre_sale_cert_name' => $element[0]->getText(),
//                        'project_name' => $element[1]->getText()]);
                if (count($element) == 6) {
                    $a = $element[1]->findElement(WebDriverBy::cssSelector('a'));
                    preg_match('/id=(\d+)/',$a->getAttribute("href"), $match);
                    $str .= $match[1] .'--'. $element[2]->getText() .'--'. $element[3]->getText() .'--'. $element[4]->getText() . $element[5]->getText() . PHP_EOL;
                }

            }
//                $pages = $driver->findElements(WebDriverBy::cssSelector("#AspNetPager1 a"));
//                $page = end($pages);
//                $page->click();
//                $index++;
//            }

        } catch (\Exception $e) {
            return $e->getMessage();
        } finally {
            $driver->quit();
        }
        return $str;
    }

    #[GetMapping(path: "bi")]
    public function es()
    {

//        $url = 'https://www.bilibili.com/video/BV1hE411t7RN/?spm_id_from=333.999.0.0&vd_source=9e0e69f9f510b3640c0fdc6be111d54c';//https://www.bilibili.com/video/BV1Sv4y1C7Fp/?spm_id_from=333.1007.tianma.2-2-5.click';
//        $text = (new Common())->get_content($url, $this->bilibiliHeaders($url));
//        preg_match('/<script>window.__INITIAL_STATE__=([^;]+)/', $text, $response);
//        $response = json_decode($response[1], true);
//        preg_match('/__playinfo__=(.*?)<\/script><script>/', $text, $playinfo);
//        $current_quality = $playinfo['data']['quality'];
//        $cid = $response['videoData']['pages'][0]['cid'];
//        return $this->bilibili_interface_api($cid,112);
        $client = $this->clientFactory->create();
        return $client->get("https://baidu.com")->getBody()->getContents();

    }

    function bilibiliHeaders($referer = null, $cookie = null)
    {
        # a reasonable UA
        $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36';
        $headers = ['Accept' => '*/*', 'Accept-Language' => 'en-US,en;q=0.5', 'User-Agent' => $ua];

        if (!empty($referer)) {
            $headers['Referer'] = $referer;
        }

        if (!empty($cookie)) {
            $headers['Cookie'] = $cookie;

        }
        return $headers;
    }

    function bilibili_interface_api($cid, $qn = 0)
    {
        $entropy = 'rbMCKn@KuamXWlPMoJGsKcbiJKUfkPF_8dABscJntvqhRSETg';
        $entropyRev = strrev($entropy);
        $entropyRevArr = [];
        for ($i = 0; $i < strlen($entropyRev); $i++) {
            $entropyRevArr[] = chr(ord($entropyRev[$i]) + 2);
        }
        $entropyStr = join('', $entropyRevArr);
        list($appkey, $sec) = explode(":", $entropyStr);
        $params = sprintf('appkey=%s&cid=%s&otype=json&qn=%s&quality=%s&type=', $appkey, $cid, $qn, $qn);
        $chksum = md5($params . $sec);
        return sprintf('https://interface.bilibili.com/v2/playurl?%s&sign=%s', $params, $chksum);
    }
}