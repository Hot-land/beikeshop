<?php
/**
 * shop.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2023-02-18 14:17:53
 * @modified   2023-02-18 14:17:53
 */

use Illuminate\Support\Facades\Route;
use Plugin\GlobalAlipay\Controllers\CallbackController;

Route::get('/callback/alipay/return', [CallbackController::class, 'return'])->name('alipay.return');
Route::post('/callback/alipay/notify', [CallbackController::class, 'notify'])->name('alipay.notify');
