<?php
/**
 * Alipay Payment Integration
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2023-02-15 14:38:55
 * @modified   2023-02-15 14:38:55
 */

namespace Plugin\GlobalAlipay\Libraries;

use Exception;
use Illuminate\Support\Facades\Log;

class Alipay
{
    public const PROD_GATEWAY_URL = 'https://openapi.alipay.com/gateway.do';

    public const SANDBOX_GATEWAY_URL = 'https://openapi.alipaydev.com/gateway.do';

    private string $apiMethodNamePage = 'alipay.trade.page.pay';

    private string $apiMethodNameWap = 'alipay.trade.wap.pay';

    private string $postCharset = 'UTF-8';

    private string $alipaySdkVersion = 'alipay-sdk-php-20161101';

    private string $apiVersion = '1.0';

    private string $gatewayUrl;

    private string $alipay_public_key;

    private string $private_key;

    private string $appid;

    private string $notifyUrl;

    private string $returnUrl;

    private string $format = 'json';

    private string $signType = 'RSA2';

    private array $apiParas = [];

    /**
     * Alipay constructor.
     * @param $config
     * @throws Exception
     */
    public function __construct($config)
    {
        $this->setParams($config);
    }

    /**
     * @param $config
     * @return Alipay
     * @throws Exception
     */
    public static function getInstance($config): self
    {
        return new self($config);
    }

    /**
     * 预支付, 获取支付参数
     *
     * @param $builder
     * @param string $httpMethod
     * @return array|string
     */
    public function pagePay($builder, string $httpMethod = 'POST'): array|string
    {
        $bizContent = null;
        if (! empty($builder)) {
            $bizContent = json_encode($builder, JSON_UNESCAPED_UNICODE);
        }
        $this->logInfo($bizContent);

        $this->apiParas['biz_content'] = $bizContent;
        $response                      = $this->pageExecute(is_mobile() ? $this->apiMethodNameWap : $this->apiMethodNamePage, $httpMethod);
        $this->logInfo('response: ', $response);

        return $response;
    }

    /**
     * 验证数据
     *
     * @param $params
     * @return bool
     * @throws Exception
     */
    public function check($params): bool
    {
        return $this->rsaCheck($params, $this->signType);
    }

    /**
     * 执行退款
     *
     * @param $builder
     * @return mixed
     * @throws Exception
     */
    public function refund($builder): mixed
    {
        $bizContent = null;
        if (! empty($builder)) {
            $bizContent = json_encode($builder, JSON_UNESCAPED_UNICODE);
        }

        $this->apiParas['biz_content'] = $bizContent;
        $response                      = $this->postRequest('alipay.trade.refund');

        return $response['alipay_trade_refund_response'];
    }

    /**
     * 退款查询
     *
     * @param $builder
     * @return mixed
     * @throws Exception
     */
    public function refundQuery($builder): mixed
    {
        $bizContent = null;
        if (! empty($builder)) {
            $bizContent = json_encode($builder, JSON_UNESCAPED_UNICODE);
        }

        $this->apiParas['biz_content'] = $bizContent;

        $response = $this->postRequest('alipay.trade.fastpay.refund.query');

        return $response['alipay_trade_fastpay_refund_query_response'];
    }

    /**
     * 设置支付参数
     *
     * @throws Exception
     */
    private function setParams($config)
    {
        $testMode = $config['test_mode'] ?? false;
        if ($testMode) {
            $this->gatewayUrl = self::SANDBOX_GATEWAY_URL;
        } else {
            $this->gatewayUrl = self::PROD_GATEWAY_URL;
        }

        $this->appid             = $config['app_id'];
        $this->private_key       = $config['merchant_private_key'];
        $this->alipay_public_key = $config['alipay_public_key'];

        if (isset($config['charset'])) {
            $this->postCharset = $config['charset'];
        }

        if (isset($config['sign_type'])) {
            $this->signType = $config['sign_type'];
        }

        $this->notifyUrl = $config['notify_url'] ?? '';
        $this->returnUrl = $config['return_url'] ?? '';

        if (empty($this->appid) || trim($this->appid) == '') {
            throw new Exception('appid should not be NULL!');
        }
        if (empty($this->private_key) || trim($this->private_key) == '') {
            throw new Exception('private_key should not be NULL!');
        }
        if (empty($this->alipay_public_key) || trim($this->alipay_public_key) == '') {
            throw new Exception('alipay_public_key should not be NULL!');
        }
        if (empty($this->postCharset) || trim($this->postCharset) == '') {
            throw new Exception('charset should not be NULL!');
        }
        if (empty($this->gatewayUrl) || trim($this->gatewayUrl) == '') {
            throw new Exception('gateway_url should not be NULL!');
        }
    }

