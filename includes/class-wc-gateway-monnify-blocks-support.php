<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;

final class WC_Monnify_Payment_Gateway_Blocks_Support extends AbstractPaymentMethodType
{

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'monnify';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize()
	{
		$this->settings = get_option('woocommerce_monnify_settings', array());

		add_action('woocommerce_rest_checkout_process_payment_with_context', array($this, 'failed_payment_notice'), 8, 2);
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active()
	{
		$payment_gateways_class = WC()->payment_gateways();
		$payment_gateways       = $payment_gateways_class->payment_gateways();
		return $payment_gateways['monnify']->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles()
	{
		$asset_path   = plugin_dir_path(WC_MONNIFY_MAIN_FILE) . 'assets/js/block/block.asset.php';
		$version      = null;
		$dependencies = array();
		if (file_exists($asset_path)) {
			$asset        = require $asset_path;
			$version      = isset($asset['version']) ? $asset['version'] : $version;
			$dependencies = isset($asset['dependencies']) ? $asset['dependencies'] : $dependencies;
		}

		wp_register_script(
			'wc-monnify-blocks-integration',
			plugin_dir_url(WC_MONNIFY_MAIN_FILE) . 'assets/js/block/block.js',
			$dependencies,
			$version,
			true
		);

		//localize script
		wp_localize_script('wc-monnify-blocks-integration', 'wcMonnifyBlockData', array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('wc-monnify-blocks-integration'),
			'logo_url' => WC_MONNIFY_URL . '/assets/images/monnify.png',
		));

		return array('wc-monnify-blocks-integration');
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data()
	{
		$payment_gateways_class = WC()->payment_gateways();
		$payment_gateways       = $payment_gateways_class->payment_gateways();
		$gateway                = $payment_gateways['monnify'];

		return array(
			'title'             => $this->get_setting('title'),
			'description'       => $this->get_setting('description'),
			'supports'          => array_filter($gateway->supports, array($gateway, 'supports')),
			'allow_saved_cards' => $gateway->saved_cards && is_user_logged_in(),
			'logo_urls'         => array($payment_gateways['monnify']->get_logo_url()),
		);
	}

	/**
	 * Add failed payment notice to the payment details.
	 *
	 * @param PaymentContext $context Holds context for the payment.
	 * @param PaymentResult  $result  Result object for the payment.
	 */
	public function failed_payment_notice(PaymentContext $context, PaymentResult &$result)
	{
		if ('monnify' === $context->payment_method) {
			add_action(
				'wc_gateway_monnify_process_payment_error',
				function ($failed_notice) use (&$result) {
					$payment_details                 = $result->payment_details;
					$payment_details['errorMessage'] = wp_strip_all_tags($failed_notice);
					$result->set_payment_details($payment_details);
				}
			);
		}
	}
}
