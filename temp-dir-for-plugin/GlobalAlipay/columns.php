<?php
/**
 * columns.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2023-02-14 20:49:50
 * @modified   2023-02-14 20:49:50
 */

return [
    [
        'name'      => 'app_id',
        'label_key' => 'common.app_id',
        'type'      => 'string',
        'required'  => true,
    ],
    [
        'name'      => 'merchant_private_key',
        'label_key' => 'common.merchant_private_key',
        'type'      => 'textarea',
        'required'  => true,
    ],
    [
        'name'      => 'alipay_public_key',
        'label_key' => 'common.alipay_public_key',
        'type'      => 'textarea',
        'required'  => true,
    ],
    [
        'name'      => 'log',
        'label_key' => 'common.log',
        'type'      => 'select',
        'options'   => [
            ['value' => 'active', 'label_key' => 'common.active'],
            ['value' => 'inactive', 'label_key' => 'common.inactive'],
        ],
        'required'  => true,
    ],
];
