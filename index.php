<?php

/**
 * Plugin Name: Uddokta Wallet
 * Plugin URI: https://wordpress.org/plugins/uddokta-pay
 * Plugin URI: uddokta-pay
 * Description: Integration of Uddokta Pay
 * Version: 1.0.0
 * Author: Uddokta Wallet
 * Author URI: https://github.com/uddoktawallet/uddoktapay.git
 * License: GPL2
 */

 
require_once 'functions.php';

function uddokta_pay_requires()
{
    $plugin_name = 'WooCommerce';

    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        $plugin_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $plugin_name), 'install-plugin_' . $plugin_name);
        $message = sprintf(__('Uddokta Pay requires ' . $plugin_name . '. Install %s.', 'your-text-domain'), '<a href="' . $plugin_url . '">' . $plugin_name . '</a>');
        echo '<div class="notice notice-info is-dismissible"><p>' . $message . '</p></div>';
    }
}
add_action('admin_notices', 'uddokta_pay_requires');



/**
 * Include the WooCommerce core functions
 */
if (!function_exists('WC')) {
    $woocommerce_path = '/woocommerce/woocommerce.php';
    $woocommerce_found = false;

    // Check if WooCommerce is installed as a plugin
    if (file_exists(WP_PLUGIN_DIR . $woocommerce_path)) {
        require_once WP_PLUGIN_DIR . $woocommerce_path;
        $woocommerce_found = true;
    }

    // Check if WooCommerce is installed as a theme
    if (!$woocommerce_found && file_exists(WP_CONTENT_DIR . '/themes/' . get_template() . $woocommerce_path)) {
        require_once WP_CONTENT_DIR . '/themes/' . get_template() . $woocommerce_path;
        $woocommerce_found = true;
    }

    // If WooCommerce is not found, throw an error message
    if (!$woocommerce_found) {
        throw new Exception('WooCommerce is required to use this plugin.');
    }
}



class Uddokta_Payment_Gateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'uddokta_payment_gateway';
        $this->title = 'Uddokta Payment Gateway';
        $this->description = 'Pay with Uddokta Payment Gateway.';
        $this->icon = plugin_dir_url(__FILE__) . 'assets/favicon.png';
        $this->init_form_fields();
        $this->init_settings();
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }



    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Enable/Disable', 'woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable Uddokta Payment Gateway', 'woocommerce'),
                'default' => 'no'
            ),
            'uddokta_public_key' => array(
                'title'       => __('Public Key', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Public key provided by Uddokta Payment Getway.', 'woocommerce'),
                'default'     => __('Uddokta Payment Gateway', 'woocommerce'),
                'desc_tip'    => true
            ),
            'uddokta_secret_key' => array(
                'title'       => __('Secret Key', 'woocommerce'),
                'type'        => 'password',
                'description' => __('Secret key provided by Uddokta Payment Getway.', 'woocommerce'),
                'default'     => __('Pay with Uddokta Payment Gateway.', 'woocommerce'),
                'desc_tip'    => true,
            ),
        );
    }

    public function getLogo()
    {
        if (has_custom_logo()) {
            return wp_get_attachment_image_src(get_theme_mod('custom_logo'), 'full')[0];
        } else {
            return "https://fakeimg.pl/350x200/?text=Website+Name";
        }
    }

    public function process_payment($order_id)
    {

        $order = wc_get_order($order_id);
        $order->update_status('pending', __('Awaiting payment confirmation.', 'woocommerce'));


        $parameters = [
            'identifier' => $order_id,
            'currency' => $order->currency,
            'amount' => $order->total,
            'details' => 'Online Shopping',
            'ipn_url' => site_url() . '?consumer=uddoktapay&target=ipn',
            'cancel_url' => site_url() . '/checkout',
            'success_url' => site_url() . '?order_id=' . $order_id . '&consumer=uddoktapay&target=success',
            'public_key' => get_option('woocommerce_uddokta_payment_gateway_settings')['uddokta_public_key'],
            'site_logo' => $this->getLogo(),
            'checkout_theme' => 'dark',
            'customer_name' =>  'Customer Name',
            'customer_email' => 'abcconnect@gmail.com',

        ];

        $url = "https://uddokta.store/payment/initiate";

        // $url = "https://uddokta.store/sandbox/payment/initiate";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS,  $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = json_decode(curl_exec($ch));
        curl_close($ch);

        return array(
            'result'   => 'success',
            'redirect' => $result->url,
        );
    }
}


function add_uddokta_payment_gateway($methods)
{
    $methods[] = 'Uddokta_Payment_Gateway';
    return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_uddokta_payment_gateway');
