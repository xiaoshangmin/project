<?php
declare(strict_types=1);

namespace App\Util;

/**
 * 引用微信签名认证类
 */
class Sign
{
    /**
     * 生成签名串
     * @param array $data
     * @param string $key
     * @param string $signType
     * @return string
     */
    public static function createSignature(array $data, string $key, string $signType = 'HMAC-SHA256'): string
    {
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string = self::formatUrlParams($data);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $key;
        //签名步骤三：MD5加密或者HMAC-SHA256
        if ($signType == 'md5') {
            //如果签名小于等于32个,则使用md5验证
            $string = md5($string);
        } else {
            //是用sha256校验
            $string = hash_hmac("sha256", $string, $key);
        }
        //签名步骤四：所有字符转为大写
        return strtoupper($string);
    }

    /**
     * 格式化成url参数
     * @param array $data
     * @return string
     */
    public static function formatUrlParams(array $data): string
    {
        $buff = "";
        foreach ($data as $k => $v) {
            if ($k != "sign" && $v !== "null" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        return trim($buff, "&");
    }

}
