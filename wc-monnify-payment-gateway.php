<?php

/**
 * Plugin Name: Payment Gateway for Monnify on WooCommerce
 * Plugin URI: https://www.monnify.com/
 * Author: Adeleye Ayodeji
 * Author URI: http://adeleyeayodeji.com/
 * Description: WooCommerce payment gateway for Monnify
 * Version: 1.0.9
 * License: 1.0.9
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: wc-monnify-payment-gateway
 */
if (!defined('ABSPATH')) {
    exit("You can not access file directly");
}

if (!in_array("woocommerce/woocommerce.php", apply_filters("active_plugins", get_option("active_plugins")))) return;

define("WC_MONNIFY_VERSION", "1.0.2");
define('WC_MONNIFY_MAIN_FILE', __FILE__);
define('WC_MONNIFY_URL', untrailingslashit(plugins_url('/', __FILE__)));

add_action("plugins_loaded", "monnify_method_init", 999);

//Methods
function monnify_method_init()
{
    //Init  class
    require_once dirname(__FILE__) . '/includes/class-wc-gateway-monnify.php';

    //Notice user
    add_action('admin_notices', 'ade_wc_monnify_testmode_notice');

    //Admin URL
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ade_woo_monnify_plugin_action_links');

    add_filter("woocommerce_payment_gateways", "monnify_method_init_payment_gateway");
}


function monnify_method_init_payment_gateway($gateways)
{
    $gateways[] = "WC_Monnify_Payment_Gateway";
    return $gateways;
}

/**
 * Display the test mode notice.
 **/
function ade_wc_monnify_testmode_notice()
{

    $monnify_settings = get_option('woocommerce_monnify_settings');
    $test_mode = isset($monnify_settings['testmode']) ? $monnify_settings['testmode'] : '';

    if ('yes' === $test_mode) {
        echo '<div class="error">
        <p>' . sprintf(__('Monnify Payment test mode is still enabled, Click <strong><a
                    href="%s">here</a></strong> to
            disable it when you want to start accepting live payment on your site.', 'wc-monnify-payment-gateway'), esc_url(
            admin_url('admin.php?page=wc-settings&tab=checkout&section=monnify')
        )) . '</p>
    </div>';
    }
}

/**
 * Add Settings link to the plugin entry in the plugins menu.
 *
 * @param array $links Plugin action links.
 *
 * @return array
 **/
function ade_woo_monnify_plugin_action_links($links)
{

    $settings_link = array(
        'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=monnify') . '"
        title="' . __('View Monnify WooCommerce Settings', 'wc-monnify-payment-gateway') . '">' . __(
            'Settings',
            'wc-monnify-payment-gateway'
        ) . '</a>',
    );

    return array_merge($settings_link, $links);
}

add_action(
    'before_woocommerce_init',
    function () {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }
);


function monnify_gateway_block_support()
{

    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    // here we're including our "gateway block support class"
    require_once __DIR__ . '/includes/class-wc-gateway-monnify-blocks-support.php';

    // registering our block support class
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new WC_Monnify_Payment_Gateway_Blocks_Support);
        }
    );
}

/**
 *  Register our block support class when WooCommerce Blocks are loaded.
 * 
 */
add_action('woocommerce_blocks_loaded', 'monnify_gateway_block_support');
