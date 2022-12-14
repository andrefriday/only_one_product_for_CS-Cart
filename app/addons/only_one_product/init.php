<?php

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

fn_register_hooks(
    'pre_add_to_cart',
    'change_order_status_before_update_product_amount'
);
