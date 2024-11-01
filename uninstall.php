<?php
/**
 * The code in this file runs when plugin is uninstalled from the WordPress dashboard.
 *
 * @package WPD Authorize.net
 * @author wpplugindesign.com
 * @since 0.5
*/

/* If uninstall is not called from WordPress exit. */
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

// delete payment gateway settings
delete_option('woocommerce_wpd_authorizenet_settings');