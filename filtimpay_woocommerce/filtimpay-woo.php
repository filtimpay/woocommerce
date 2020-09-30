<?php
/*
 * Plugin Name: FiltimPay WooCommerce
 * Description: Плагин FiltimPay для использования оплаты в woocommerce
 * Version: 1.2.0
 * Author: Carlos Castaneda
 */

function add_filtimpay_gateway_class($methods)
{
    require_once(__DIR__ . '/includes/class-wc-gateway-filtimpay.php');
    $methods[] = 'WC_Gateway_FiltimPay';
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_filtimpay_gateway_class');