    /**
     * 页面执行
     *
     * @param $method
     * @param string $httpMethod
     * @return array|string
     */
    private function pageExecute($method, string $httpMethod = 'POST'): array|string
    {
        $iv         = $this->apiVersion;
        $httpMethod = strtoupper($httpMethod);

        $sysParams['app_id']      = $this->appid;
        $sysParams['version']     = $iv;
        $sysParams['format']      = $this->format;
        $sysParams['sign_type']   = $this->signType;
        $sysParams['method']      = $method;
        $sysParams['timestamp']   = date('Y-m-d H:i:s');
        $sysParams['alipay_sdk']  = $this->alipaySdkVersion;
        $sysParams['notify_url']  = $this->notifyUrl;
        $sysParams['return_url']  = $this->returnUrl;
        $sysParams['charset']     = $this->postCharset;
        $sysParams['gateway_url'] = $this->gatewayUrl;

        $apiParams = $this->apiParas;

        $totalParams = array_merge($apiParams, $sysParams);

        $totalParams['sign'] = $this->generateSign($totalParams, $this->signType);

        if ('GET' == $httpMethod) {
            $preString = $this->getSignContentUrlEncode($totalParams);

            return $this->gatewayUrl . '?' . $preString;
        }
            foreach ($totalParams as $key => $value) {
                if (false === $this->checkEmpty($value)) {
                    $value             = str_replace('"', '&quot;', $value);
                    $totalParams[$key] = $value;
                } else {
                    unset($totalParams[$key]);
                }
            }

            return $totalParams;

    }

    /**
     * 判断参数是否为空
     *
     * @param $value
     * @return bool
     */
    private function checkEmpty($value): bool
    {
        if (! isset($value)) {
            return true;
        } elseif (trim($value) === '') {
            return true;
        }

        return false;
    }

    /**
     * 数据验证
     *
     * @param $params
     * @param string $signType
     * @return bool
     * @throws Exception
     */
    private function rsaCheck($params, string $signType = 'RSA'): bool
    {
        $sign                = $params['sign'];
        $params['sign_type'] = null;
        $params['sign']      = null;

        return $this->verify($this->getSignContent($params), $sign, $signType);
    }

    /**
     * 通过公钥验证签名
     *
     * @param $data
     * @param $sign
     * @param string $signType
     * @return bool
     * @throws Exception
     */
    private function verify($data, $sign, string $signType = 'RSA'): bool
    {
        $pubKey = $this->alipay_public_key;

        if (stripos($pubKey, 'BEGIN PUBLIC KEY') === false) {
            $res = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($pubKey, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
        } else {
            $res = $pubKey;
        }

        if (empty(trim($pubKey))) {
            throw new Exception('无效的支付宝公钥');
        }

        if ('RSA2' == $signType) {
            $result = (bool) openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool) openssl_verify($data, base64_decode($sign), $res);
        }

        return $result;
    }

