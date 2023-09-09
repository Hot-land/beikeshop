<?php
/**
 * AlipayController.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2023-02-18 14:33:44
 * @modified   2023-02-18 14:33:44
 */

namespace Plugin\GlobalAlipay\Controllers;

use App\Http\Controllers\Controller;
use Beike\Models\Order;
use Beike\Services\StateMachineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Plugin\GlobalAlipay\Services\Payment\Alipay as AlipayService;

class CallbackController extends Controller
{
    /**
     * 支付宝同步回调
     * 支付成功后支付宝跳转页面
     */
    public function return(Request $request): string
    {
        Log::info('Alipay Return Start =========');
        Log::info("alipay_return: ip {$request->getClientIp()}");
        Log::info('alipay_return: request_data:', $request->all());

        $alipay  = AlipayService::getInstance();
        $payment = $alipay->payment;

        try {
            $orderId = $alipay->getOriginalOrderId($request->get('out_trade_no'));
            $order   = Order::query()->find($orderId);

            if (! $order) {
                return '订单不存在';
            }
            if ($payment->check($request->all())) {
                $url = shop_route('account.order.show', ['number' => $order->number]);

                return redirect($url)->with('success', 'Paid successfully');
            }

            return '支付失败';
        } catch (\Exception $e) {
            return "支付失败: IP: {$request->getClientIp()} -- {$e->getMessage()}";
        }
    }

    /**
     * 支付宝异步回调
     * 支付成功后会从支付宝服务器通知到该地址
     * @throws \Exception|\Throwable
     */
    public function notify(Request $request): string
    {
        Log::info('Alipay Notify Start =========');
        Log::info("alipay_notify: ip {$request->getClientIp()}");
        Log::info('alipay_notify: request_data:');
        Log::info($request->getContent());

        $alipay  = AlipayService::getInstance();
        $payment = $alipay->payment;

        try {
            $orderId = $alipay->getOriginalOrderId($request->get('out_trade_no'));
            $order   = Order::query()->find($orderId);

            if (! $order) {
                Log::info('alipay_notify: order not exist!');

                return 'fail';
            }
            Log::info('alipay_notify: order_id --' . $order->id);

            if (! $payment->check($request->all())) {
                Log::notice('alipay_notify: Alipay notify post data verification fail.', [
                    'data' => $request->getContent(),
                ]);

                return 'fail';
            }

            switch ($request->get('trade_status')) {
                case 'TRADE_SUCCESS':
                    Log::info('alipay_notify: trade success');
                    StateMachineService::getInstance($order)->changeStatus('paid', '订单已支付', true);

                    break;
                case 'TRADE_FINISHED':
                    break;
            }

            return 'success';
        } catch (\Exception $e) {
            $message = "alipay_notify_notify: IP: {$request->getClientIp()} -- {$e->getMessage()}";
            Log::error($message);

            return 'fail';
        }
    }
}
