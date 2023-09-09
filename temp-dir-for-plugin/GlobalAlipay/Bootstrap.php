<?php
/**
 * Bootstrap.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2023-02-14 20:50:01
 * @modified   2023-02-14 20:50:01
 */

namespace Plugin\GlobalAlipay;

use Plugin\GlobalAlipay\Services\PaymentService;

class Bootstrap
{
    public function boot()
    {
        $this->beforeOrderPay();
    }

    public function beforeOrderPay()
    {
        add_hook_action('account.order.pay.before', function ($data) {
            $order  = $data['order'];
            $payUrl = PaymentService::getInstance($order)->getPayLink();
            if ($payUrl) {
                header("Location: $payUrl");
                exit;
            }
        });
    }
}