    /**
     * 待签名字符串
     *
     * @param $params
     * @return string
     */
    private function getSignContent($params): string
    {
        ksort($params);

        $stringToBeSigned = '';
        $i                = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && '@' != substr($v, 0, 1)) {
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . '=' . "$v";
                } else {
                    $stringToBeSigned .= '&' . "$k" . '=' . "$v";
                }
                $i++;
            }
        }

        unset($k, $v);

        return $stringToBeSigned;
    }

    /**
     * 生成签名字符串
     *
     * @param $params
     * @param string $signType
     * @return string
     */
    private function generateSign($params, string $signType = 'RSA'): string
    {
        return $this->sign($this->getSignContent($params), $signType);
    }

    /**
     * 通过 openssl 生成签名字符串
     *
     * @param $data
     * @param string $signType
     * @return string
     */
    private function sign($data, string $signType = 'RSA'): string
    {
        $priKey = $this->private_key;

        if (stripos($priKey, 'BEGIN RSA PRIVATE KEY') === false) {
            $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($priKey, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
        } else {
            $res = $priKey;
        }

        if ('RSA2' == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }

        return base64_encode($sign);
    }

    /**
     * 发起支付请求
     *
     * @param $method
     * @return mixed
     * @throws Exception
     */
    private function postRequest($method): mixed
    {
        $sysParams['app_id']     = $this->appid;
        $sysParams['version']    = $this->apiVersion;
        $sysParams['format']     = $this->format;
        $sysParams['sign_type']  = $this->signType;
        $sysParams['method']     = $method;
        $sysParams['timestamp']  = date('Y-m-d H:i:s');
        $sysParams['alipay_sdk'] = $this->alipaySdkVersion;
        $sysParams['charset']    = $this->postCharset;

        $apiParams = $this->apiParas;

        $totalParams = array_merge($apiParams, $sysParams);

        $totalParams['sign'] = $this->generateSign($totalParams, $this->signType);

        $result = $this->curl($this->gatewayUrl, $totalParams);

        return json_decode($result, true);
    }

    /**
     * 生成签名后的参数, 用于GET请求
     *
     * @param $params
     * @return string
     */
    private function getSignContentUrlEncode($params): string
    {
        ksort($params);

        $stringToBeSigned = '';
        $i                = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && '@' != substr($v, 0, 1)) {
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . '=' . urlencode($v);
                } else {
                    $stringToBeSigned .= '&' . "$k" . '=' . urlencode($v);
                }
                $i++;
            }
        }

        unset($k, $v);

        return $stringToBeSigned;
    }

    /**
     * @param $url
     * @param null $postFields
     * @return bool|string
     * @throws Exception
     */
    private function curl($url, $postFields = null): bool|string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $postBodyString = '';
        $encodeArray    = [];
        $postMultipart  = false;

        if (is_array($postFields) && 0 < count($postFields)) {
            foreach ($postFields as $k => $v) {
                if ('@' != substr($v, 0, 1)) { //判断是不是文件上传
                    $postBodyString .= "$k=" . urlencode($v) . '&';
                    $encodeArray[$k] = $v;
                } else { //文件上传用multipart/form-data，否则用www-form-urlencoded
                    $postMultipart   = true;
                    $encodeArray[$k] = new CURLFile(substr($v, 1));
                }
            }
            unset($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encodeArray);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
            }
        }

        if ($postMultipart) {
            $headers = ['content-type: multipart/form-data;charset=' . $this->postCharset . ';boundary=' . $this->getMillisecond()];
        } else {
            $headers = ['content-type: application/x-www-form-urlencoded;charset=' . $this->postCharset];
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch), 0);
        }
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new Exception($response, $httpStatusCode);
            }

        curl_close($ch);

        return $response;
    }

    /**
     * 获取微秒
     *
     * @return float
     */
    private function getMillisecond(): float
    {
        [$s1, $s2] = explode(' ', microtime());

        return (float) sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    /**
     * @return string
     */
    private function getPostCharset(): string
    {
        return trim($this->postCharset);
    }

    /**
     * 日志记录
     *
     * @param ...$messages
     */
    private function logInfo(...$messages)
    {
        foreach ($messages as $message) {
            if (is_array($message)) {
                $message = json_encode($message);
            } else {
                $message = (string) $message;
            }
            Log::info($message);
        }
    }
}
