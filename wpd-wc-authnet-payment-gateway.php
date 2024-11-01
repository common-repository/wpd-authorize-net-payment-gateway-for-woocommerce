<?php
/**
 * Plugin Name: WPD Authorize.net Payment Gateway for WooCommerce
 * Plugin URI: wpplugindesign.com
 * Description: Implements a secure payment gateway for WooCommerce Credit Card Payments via Authorize.Net.
 * Version: 0.7
 * Author: Carmen Borrelli
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WPD Authorize.net 
 * @author wpplugindesign.com
*/

if (!defined('ABSPATH')) {
	exit;
}

if (!defined('WPD_AUTHNET_PLUGIN_FILE')) {
	define('WPD_AUTHNET_PLUGIN_FILE', __FILE__);
}
if (!defined('WPD_AUTHNET_PLUGIN_BASENAME')) {
	define('WPD_AUTHNET_PLUGIN_BASENAME', plugin_basename(WPD_AUTHNET_PLUGIN_FILE));
}

if (!class_exists('WPD_WC_Authorizenet_Payment_Gateway')) {

class WPD_WC_Authorizenet_Payment_Gateway {

	public $version = '0.7';
	protected static $instance = null;

    public $plugin_path;
    public $plugin_url;
    
    public $order_statuses = array(); // populated by get_wc_order_statuses (wp_loaded)
    public $api_resp_codes = array(1 => 'Approved', 2 => 'Declined', 3 => 'Error', 4 => 'Held for Review');
    
	/**
	 * singleton plugin instance
	 *
	 * @static
	 * @return singleton plugin instance
	 */
	public static function get_instance() {
		if (null == self::$instance) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	private function __construct() {
		$this->plugin_path = plugin_dir_path(__FILE__);
		$this->plugin_url  = plugin_dir_url(__FILE__);

		add_action('admin_enqueue_scripts', array($this, 'admin_register_scripts_styles'));

		// add 'Settings' link on Plugins page
		add_filter('plugin_action_links_' . WPD_AUTHNET_PLUGIN_BASENAME, array($this, 'plugin_action_links'));

		// get current gateway settings
		add_action('wp_loaded', array($this, 'get_gateway_settings'));

		// get and store order statuses
		add_action('wp_loaded', array($this, 'get_wc_order_statuses'));

		// add classes to body tag
		add_filter('admin_body_class', array($this, 'admin_body_classes'), 8000);

		// register wc payment gateway
		add_action('plugins_loaded', array($this, 'authorizenet_gateway_init'), 700);
		add_filter('woocommerce_payment_gateways', array($this, 'add_authorizenet_gateway_class'));

		add_action('admin_notices', array($this, 'admin_notices'));

		// add wpd-authnet-transactions meta box to wc single order page
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

		// ajax - get transaction current status
		add_action('wp_ajax_authnet_get_transaction_status', array($this, 'authnet_get_transaction_status'));

		// change message on checkout thank you page if payment was 'held for review'
		add_action('template_redirect', array($this, 'maybe_add_held_for_review_message'));
	}

	public function get_plugin_url() {
		return $this->plugin_url;
	}
	public function get_plugin_path() {
		return $this->plugin_path;
	}

    public function admin_register_scripts_styles() {
    	wp_register_style(
    		'wpdauth_admin_styles', 
    		$this->plugin_url.'css/styles.css',
    		array(),
    		$this->version
    	);
		wp_register_script(
			'wpdauth_admin_script',
			$this->plugin_url.'js/scripts.js',
			array('jquery'), 
			$this->version, 
			true
		);
		wp_enqueue_style('wpdauth_admin_styles');
		wp_enqueue_script('wpdauth_admin_script');
	}

	/**
	 * Show action links on the plugin screen
	 * @param mixed $links
	 * @return array
	 */
	public static function plugin_action_links($links) {
		$add = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wpd_authorizenet' ) . '" aria-label="' . esc_attr__('WPD Authorize.net Payment Gateway for WooCommerce settings', 'woocommerce') . '">' . esc_html__('Settings', 'woocommerce') . '</a>'
		);
		return array_merge($add, $links);
	}

	public function admin_notices() {
		$wpd_settings = get_option('woocommerce_wpd_authorizenet_settings');
		// reminder to enter Authorize.net api credentials
		if (empty($wpd_settings['api_login']) || empty($wpd_settings['transaction_key'])) { ?>
			<div class="error">
				<p><?php echo __('Please enter your Authorize.net api credentials for the ', 'woocommerce'); ?><a href="<?php echo get_admin_url(); ?>admin.php?page=wc-settings&tab=checkout&section=wpd_authorizenet">WPD Authorize.Net payment gateway</a><?php echo __(' to start processing credit card payments.', 'woocommerce'); ?></p>
			</div><?php
		}
	}

	public function add_meta_boxes() {
		add_meta_box(
			'wpd-authnet-transactions', 
			__('Authorize.Net Transactions', 'woocommerce'), 
			array($this, 'order_authorizenet_meta_box'), 
			'shop_order',
			'normal', 
			'high',
			array()
		);
	}

	public function order_authorizenet_meta_box($post, $metabox) {
		global $post;
		require_once $this->plugin_path . 'includes/transaction-table.php';

		$t = new WPD_Authorizenet_Transactions_Table();
		$t->order_id = $post->ID;
		$t->prepare_items();
		$t->display();
		/*
		echo '<pre>';
		print_r(get_post_meta($post->ID, 'wpd_authnet_pmet', true));
		echo '</pre>';

		echo '<pre>';
		print_r(get_post_meta($post->ID, 'wpd_authnet_pmet_declined', true));
		echo '</pre>';
		*/
	}

	public function authnet_get_transaction_status() {

		$trans_id = sanitize_text_field($_POST['trans_id']);
		$order_id = sanitize_text_field($_POST['order_id']);

		if (!class_exists('WPD_WC_Authorizenet_Gateway')) {
			require_once $this->plugin_path . 'includes/payment-gateway.php';
		}
		$gateway = new WPD_WC_Authorizenet_Gateway();
		$response = $gateway->get_transaction_status($trans_id, $order_id);

		if ($response) {
			$response['result'] = 'success';
			$response['trans_id'] = $trans_id;
			$return = $response;
		} else {
			$return = array(
				'result' => 'fail',
				'trans_id' => $trans_id
			);
		}
		echo json_encode($return);
		exit;
	}

	public function maybe_add_held_for_review_message() {
		if (isset($_GET['wpdauth_held'])) :
			$wpdauth_held = sanitize_text_field($_GET['wpdauth_held']);
	 		if (!empty($wpdauth_held)) {
	 			if ('yes' == $this->gateway_settings['show_held_message']) {
	 				add_filter('woocommerce_thankyou_order_received_text', array($this, 'held_for_review_message'), 2000, 1);
	 			}
	 		}
 		endif;
	}

	public function held_for_review_message($order) {
		$held_message = 'Thank you for your order. Your payment has been held for review.';
		if ('' != ($hm = trim($this->gateway_settings['held_message']))) {
			$held_message = $hm;
		}
		return __($held_message, 'woocommerce');
	}

	public function admin_body_classes($classes) {
		$screen = get_current_screen();
		if ('shop_order' == $screen->id) {
			$add = 'wpd_single_shop_order';
		}
		if (isset($add)) {
			$classes .= ' ' . $add;
		}
		return $classes;
	}

	public function get_gateway_settings() {
		$this->gateway_settings = get_option('woocommerce_wpd_authorizenet_settings');
	}

	public function get_wc_order_statuses() {
		$this->order_statuses = wc_get_order_statuses();
	}

	public function authorizenet_gateway_init() {
		require $this->plugin_path . 'includes/payment-gateway.php';
	}

	public function add_authorizenet_gateway_class($gateways) {
		$gateways[] = 'WPD_WC_Authorizenet_Gateway';
		return $gateways;
	}

}
}

WPD_WC_Authorizenet_Payment_Gateway::get_instance();