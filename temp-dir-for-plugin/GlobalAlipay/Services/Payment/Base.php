<?php
/**
 * Base.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2022-12-06 14:43:50
 * @modified   2022-12-06 14:43:50
 */

namespace Plugin\GlobalAlipay\Services\Payment;

class Base
{
    protected function getUniqueOrderId($order): string
    {
        return $order->id . '-' . time();
    }

    public function getOriginalOrderId($outTradeNo)
    {
        $orderIds = explode('-', $outTradeNo);
        if (is_array($orderIds) && isset($orderIds[0])) {
            return $orderIds[0];
        }

        return 0;
    }
}
