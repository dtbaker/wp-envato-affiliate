<?php
/**
 * Plugin Name: Envato Affiliate
 * Description: Affiliate integration options for Envato
 * Plugin URI: https://envato.com/
 * Author: dtbaker
 * Version: 1.0.2
 * Author URI: https://dtbaker.net/
 * GitHub Plugin URI: https://github.com/dtbaker/wp-envato-affiliate
 * Requires at least:   4.9.4
 * Tested up to:        4.9.4
 * Text Domain: envato-affiliate
 * @package envato-affiliate
 */

defined( 'ABSPATH' ) || die;

/* Set plugin version constant. */
define( 'ENVATO_AFFILIATE_VERSION', '1.0.2' );

/* Debug output control. */
define( 'ENVATO_AFFILIATE_DEBUG_OUTPUT', 0 );

/* Set constant path to the plugin directory. */
define( 'ENVATO_AFFILIATE_SLUG', basename( plugin_dir_path( __FILE__ ) ) );

/* Set constant path to the plugin directory. */
define( 'ENVATO_AFFILIATE_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );

/* Set the constant path to the plugin directory URI. */
define( 'ENVATO_AFFILIATE_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );


add_action( 'plugins_loaded', 'envato_affiliate_load_plugin_textdomain' );

if ( ! version_compare( PHP_VERSION, '5.4', '>=' ) ) {
	add_action( 'admin_notices', 'envato_affiliate_fail_php_version' );
} else {

	/* EnvatoAffiliate Class */
	require_once( ENVATO_AFFILIATE_PATH . 'inc/class.EnvatoAffiliate.php' );

	/* EnvatoAffiliateAPI Class */
	require_once( ENVATO_AFFILIATE_PATH . 'inc/class.EnvatoAffiliateAPI.php' );

	/* Start up our magic */
	EnvatoAffiliate::get_instance()->init();


}

/**
 * Load gettext translate for our text domain.
 *
 * @since 1.0.0
 *
 * @return void
 */
function envato_affiliate_load_plugin_textdomain() {
	load_plugin_textdomain( 'dtbaker-elementor' );
}

/**
 * Show in WP Dashboard notice about the plugin is not activated.
 *
 * @since 1.0.0
 *
 * @return void
 */
if( ! function_exists( 'envato_affiliate_fail_php_version' ) ) {
	function envato_affiliate_fail_php_version() {
		$message      = esc_html__( 'The StylePress for Elementor plugin requires PHP version 5.4+, plugin is currently NOT ACTIVE.', 'stylepress' );
		$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
		echo wp_kses_post( $html_message );
	}
}
