<?php
if (class_exists("WC_Payment_Gateway")) {
    class WC_Monnify_Payment_Gateway extends WC_Payment_Gateway_CC
    {
        /**
         * API public key
         *
         * @var string
         */
        public $public_key;

        /**
         * Is test mode active?
         *
         * @var bool
         */
        public $testmode;

        /**
         * API secret key
         *
         * @var string
         */
        public $secret_key;

        /**
         * Monnify test public key.
         *
         * @var string
         */
        public $test_public_key;

        /**
         * Monnify test secret key.
         *
         * @var string
         */
        public $test_secret_key;

        /**
         * Monnify live public key.
         *
         * @var string
         */
        public $live_public_key;

        /**
         * Monnify Contracts key.
         *
         * @var string
         */

        public $contracts;
        /**
         * Monnify Contracts Test key.
         *
         * @var string
         */
        public $contracts_test;

        /**
         * Monnify Contracts Live key.
         *
         * @var string
         */
        public $contracts_live;

        /**
         * Monnify live secret key.
         *
         * @var string
         */
        public $live_secret_key;

        /**
         * Monnify contract code.
         *
         * @var string
         */
        public $contractCode;

        /**
         * Monnify APIURL.
         *
         * @var string
         */
        public $apiURL;

        /**
         * saved_cards
         * 
         */
        public $saved_cards;

        /**
         * remove_cancel_order_button
         * 
         */
        public $remove_cancel_order_button;


        /**
         * Constructor
         */
        public function __construct()
        {
            $this->id = "monnify";
            // $this->icon = apply_filters("woocommerce_monnify_icon", plugins_url( 'assets/images/monnify.png', WC_MONNIFY_MAIN_FILE ));
            $this->has_fields = true;
            $this->method_title = __("Monnify Payment", "wc-monnify-payment-gateway");
            $this->method_description = sprintf(__('Monnify provide merchants with the tools and services needed to accept online payments from local and international customers using Mastercard, Visa, Verve Cards <a href="%1$s" target="_blank">Get your API keys</a>.', 'wc-monnify-payment-gateway'), 'https://app.monnify.com/developer');
            $this->supports = array(
                'products',
                'tokenization',
                'subscriptions',
                'subscription_cancellation',
                'subscription_suspension',
                'subscription_reactivation',
                'subscription_amount_changes',
                'subscription_date_changes',
                'subscription_payment_method_change',
                'subscription_payment_method_change_customer',
                'subscription_payment_method_change_admin',
                'multiple_subscriptions',
            );
            // Load the form fields
            $this->init_form_fields();
            // Load the settings
            $this->init_settings();
            //Load 
            $this->title       = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled     = $this->get_option('enabled');

            $this->saved_cards = false; //not applicable for now

            $this->remove_cancel_order_button = false; //not applicable for now

            $this->test_public_key = $this->get_option('test_public_key');
            $this->test_secret_key = $this->get_option('test_secret_key');

            $this->live_public_key = $this->get_option('live_public_key');
            $this->live_secret_key = $this->get_option('live_secret_key');

            $this->contracts_test = $this->get_option('test_contracts_key');
            $this->contracts_live = $this->get_option('live_contracts_key');

            $this->testmode    = $this->get_option('testmode') === 'yes' ? true : false;

            $this->contractCode = $this->testmode ? $this->contracts_test : $this->contracts_live;
            $this->apiURL = $this->testmode ? "https://sandbox.monnify.com/api/" : "https://api.monnify.com/api/";

            $this->contracts = $this->testmode ? $this->contracts_test : $this->contracts_live;

            $this->public_key = $this->testmode ? $this->test_public_key : $this->live_public_key;
            $this->secret_key = $this->testmode ? $this->test_secret_key : $this->live_secret_key;
            // Hooks
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
            add_action('woocommerce_available_payment_gateways', array($this, 'add_gateway_to_checkout'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
            add_action('woocommerce_api_wc_monnify_payment_gateway', array($this, 'monnify_verify_payment'));
            add_action(
                'woocommerce_update_options_payment_gateways_' . $this->id,
                array(
                    $this,
                    'process_admin_options',
                )
            );
            // Check if the gateway can be used.
            if (!$this->is_valid_for_use()) {
                $this->enabled = false;
            }
        }

        public function init_form_fields()
        {
            $form_fields = apply_filters(
                "woo_ade_pay_fields",
                array(
                    "enabled" => array(
                        "title" => __("Enable/Disable", "wc-monnify-payment-gateway"),
                        "type" => "checkbox",
                        "label" => __("Enable or Disable Ade Monnify Payment", "wc-monnify-payment-gateway"),
                        "default" => "no"
                    ),
                    "title" => array(
                        "title" => __("Title", "wc-monnify-payment-gateway"),
                        "type" => "text",
                        "description" => __("This controls the payment method title which the user sees during checkout.", "wc-monnify-payment-gateway"),
                        "default" => __("Monnify Payment", "wc-monnify-payment-gateway"),
                        "desc_tip" => true
                    ),
                    "description" => array(
                        "title" => __("Payment Description", "wc-monnify-payment-gateway"),
                        "type" => "textarea",
                        "description" => __("Add a new description", "wc-monnify-payment-gateway"),
                        "default" => __("Accept payments seamlessly via card, account transfers, etc. using Monnify payment gateway.", "wc-monnify-payment-gateway"),
                        "desc_tip" => true
                    ),
                    "instructions" => array(
                        "title" => __("Instructions", "wc-monnify-payment-gateway"),
                        "type" => "textarea",
                        "description" => __("Instructions that will be added to the thank you page."),
                        "default" => __("", "wc-monnify-payment-gateway"),
                        "desc_tip" => true
                    ),
                    'testmode'                         => array(
                        'title'       => __('Test mode', 'wc-monnify-payment-gateway'),
                        'label'       => __('Enable Test Mode', 'wc-monnify-payment-gateway'),
                        'type'        => 'checkbox',
                        'description' => __('Test mode enables you to test payments before going live. <br />Once the LIVE MODE is enabled on your Monnify account uncheck this.', 'wc-monnify-payment-gateway'),
                        'default'     => 'yes',
                        'desc_tip'    => true,
                    ),
                    'test_secret_key'                  => array(
                        'title'       => __('Test Secret Key', 'wc-monnify-payment-gateway'),
                        'type'        => 'text',
                        'description' => __('Enter your Test Secret Key here', 'wc-monnify-payment-gateway'),
                        'default'     => '',
                    ),
                    'test_public_key'                  => array(
                        'title'       => __('Test Public Key', 'wc-monnify-payment-gateway'),
                        'type'        => 'text',
                        'description' => __('Enter your Test Public Key here.', 'wc-monnify-payment-gateway'),
                        'default'     => '',
                    ),
                    'test_contracts_key'                  => array(
                        'title'       => __('Test Contracts Key', 'wc-monnify-payment-gateway'),
                        'type'        => 'text',
                        'description' => __('Enter your Test Contracts Key here.', 'wc-monnify-payment-gateway'),
                        'default'     => '',
                    ),
                    'live_secret_key'                  => array(
                        'title'       => __('Live Secret Key', 'wc-monnify-payment-gateway'),
                        'type'        => 'text',
                        'description' => __('Enter your Live Secret Key here.', 'wc-monnify-payment-gateway'),
                        'default'     => '',
                    ),
                    'live_public_key'                  => array(
                        'title'       => __('Live Public Key', 'wc-monnify-payment-gateway'),
                        'type'        => 'text',
                        'description' => __('Enter your Live Public Key here.', 'wc-monnify-payment-gateway'),
                        'default'     => '',
                    ),
                    'live_contracts_key'                  => array(
                        'title'       => __('Live Contracts Key', 'wc-monnify-payment-gateway'),
                        'type'        => 'text',
                        'description' => __('Enter your Live Contracts Key here.', 'wc-monnify-payment-gateway'),
                        'default'     => '',
                    ),
                    // Add new field for Order Status After Payment
                    'order_status_after_payment' => array(
                        'title'       => __('Order Status After Payment', 'wc-monnify-payment-gateway'),
                        'type'        => 'select',
                        'description' => __('Select the default order status after a successful payment.', 'wc-monnify-payment-gateway'),
                        'default'     => 'processing',
                        'options'     => array(
                            'processing' => __('Processing', 'wc-monnify-payment-gateway'),
                            'completed'  => __('Completed', 'wc-monnify-payment-gateway')
                        ),
                    ),
                )
            );

            $this->form_fields = $form_fields;
        }

        /**
         * Get Paystack payment icon URL.
         */
        public function get_logo_url()
        {
            $url = WC_HTTPS::force_https_url(WC_MONNIFY_URL . '/assets/images/monnify.png');
            return apply_filters('woocommerce_monnify_icon', $url, $this->id);
        }

        /**
         * Payment form on checkout page
         */
        public function payment_fields()
        {

            if ($this->description) {
                echo wpautop(wptexturize($this->description));
            }

            if (!is_ssl()) {
                return;
            }

            if ($this->supports('tokenization') && is_checkout() && $this->saved_cards && is_user_logged_in()) {
                $this->tokenization_script();
                $this->saved_payment_methods();
                $this->save_payment_method_checkbox();
            }
        }


        /**
         * Display monnify payment icon.
         */
        public function get_icon()
        {

            $icon = '<img src="' . WC_HTTPS::force_https_url(plugins_url('assets/images/monnify.png', WC_MONNIFY_MAIN_FILE)) . '" alt="Monnify Payment Options" style="height: 20px;" />';

            return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
        }

        /**
         * Displays the payment page.
         *
         * @param $order_id
         */
        public function receipt_page($order_id)
        {

            $order = wc_get_order($order_id);

            echo '<div id="yes-add">' . __('Thank you for your order, please click the button below to pay with Monnify.', 'wc-monnify-payment-gateway') . '</div>';

            echo '<div id="monnify_form"><form id="order_review" method="post" action="' . WC()->api_request_url('WC_Monnify_Payment_Gateway') . '"></form><button class="button alt" id="wc-monnify-payment-gateway-button">' . __('Pay Now', 'wc-monnify-payment-gateway') . '</button>';

            if (!$this->remove_cancel_order_button) {
                echo '  <a class="button cancel" id="cancel-btn" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Cancel order &amp; restore cart', 'wc-monnify-payment-gateway') . '</a></div>';
            }
        }

        /**
         * Verify Monnify payment.
         */
        public function monnify_verify_payment()
        {

            //If transactions_refrence is not set
            if (isset($_GET["transactions_refrence"]) && $_GET["transactions_refrence"] != "undefined" && $_GET["transactions_refrence"] != "") {
                //DO More
                if (isset($_GET['monnify_id']) && urldecode($_GET['monnify_id'])) {
                    $order_id = sanitize_text_field(urldecode($_GET['monnify_id']));
                    $transactions_refrence = sanitize_text_field($_GET['transactions_refrence']);

                    if (!$order_id) {
                        $order_id = sanitize_text_field(urldecode($_GET['monnify_id']));
                    }
                    //Get Order
                    $order = wc_get_order($order_id);
                    //Then make http request for transaction verification
                    $monnify_url = $this->apiURL . 'v1/auth/login';

                    $headers = array(
                        'Authorization' => 'Basic ' . base64_encode($this->public_key . ":" . $this->secret_key),
                    );

                    $args = array(
                        'headers' => $headers,
                        'timeout' => 60,
                    );
                    //Query
                    $request = wp_remote_post($monnify_url, $args);
                    //Log
                    if (!is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {
                        //Access More Resources
                        $monnify_response = json_decode(wp_remote_retrieve_body($request));
                        $complex_token =  $monnify_response->responseBody->accessToken;
                        //Verify Payment
                        $monnify_url_v = $this->apiURL . 'v2/transactions/' . urlencode($transactions_refrence);
                        $headers_v = array(
                            'Authorization' => 'Bearer ' . $complex_token,
                        );

                        $args_v = array(
                            'headers' => $headers_v,
                            'timeout' => 60,
                        );
                        //Query
                        $request_v = wp_remote_get($monnify_url_v, $args_v);
                        //log 
                        if (!is_wp_error($request_v) && 200 === wp_remote_retrieve_response_code($request_v)) {
                            $monnify_response_v = json_decode(wp_remote_retrieve_body($request_v));
                            if ($monnify_response_v->responseBody->paymentStatus === "PAID") {
                                //CLear Order
                                $order->payment_complete($transactions_refrence);
                                // $order->update_status( 'completed' );

                                // Retrieve the admin setting for order status after payment
                                $order_status_after_payment = $this->get_option('order_status_after_payment');

                                // Update order status based on the setting
                                if ($order_status_after_payment == 'completed') {
                                    $order->update_status('completed', __('Payment received, your order is now complete.', 'wc-monnify-payment-gateway'));
                                } else {
                                    $order->update_status('processing', __('Payment received, your order is currently being processed.', 'wc-monnify-payment-gateway'));
                                }


                                $order->add_order_note('Payment was successful on Monnify');
                                $order->add_order_note(sprintf(__('Payment via Monnify successful (Transaction Reference: %s)', 'wc-monnify-payment-gateway'), $transactions_refrence));
                                //Customer Note
                                $customer_note  = 'Thank you for your order.<br>';
                                $customer_note .= 'Your payment was successful, we are now <strong>processing</strong> your order.';

                                $order->add_order_note($customer_note, 1);

                                wc_add_notice($customer_note, 'notice');
                                //CLear Cart
                                WC()->cart->empty_cart();
                            };
                        } else {
                            //If error
                            $order->update_status('Failed');

                            update_post_meta($order_id, '_transaction_id', $transactions_refrence);

                            $notice      = sprintf(__('Thank you for shopping with us.%1$sYour payment is currently having issues with verification and .%1$sYour order is currently on-hold.%2$sKindly contact us for more information regarding your order and payment status.', 'wc-monnify-payment-gateway'), '<br />', '<br />');
                            $notice_type = 'notice';

                            // Add Customer Order Note
                            $order->add_order_note($notice, 1);

                            // Add Admin Order Note
                            $admin_order_note = sprintf(__('<strong>Look into this order</strong>%1$sThis order is currently on hold.%2$sReason: Payment can not be verified.%3$swhile the <strong>Monnify Transaction Reference:</strong> %4$s', 'wc-monnify-payment-gateway'), '<br />', '<br />', '<br />', $transactions_refrence);
                            $order->add_order_note($admin_order_note);

                            function_exists('wc_reduce_stock_levels') ? wc_reduce_stock_levels($order_id) : $order->reduce_order_stock();

                            wc_add_notice($notice, $notice_type);

                            exit;
                        }
                    }
                    //Quit and redirect
                    wp_redirect($this->get_return_url($order));
                    exit;
                }
            }

            wp_redirect(wc_get_page_permalink('cart'));

            exit;
        }

        /**
         * Process the payment.
         *
         * @param int $order_id
         *
         * @return array|void
         */
        public function process_payment($order_id)
        {

            if (is_user_logged_in() && isset($_POST['wc-' . $this->id . '-new-payment-method']) && true === (bool)
            $_POST['wc-' . $this->id . '-new-payment-method'] && $this->saved_cards) {

                update_post_meta($order_id, '_wc_monnify_save_card', true);
            }

            $order = wc_get_order($order_id);

            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true),
            );
        }

        /**
         * Check if this gateway is enabled and available in the user's country.
         */
        public function is_valid_for_use()
        {

            if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_monnify_supported_currencies', array('NGN', 'USD', 'ZAR', 'GHS')))) {

                $msg = sprintf(__('Monnify does not support your store currency. Kindly set it to either NGN (&#8358), GHS (&#x20b5;), USD (&#36;) or ZAR (R) <a href="%s">here</a>', 'wc-monnify-payment-gateway'), admin_url('admin.php?page=wc-settings&tab=general'));

                WC_Admin_Settings::add_error($msg);

                return false;
            }

            return true;
        }

        /**
         * Load admin scripts.
         */
        public function admin_scripts()
        {

            if ('woocommerce_page_wc-settings' !== get_current_screen()->id) {
                return;
            }

            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '';

            $monnify_admin_params = array(
                'plugin_url' => WC_MONNIFY_URL,
            );

            wp_enqueue_script('wc_monnify_admin', plugins_url('assets/js/monnify-admin' . $suffix . '.js', WC_MONNIFY_MAIN_FILE), array(), WC_MONNIFY_VERSION, true);

            wp_localize_script('wc_monnify_admin', 'wc_monnify_admin_params', $monnify_admin_params);
        }

        /**
         * Check if monnify merchant details is filled.
         */
        public function admin_notices()
        {

            if ($this->enabled == 'no') {
                return;
            }

            // Check required fields.
            if (!($this->public_key && $this->secret_key)) {
                echo '<div class="error"><p>' . sprintf(__('Please enter your Monnify merchant details <a href="%s">here</a> to be able to use the Monnify WooCommerce plugin.', 'wc-monnify-payment-gateway'), admin_url('admin.php?page=wc-settings&tab=checkout&section=monnify')) . '</p></div>';
                return;
            }
        }

        /**
         * Check if Monnify gateway is enabled.
         *
         * @return bool
         */
        public function is_available()
        {

            if ('yes' == $this->enabled) {

                if (!($this->public_key && $this->secret_key)) {

                    return false;
                }

                return true;
            }

            return false;
        }

        /**
         * Outputs scripts used for monnify payment.
         */
        public function payment_scripts()
        {

            if (!is_checkout_pay_page()) {
                return;
            }

            if ($this->enabled === 'no') {
                return;
            }

            $order_key = sanitize_text_field(urldecode($_GET['key']));
            $order_id  = absint(get_query_var('order-pay'));

            $order = wc_get_order($order_id);
            $api_verify_url = WC()->api_request_url('WC_Monnify_Payment_Gateway') . '?monnify_id=' . $order_id;

            $payment_method = method_exists($order, 'get_payment_method') ? $order->get_payment_method() : $order->payment_method;

            if ($this->id !== $payment_method) {
                return;
            }

            wp_enqueue_script('jquery');

            wp_enqueue_script('monnify', 'https://sdk.monnify.com/plugin/monnify.js', array('jquery'), WC_MONNIFY_VERSION, false);

            wp_enqueue_script('wc_monnify', plugins_url('assets/js/monnify.js', WC_MONNIFY_MAIN_FILE), array('jquery', 'monnify'), WC_MONNIFY_VERSION, false);

            $monnify_params = array(
                'key' => $this->public_key,
                'contractCode' => $this->contractCode,
                'testmode' => $this->testmode,
                'api_verify_url' => $api_verify_url
            );

            if (is_checkout_pay_page() && get_query_var('order-pay')) {

                $email         = method_exists($order, 'get_billing_email') ? $order->get_billing_email() : $order->billing_email;
                $amount        = $order->get_total();
                $txnref        = $order_id . '_' . time();
                $the_order_id  = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
                $the_order_key = method_exists($order, 'get_order_key') ? $order->get_order_key() : $order->order_key;
                $currency      = method_exists($order, 'get_currency') ? $order->get_currency() : $order->order_currency;

                if ($the_order_id == $order_id && $the_order_key == $order_key) {

                    $monnify_params['email']        = $email;
                    $monnify_params['amount']       = $amount;
                    $monnify_params['txnref']       = $txnref;
                    $monnify_params['currency']     = $currency;
                    $monnify_params['bank_channel'] = 'true';
                    $monnify_params['card_channel'] = 'true';
                    $monnify_params['first_name'] = $order->get_billing_first_name();
                    $monnify_params['last_name'] = $order->get_billing_last_name();
                    $monnify_params['phone'] = $order->get_billing_phone();
                    $monnify_params['card_channel'] = 'true';
                }
                update_post_meta($order_id, '_monnify_txn_ref', $txnref);
            }

            wp_localize_script('wc_monnify', 'wc_monnify_params', $monnify_params);
        }

        /**
         * Add Gateway to checkout page.
         *
         * @param $available_gateways
         *
         * @return mixed
         */
        public function add_gateway_to_checkout($available_gateways)
        {

            if ('no' == $this->enabled) {
                unset($available_gateways[$this->id]);
            }

            return $available_gateways;
        }
    }
}
