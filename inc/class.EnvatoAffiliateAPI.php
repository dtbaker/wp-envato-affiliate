<?php

defined( 'ABSPATH' ) || die;


class EnvatoAffiliateAPI{

	private static $instance = null;
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct( ) {

	}

	public function call_api( $endpoint, $arguments = [], $custom_settings = [] ){
		$settings = EnvatoAffiliate::get_instance()->get_settings( $custom_settings );
		$arguments = apply_filters('envato_affiliate/call_api/arguments', $arguments, $endpoint, $settings, $custom_settings );
		$api_url = "https://api.envato.com/" . $endpoint . '?' . http_build_query($arguments);
		$api_url = apply_filters('envato_affiliate/call_api/url', $api_url, $endpoint, $arguments, $settings, $custom_settings );
		$api_hash = md5( $api_url );
		EnvatoAffiliate::get_instance()->debug("API URL is: $api_url");
		$api_result = get_transient( $api_hash );
		if($api_result === false) {
			EnvatoAffiliate::get_instance()->debug("No local API cache found.");
			// no transient value found. hit the API
			$response = wp_remote_get( $api_url,
				array(
					'timeout' => 5,
					'headers' => array(
						'Authorization' => 'Bearer ' . $settings['api_token'],
						'User-Agent' => 'WordPress Envato Affiliate Plugin',
					)
				)
			);
			if ( is_wp_error( $response ) or ( wp_remote_retrieve_response_code( $response ) != 200 ) ) {
				EnvatoAffiliate::get_instance()->debug("API Error: " . var_export(wp_remote_retrieve_body( $response ),true));
				$api_result = array();
			}else {
				$api_result = json_decode( wp_remote_retrieve_body( $response ), true );
				EnvatoAffiliate::get_instance()->debug("Successfull API Result of size " . strlen(wp_remote_retrieve_body( $response )) ." characters.");
			}
			if ( ! is_array( $api_result ) ) {
				$api_result = array();
			}
			$api_result = apply_filters( 'envato_affiliate/call_api/results', $api_result, $response, $endpoint, $arguments, $settings, $custom_settings );
			$seconds = max( 600, 3600 * (int)$settings['cache_timeout'] );
			EnvatoAffiliate::get_instance()->debug("Saving cache for $seconds seconds");
			set_transient( $api_hash, $api_result, $seconds );
		}else{
			EnvatoAffiliate::get_instance()->debug("Using cached result.");
		}
		EnvatoAffiliate::get_instance()->debug("Returning API result ");
		return $api_result;
	}


}