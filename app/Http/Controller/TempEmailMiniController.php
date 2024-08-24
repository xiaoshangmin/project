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
        return $this->success(['downloadAd' => 0]);
    }

    #[GetMapping(path: "inads")]
    public function inads()
    {
        return $this->fail();
        //return $this->success();

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

    #[PostMapping(path: "getSensitive")]
    public function getSensitive()
    {
        $word = '习近平,平近习,xjp,习太子,习明泽,老习,温家宝,温加宝,温x,温jia宝,温宝宝,温加饱,温加保,张培莉,温云松,温如春,温jb,胡温,胡x,胡jt,胡boss,胡总,胡王八,hujintao,胡jintao,胡j涛,胡惊涛,胡景涛,胡紧掏,湖紧掏,胡紧套,锦涛,hjt,胡派,胡主席,刘永清,胡海峰,胡海清,江泽民,民泽江,江胡,江哥,江主席,江书记,江浙闽,江沢民,江浙民,择民,则民,茳泽民,zemin,ze民,老江,老j,江core,江x,江派,江zm,jzm,江戏子,江蛤蟆,江某某,江贼,江猪,江氏集团,江绵恒,江绵康,王冶坪,江泽慧,邓小平,平小邓,xiao平,邓xp,邓晓平,邓朴方,邓榕,邓质方,毛泽东,猫泽东,猫则东,猫贼洞,毛zd,毛zx,z东,ze东,泽d,zedong,毛太祖,毛相,主席画像,改革历程,朱镕基,朱容基,朱镕鸡,朱容鸡,朱云来,李鹏,李peng,里鹏,李月月鸟,李小鹏,李小琳,华主席,华国,国锋,国峰,锋同志,白春礼,薄熙来,薄一波,蔡赴朝,蔡武,曹刚川,常万全,陈炳德,陈德铭,陈建国,陈良宇,陈绍基,陈同海,陈至立,戴秉国,丁一平,董建华,杜德印,杜世成,傅锐,郭伯雄,郭金龙,贺国强,胡春华,耀邦,华建敏,黄华华,黄丽满,黄兴国,回良玉,贾庆林,贾廷安,靖志远,李长春,李春城,李建国,李克强,李岚清,李沛瑶,李荣融,李瑞环,李铁映,李先念,李学举,李源潮,栗智,梁光烈,廖锡龙,林树森,林炎志,林左鸣,令计划,柳斌杰,刘奇葆,刘少奇,刘延东,刘云山,刘志军,龙新民,路甬祥,罗箭,吕祖善,马飚,马恺,孟建柱,欧广源,强卫,沈跃跃,宋平顺,粟戎生,苏树林,孙家正,铁凝,屠光绍,王东明,汪东兴,王鸿举,王沪宁,王乐泉,王洛林,王岐山,王胜俊,王太华,王学军,王兆国,王振华,吴邦国,吴定富,吴官正,无官正,吴胜利,吴仪,奚国华,习仲勋,徐才厚,许其亮,徐绍史,杨洁篪,叶剑英,由喜贵,于幼军,俞正声,袁纯清,曾培炎,曾庆红,曾宪梓,曾荫权,张德江,张定发,张高丽,张立昌,张荣坤,张志国,赵洪祝,紫阳,周生贤,周永康,朱海仑,中南海,大陆当局,中国当局,北京当局,共产党,党产共,共贪党,阿共,产党共,公产党,工产党,共c党,共x党,共铲,供产,共惨,供铲党,供铲谠,供铲裆,共残党,共残主义,共产主义的幽灵,拱铲,老共,中共,中珙,中gong,gc党,贡挡,gong党,g产,狗产蛋,共残裆,恶党,邪党,共产专制,共产王朝,裆中央,土共,土g,共狗,g匪,共匪,仇共,症腐,政腐,政付,正府,政俯,政f,zhengfu,政zhi,挡中央,档中央,中央领导,中国zf,中央zf,国wu院,中华帝国,gong和,大陆官方,北京政权,江泽民,胡锦涛,温家宝,习近平,习仲勋,贺国强,贺子珍,周永康,李长春,李德生,王岐山,姚依林,回良玉,李源潮,李干成,戴秉国,黄镇,刘延东,刘瑞龙,俞正声,黄敬,薄熙,薄一波,周小川,周建南,温云松,徐明,江泽慧,江绵恒,江绵康,李小鹏,李鹏,李小琳,朱云来,朱容基,法轮功,李洪志,新疆骚乱,爱液,按摩棒,拔出来,爆草,包二奶,暴干,暴奸,暴乳,爆乳,暴淫,被操,被插,被干,逼奸,仓井空,插暴,操逼,操黑,操烂,肏你,肏死,操死,操我,厕奴,插比,插b,插逼,插进,插你,插我,插阴,潮吹,潮喷,成人电影,成人论坛,成人色情,成人网站,成人文学,成人小说,艳情小说,成人游戏,吃精,抽插,春药,大波,大力抽送,大乳,荡妇,荡女,盗撮,发浪,放尿,肥逼,粉穴,风月大陆,干死你,干穴,肛交,肛门,龟头,裹本,国产av,好嫩,豪乳,黑逼,后庭,后穴,虎骑,换妻俱乐部,黄片,几吧,鸡吧,鸡巴,鸡奸,妓女,奸情,叫床,脚交,精液,就去日,巨屌,菊花洞,菊门,巨奶,巨乳,菊穴,开苞,口爆,口活,口交,口射,口淫,裤袜,狂操,狂插,浪逼,浪妇,浪叫,浪女,狼友,聊性,凌辱,漏乳,露b,乱交,乱伦,轮暴,轮操,轮奸,裸陪,买春,美逼,美少妇,美乳,美腿,美穴,美幼,秘唇,迷奸,密穴,蜜穴,蜜液,摸奶,摸胸,母奸,奈美,奶子,男奴,内射,嫩逼,嫩女,嫩穴,捏弄,女优,炮友,砲友,喷精,屁眼,前凸后翘,强jian,强暴,强奸处女,情趣用品,情色,拳交,全裸,群交,人妻,人兽,日逼,日烂,肉棒,肉逼,肉唇,肉洞,肉缝,肉棍,肉茎,肉具,揉乳,肉穴,肉欲,乳爆,乳房,乳沟,乳交,乳头,骚逼,骚比,骚女,骚水,骚穴,色逼,色界,色猫,色盟,色情网站,色区,色色,色诱,色欲,色b,少年阿宾,射爽,射颜,食精,释欲,兽奸,兽交,手淫,兽欲,熟妇,熟母,熟女,爽片,双臀,死逼,丝袜,丝诱,松岛枫,酥痒,汤加丽,套弄,体奸,体位,舔脚,舔阴,调教,偷欢,推油,脱内裤,文做,舞女,无修正,吸精,夏川纯,相奸,小逼,校鸡,小穴,小xue,性感妖娆,性感诱惑,性虎,性饥渴,性技巧,性交,性奴,性虐,性息,性欲,胸推,穴口,穴图,亚情,颜射,阳具,杨思敏,要射了,夜勤病栋,一本道,一夜欢,一夜情,一ye情,阴部,淫虫,阴唇,淫荡,阴道,淫电影,阴阜,淫妇,淫河,阴核,阴户,淫贱,淫叫,淫教师,阴茎,阴精,淫浪,淫媚,淫糜,淫魔,淫母,淫女,淫虐,淫妻,淫情,淫色,淫声浪语,淫兽学园,淫书,淫术炼金士,淫水,淫娃,淫威,淫亵,淫样,淫液,淫照,阴b,应召,幼交,欲火,欲女,玉乳,玉穴,援交,原味内衣,援助交际,招鸡,招妓,抓胸,自慰,作爱,a片,fuck,gay片,g点,h动画,h动漫,失身粉,淫荡自慰器,出售雷管,炸药,炸药,出售炸药,出售炸药 电话,制作火药配方,炸药出售 ,出售雷管炸药,火药配方,在家里做原子弹,硝酸甘油炸弹制作,TNT 炸弹的制作,硝铵炸药配方,硝酸甘油制作,炸弹配方,简易炸药,火药制作简易炸弹,炸弹制作,硝酸甘油,硝酸甘油炸弹制作方法,手把手教你做炸弹,恐怖分子傻瓜手册,氢弹手工制作方法,起爆器,自制燃烧弹,燃烧弹,弹药制造方法,液体炸药配方,炸药配方与制作,自制炸药配方,tnt炸药成分,炸药的制作,tnt炸药配方,各种炸药配方大全,炸药的成分,燃烧弹的制作方 ,黑火药的配方,黑火药的制作,黑火药比例,燃烧弹 制作,黑火药配方比例,黑火药的成分,原子弹配方,原子弹DIY教程,C4炸药,雷管出售,制作硝化甘油,DIY核弹,核弹制作方法,原子弹制作手册,简易原子弹教程,黑火药配方,自制炸药方法,教你怎么用土办法做武器,莫洛托夫鸡尾酒的配方,zz炸弹的制作方法,C4炸药的成分,炸药制造生产工艺,原子弹制造完全手册,「红烧兔子」大餐,汽车炸弹制作,人体炸弹制作流程,炸弹制作从入门,鸡尾酒炸弹制作,集束炸弹制作,硝酸铵的简便制取方法,莫洛托夫燃烧弹,塑胶炸弹,用火药混合塑料,高性能爆炸物质混合,原子弹 制作方法,手教你制作原子弹,轻型巡航导弹的制作,黑索金的制造过程,Zha药制造进程,购买自爆材料,可塑炸弹制作方法,C4塑胶炸药,水浴法制TNT,简易核弹头的制造,高爆炸药的基本配方,如何制硝化甘油,燃烧剂的简易制备,简易大威力炸弹,炸弹制作入门,硝酸甘油炸药制作,基本配方是硝酸甘,燃烧剂的简易制作,制作性能优良的燃烧弹,酸甘油炸药,简易导弹制作,核弹头的制造,可塑炸弹制作,炸药硝酸甘油制法,原子弹制作方法,火药的配方,手教你做原子弹,怎么用土办法做武器,手把手教你制作原子弹,燃烧弹的制作方法,怎么自制燃烧弹,TNT炸弹制作,高爆炸药基本配方,爆破和杀伤燃烧弹,炸弹制作从入门到精,写的炸弹制作教程,原子弹制作 方法,教你制作原子弹,下硝化甘油的制作方法,DIY原子弹,原子弹制作,炸药及其制造方法,ATOM弹制造,三分钟教你原子弹,火药原理,简易炸弹,简易燃烧弹,土炸药成份,你怎么用土办法做武器,燃烧弹制作,燃烧瓶的制作方法,炸弹制作手册 硫酸,原子弹制作方法DIY,汽油三分之一白糖混合,炸药的制作配方,燃烧弹的制作方,炸药的配方,如何自制燃烧弹,标准炸弹教程,黑火药和硝酸钾农药混合,恐怖分子自己制造炸弹,手柄制作炸弹引爆器,制作炸弹首先要,燃烧瓶制作,出售炸药QQ,出售气枪,出售气枪 QQ,出售气枪 电话,出售手枪,出售手枪 QQ,出售手枪 电话,出售猎枪,出售猎枪 QQ,出售猎枪 电话,出售汽枪,出售步枪,出售狙击枪,出售手狗,出售气狗,出售猎狗,【手枪出售】联系电话,64式手枪出售,92式手枪出售,出售:高仿真枪,出售54式手枪,出售M1911,出售各类猎狗,出售国产军用54手枪,出售火枪 –游戏,出售进口金属枪模,出售仿真枪,出售麻醉枪免收任何定金,出售手枪=货到付款,出售外国枪模,出售袖珍手枪,娥眉气枪出售,防身手枪QQ,防身武器手枪,仿真枪QQ,钢珠枪小口径步枪,高压狗QQ,高压气枪,气枪子弹,高压气枪|电狗专卖,高压气枪|气枪货到付款,高压气枪专卖店,各类军用枪,各类军用枪支,各式气枪出售,工字牌气枪出售专卖,气枪,工字汽狗麻醉枪,供应军用手枪,供应汽枪,购买枪支QQ,国产汽狗专卖|气枪货,集团出售手枪,集团出售手枪气枪,进口气枪,气枪子弹,军用54出售,军用77出售,军用手枪钢珠枪,里有汽枪出售,猎枪出售/枪,猎枪仿真枪QQ,猎枪麻醉枪钢珠枪,猎枪哪里有卖,猎枪手枪买卖,猎枪销售手枪销售气枪,猎枪专卖店,猎枪子弹底火专卖,麻醉枪出售可验货,麻醉枪猎枪军用,买卖54狗,买卖64狗,买卖警察枪支,买卖军狗,买卖军用枪支,卖64手枪,卖92手枪,卖钢珠枪QQ,哪里能买到自制手枪  ,哪里有钢珠枪,哪里有连发猎枪买卖,哪里有卖双管猎,哪里有气枪卖,哪里有枪出售,哪里有真枪出售,哪里有真枪卖买,哪能买到真枪,期出售手枪气枪猎,气狗 高压气枪,气狗出售 进口气狗,气枪专卖网,气枪/出售/专卖网,气枪/猎枪/钢珠枪,气枪仿真枪出售,气枪购买|气枪组装,气枪货到付款,汽狗出售,气枪价格 仿真气枪,气枪价格，气枪专卖店,气枪看货加QQ,气枪联系QQ,气枪猎枪麻醉枪,气枪迷魂药,汽狗高压汽枪,铅弹 上海工字气枪,全金属仿真枪专卖,热武器QQ,三箭气枪出售,手枪气* 枪猎,手槍买卖自制手枪,售枪购买手枪,售手枪QQ,司马仿真枪模型QQ,淘宝气枪货到付款,提供气狗专卖店,提供专业及时的气狗,秃鹰气枪货到付款,锡峰气枪出售,仙鹤气枪出售,销售/专卖/买卖77式手枪,销售电手狗,销售气手狗,销售小口径步枪,销售运动步枪,小口径秃鹰沙漠,有军用枪支出售,长期出 售手枪,折叠狗QQ,转让 猎枪,子弹货到付款,自制手枪哪里买  ,自制手枪哪里有卖,阻击枪/汽枪/高压气枪,组装手枪  ,最新销售汽狗,出售警用手铐|仿真枪,猎枪|真枪,手枪|阻击枪,弓弩专营,弓弩专卖网货到付款,弓弩专卖网,弓弩专卖货到付款,弓弩专卖店网,弓弩专卖店,弓弩专卖,弓弩制作,弓弩直销,弓弩有限公司,弓弩营销中心,弓弩一百专卖店,弓弩销售,弓弩网 ,弓弩网,弓弩图纸,弓弩特许经销,弓弩狩猎网,自制手弩,追风弓弩麻醉箭专卖,专业弓弩网,中国战神军用弓弩,中国弩弓专卖,中国弓弩专卖网,中国弓弩直销,中国弓弩网,中国弓弩狩猎网,中国弓驽网,制作简易弓弩 ,郑州弓弩专卖,赵氏弓弩专卖网,赵氏弓弩专卖店,赵氏弓弩专卖,赵氏弓弩销售,小型弓弩专卖店,小猎人弓弩网,狩猎器材弓弩专卖,狩猎器材弓弩,狩猎弓弩专卖网,狩猎弓弩专卖,狩猎弓弩麻醉箭,手枪式折叠三用弩,三利达弓弩专卖网,三利达弓弩直营,三利达弓弩配件,三步倒药箭批发,三步倒弩箭专卖,三步倒麻醉弩箭销售,三步倒麻醉箭专卖,三步倒麻醉箭,三步倒捕狗药,军用弓弩专卖网,军用弓弩专卖店,军用弓弩批发,军用弓弩公司,供应三利达弓弩麻醉箭,供应三步倒麻醉箭,供应秦氏弓弩,供应弩用麻醉箭,供应弩捕狗箭,供应麻醉箭三步倒,供应麻醉箭批发,供应麻醉箭,供应军用弩折叠弩,供应军用弓弩专卖,供应精品弓弩,供应弓弩麻醉箭,供应弓弩,供应钢珠弓弩,弓弩商城专卖,弓弩商城,弓弩亲兄弟货到付款,弓弩批发,弓弩免定金货到付款,弓弩麻醉箭,弓弩麻醉镖,弓弩论坛 ,钢珠弓弩专卖网,钢珠弓弩专卖店,打狗弓弩三步倒,麻醉弓弩专卖店,出售军刀,出售军刺,出售弹簧刀,出售三棱刀,出售跳刀,军刀网,南方军刀网,户外军刀网,三棱军刺专卖,出售开山刀军刺,西点军刀网,军刀专 卖,戈博军刀,阿兰德龙户外,出售军品军刀,勃朗宁军刀,军刀军品网,阿兰得龙野营刀具网,出售军刺军刀,警用刀具出售,折刀专卖网,阳江军品军刀网,野营刀专卖,砍刀精品折刀专卖,匕首蝴蝶甩刀专卖,军刀专卖军刺,军刀专卖刀具批发,军刀图片砍刀,军刀网军刀专卖,军刀价格军用刀具,军品军刺网,军刀军刺甩棍,阳江刀具批发网,北方先锋军刀,正品军刺出售,野营军刀出售,开山刀砍刀出售,仿品军刺出售,军刀直刀专卖,手工猎刀专卖,自动跳刀专卖,军刀电棍销售,军刀甩棍销售,美国军刀出售,极端武力折刀,防卫棍刀户外刀具,阿兰德龙野营刀,仿品军刺网,野营砍刀户外军刀,手工猎刀户外刀具,中国户外刀具网,西点军品军刀网,野营开山刀军刺,三利达弓弩军刀,尼泊尔军刀出售,防卫野营砍刀出售,防卫著名军刀出售,防卫棍刀出售,防卫甩棍出售,防卫电棍出售,军刺野营砍刀出售,著名精品折刀出售,战术军刀出售,刺刀专卖网,户外军刀出售,阳江刀具直销网,冷钢刀具直销网,防卫刀具直销网,极端武力直销网,刀具直销网,军刀直销网,直刀匕首直销网,军刀匕首直销网,折刀砍刀军品网,野营刀具军品网,阳江刀具军品网,冷钢刀具军品网,防卫刀具军品网,极端武力军品网,军用刀具军品网,军刀直刀军品网,折刀砍刀专卖,野营刀具专卖,阳江刀具专卖,冷钢刀具专卖,防卫刀具专卖,出售美军现役军刀,操你妈,杂种,';
        return $this->success($word);
    }

    #[PostMapping(path: "getFont")]
    public function getFont()
    {
        $fontList = [[
            "value" => "inherit",
            "name" => "原始",
            "active" => true
        ], [
            "value" => "YuFanXiLiu",
            "name" => "宋体",
            "url" => "https://oss.wowyou.cc/font/YuFanXiLiu.otf",
            "active" => false
        ], [
            "value" => "mushin",
            "name" => "手写",
            "url" => "https://oss.wowyou.cc/font/mushin.otf",
            "active" => false
        ], [
            "value"=>"kaiti",
            "name"=>"楷体",
            "url"=>"https://oss.wowyou.cc/font/FangZhengKaiTiJianTi-1.ttf",
            "active"=>false,
        ],[
            "value" => "Uranus_Pixel_11Px",
            "name" => "像素",
            "url" => "https://oss.wowyou.cc/font/Uranus_Pixel_11Px.ttf",
            "active" => false
        ], [
            "value" => "xiaobai",
            "name" => "小白",
            "url" => "https://oss.wowyou.cc/font/xiaobai.ttf",
            "active" => false
        ], [
            "value" => "Softbrush",
            "name" => "软笔",
            "url" => "https://oss.wowyou.cc/font/Softbrush.ttf",
            "active" => false
        ]];
        return $this->success($fontList);
    }


    public static function limitCallback(float $seconds, ProceedingJoinPoint $proceedingJoinPoint)
    {
        // $seconds 下次生成Token 的间隔, 单位为秒
        // $proceedingJoinPoint 此次请求执行的切入点
        // 可以通过调用 `$proceedingJoinPoint->process()` 继续完成执行，或者自行处理
        return $proceedingJoinPoint->process();
    }


    //https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/sec-center/sec-check/mediaCheckAsync.html
    private function doCheck(string $mediaUrl, string $openid)
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
