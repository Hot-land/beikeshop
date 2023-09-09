<?php
/**
 * Alipay.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2022-12-06 14:41:13
 * @modified   2022-12-06 14:41:13
 */

namespace Plugin\GlobalAlipay\Services\Payment;

use Beike\Models\Order;
use Beike\Services\CurrencyService;
use Exception;
use Illuminate\Support\Facades\Log;
use Plugin\GlobalAlipay\Libraries\Alipay as AlipaySdk;

class Alipay extends Base
{
    public const PAYMENT_CODE = 'GlobalAlipay';

    public AlipaySdk $payment;

    private array $payRequestData = [];

    private string $returnUrl;

    /**
     * @throws Exception
     */
    public static function getInstance($returnUrl = ''): self
    {
        return new self($returnUrl);
    }

    /**
     * @throws Exception
     */
    public function __construct($returnUrl = '')
    {
        $this->setReturnUrl($returnUrl);
        $this->initAlipay();
    }

    /**
     * 设置同步回调地址
     *
     * @param $returnUrl
     * @return void
     */
    private function setReturnUrl($returnUrl): void
    {
        $this->returnUrl = $returnUrl;
    }

    /**
     * 初始化 Alipay Payment 实例
     *
     * @throws Exception
     */
    private function initAlipay(): void
    {
        $appID      = plugin_setting('alipay.app_id');
        $privateKey = plugin_setting('alipay.merchant_private_key');
        $publicKey  = plugin_setting('alipay.alipay_public_key');

        if (empty($privateKey)) {
            throw new \Exception('商户私钥不存在');
        }
        if (empty($publicKey)) {
            throw new \Exception('支付宝公钥不存在');
        }
        if (empty($this->returnUrl)) {
            $this->returnUrl = config('app.url') . 'callback/alipay/return';
        }

        $config = [
            'app_id'               => $appID,
            'merchant_private_key' => $privateKey,
            'alipay_public_key'    => $publicKey,
            'notify_url'           => config('app.url') . 'callback/alipay/notify',
            'return_url'           => $this->returnUrl,
        ];
        $this->payment = new AlipaySdk($config);
    }

    /**
     * 设置插件订单
     *
     * @param Order $order
     * @return Alipay
     */
    public function setOrder(Order $order): self
    {
        $outTradeNo = $this->getUniqueOrderId($order);

        $total   = CurrencyService::getInstance()->convert($order->total, $order->currency, 'CNY');
        $total   = round($total, 2);
        $subject = system_setting('base.meta_title') . '-' . $order->number;
        $body    = $this->getOrderBody($order);

        $this->payRequestData = [
            'body'         => $body,
            'subject'      => str_replace('&', '-', $subject),
            'total_amount' => $total,
            'out_trade_no' => $outTradeNo,
            'product_code' => 'FAST_INSTANT_TRADE_PAY',
        ];

        Log::info('payRequestData: ', $this->payRequestData);

        return $this;
    }

    /**
     * 获取订单信息
     *
     * @param $order
     * @return string
     */
    private function getOrderBody($order): string
    {
        return $order->number . '-' . $order->orderProducts->first()->name;
    }

    /**
     * 获取支付链接
     *
     * @return array|string
     */
    public function getPayLink(): array|string
    {
        $payLink = $this->payment->pagePay($this->payRequestData, 'GET');
        Log::info("URL: {$payLink}");

        return $payLink;
    }
}
