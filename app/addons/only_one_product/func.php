<?php

use Tygh\Registry;
use Tygh\Enum\OrderStatuses;
use Tygh\Addons\ProductVariations\ServiceProvider;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

function fn_only_one_product($product_id)
{
    $only_one_product_ids = db_get_fields('SELECT product_id FROM ?:products WHERE only_one_product = ?s', 'Y');

    if (in_array($product_id, $only_one_product_ids)) {
        return true;
    }
}

function fn_only_one_product_pre_add_to_cart(&$product_data, &$cart, &$auth, &$update)
{
    foreach ($product_data as $key => $product) {
        $product_id = !empty($product['product_id']) ? $product['product_id'] : $key;
    }
    if (fn_only_one_product($product_id)) {
        foreach ($cart['products'] as $item_id => $product_in_cart) {
            if (Registry::get('addons.product_variations.status') === 'A') {
                $product_id_map = ServiceProvider::getProductIdMap();
                $product_parent_ids = $product_id_map->getVariationSubGroupProductIds($product_id);
                $variation_product_in_cart = in_array($product_in_cart['product_id'], $product_parent_ids);
                if ($product_parent_ids && $variation_product_in_cart) {
                    $only_one_product = true;
                    $message = __('product_in_cart');
                }
            }
            if ($product_in_cart['product_id'] == $product_id) {
                $only_one_product = true;
                $message = __('product_in_cart');
            }
        }

        if ($auth['user_id']) {
            $products_in_orders = db_get_fields('SELECT ?:order_details.product_id FROM ?:orders, ?:order_details WHERE ?:orders.order_id = ?:order_details.order_id AND ?:orders.user_id = ?i AND ?:orders.status != ?s', $auth['user_id'], 'I');
            if (in_array($product_id, $products_in_orders)) {
                $only_one_product = true;
                $message = __('you_have_already_bought_this_product');
            }

            if (Registry::get('addons.product_variations.status') === 'A') {
                $product_id_map = ServiceProvider::getProductIdMap();
                $product_parent_ids = $product_id_map->getVariationSubGroupProductIds($product_id);
                $variation_product_in_orders = array_intersect($products_in_orders, $product_parent_ids);
                if ($product_parent_ids && $variation_product_in_orders) {
                    $only_one_product = true;
                    $message = __('you_have_already_bought_this_product');
                }
            }
        }
        if ($only_one_product) {
            fn_set_notification('E', __('error'), $message);
            $product_data = [];
        }
    }
}

function fn_only_one_product_change_order_status_before_update_product_amount(
    $order_id,
    $status_to,
    $status_from,
    $force_notification,
    $place_order,
    $order_info,
    $k,
    $v
) {

    if (fn_only_one_product($v['product_id'])) {
        if (($status_to === OrderStatuses::CANCELED &&
                fn_get_status_param_value($status_to, 'inventory', $type = STATUSES_ORDER) === 'D') &&
            (fn_get_status_param_value($status_from, 'inventory', $type = STATUSES_ORDER) === 'D' ||
                fn_get_status_param_value($status_from, 'inventory', $type = STATUSES_ORDER) === 'I')
        ) {
            fn_update_product_amount(
                $v['product_id'],
                $v['amount'],
                $v['extra']['product_options'],
                '+',
                $force_notification !== false,
                $order_info
            );
        } elseif (($status_from === OrderStatuses::CANCELED &&
                fn_get_status_param_value($status_from, 'inventory', $type = STATUSES_ORDER) === 'D') &&
            (fn_get_status_param_value($status_to, 'inventory', $type = STATUSES_ORDER) === 'D' ||
                fn_get_status_param_value($status_to, 'inventory', $type = STATUSES_ORDER) === 'I')
        ) {
            fn_update_product_amount(
                $v['product_id'],
                $v['amount'],
                $v['extra']['product_options'],
                '-',
                $force_notification !== false,
                $order_info
            );
        }
    }
}
