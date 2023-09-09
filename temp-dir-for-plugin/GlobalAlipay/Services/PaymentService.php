<?php
/**
 * PaymentService.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2022-12-06 14:38:55
 * @modified   2022-12-06 14:38:55
 */

namespace Plugin\GlobalAlipay\Services;

use Beike\Models\Order;
use Plugin\GlobalAlipay\Services\Payment\Alipay;

class PaymentService
{
    private Order $order;

    /**
     * PaymentService constructor.
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * 获取当前实例
     * @param Order $order
     * @return PaymentService
     */
    public static function getInstance(Order $order)
    {
        return new self($order);
    }

    /**
     * 获取支付链接
     *
     * @return array|string
     * @throws \Exception
     */
    public function getPayLink(): array|string
    {
        $order       = $this->order;
        $paymentCode = $order->payment_method_code;
        $payLink     = '';

        if ($paymentCode == 'global_alipay') {
            $alipay  = Alipay::getInstance()->setOrder($order);
            $payLink = $alipay->getPayLink();
        }

        return $payLink;
    }
}
