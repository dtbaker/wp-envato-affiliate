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

	public function call_market_api( $endpoint, $arguments = [], $custom_settings = [] ) {
		$settings  = EnvatoAffiliate::get_instance()->get_settings( $custom_settings );
		$arguments = apply_filters( 'envato_affiliate/call_market_api/arguments', $arguments, $endpoint, $settings, $custom_settings );
		$api_url   = "https://api.envato.com/" . $endpoint . '?' . http_build_query( $arguments );
		$api_url   = apply_filters( 'envato_affiliate/call_market_api/url', $api_url, $endpoint, $arguments, $settings, $custom_settings );
		$api_hash  = md5( $api_url );
		EnvatoAffiliate::get_instance()->debug( "API URL is: $api_url" );
		$api_result = get_transient( $api_hash );
		if ( $api_result === false ) {
			EnvatoAffiliate::get_instance()->debug( "No local API cache found." );
			// no transient value found. hit the API
			$response = wp_remote_get( $api_url,
				array(
					'timeout' => 5,
					'headers' => array(
						'Authorization' => 'Bearer ' . $settings['api_token'],
						'User-Agent'    => 'WordPress Envato Affiliate Plugin',
					)
				)
			);
			if ( is_wp_error( $response ) or ( wp_remote_retrieve_response_code( $response ) != 200 ) ) {
				EnvatoAffiliate::get_instance()->debug( "API Error: " . var_export( wp_remote_retrieve_body( $response ), true ) );
				$api_result = array();
			} else {
				$api_result = json_decode( wp_remote_retrieve_body( $response ), true );
				EnvatoAffiliate::get_instance()->debug( "Successfull API Result of size " . strlen( wp_remote_retrieve_body( $response ) ) . " characters." );
			}
			if ( ! is_array( $api_result ) ) {
				$api_result = array();
			}
			if ( ! empty( $api_result['matches'] ) ) {
				foreach ( $api_result['matches'] as $key => $item ) {
					$api_result['matches'][ $key ]['preview_graphic_size'] = 'full';
					if ( ! empty( $item['previews'] ) ) {
						foreach ( $item['previews'] as $preview ) {
							if ( ! empty( $preview['landscape_url'] ) ) {
								$api_result['matches'][ $key ]['preview_graphic_url'] = $preview['landscape_url'];
							}
							if ( ! empty( $preview['square_url'] ) && ! empty( $preview['icon_url'] ) && ! $api_result['matches'][ $key ]['preview_graphic_url'] ) {
								//$preview_graphic_url = $preview['square_url']; // These images are HUGE! Like 40MB huge! Don't use them on your site.
								$api_result['matches'][ $key ]['preview_graphic_url']  = $preview['icon_url'];
								$api_result['matches'][ $key ]['preview_graphic_size'] = 'thumb';
							}
							if ( ! empty( $preview['icon_url'] ) ) {
								$api_result['matches'][ $key ]['thumbnail_url'] = $preview['icon_url'];
							}
						}
					}
				}
			}
			$api_result = apply_filters( 'envato_affiliate/call_market_api/results', $api_result, $response, $endpoint, $arguments, $settings, $custom_settings );
//			echo '<pre>'.print_r($api_result,true).'</pre>';
			$seconds = max( 600, 3600 * (int) $settings['cache_timeout'] );
			EnvatoAffiliate::get_instance()->debug( "Saving cache for $seconds seconds" );
			set_transient( $api_hash, $api_result, $seconds );
		} else {
			EnvatoAffiliate::get_instance()->debug( "Using cached result." );
		}
		EnvatoAffiliate::get_instance()->debug( "Returning API result " );

		return $api_result;
	}

	public function call_elements_api( $endpoint, $arguments = [], $custom_settings = [] ) {
		$settings  = EnvatoAffiliate::get_instance()->get_settings( $custom_settings );
		$arguments = apply_filters( 'envato_affiliate/call_elements_api/arguments', $arguments, $endpoint, $settings, $custom_settings );
		$api_url   = "https://elements.envato.com/api/" . $endpoint . '?' . http_build_query( $arguments );
		$api_url   = apply_filters( 'envato_affiliate/call_elements_api/url', $api_url, $endpoint, $arguments, $settings, $custom_settings );
		$api_hash  = md5( $api_url );
		EnvatoAffiliate::get_instance()->debug( "API URL is: $api_url" );
		$api_result = get_transient( $api_hash );
		if ( true || $api_result === false ) {
			EnvatoAffiliate::get_instance()->debug( "No local API cache found." );
			// no transient value found. hit the API
			$response = wp_remote_get( $api_url,
				array(
					'timeout' => 5,
					'headers' => array(
						'User-Agent' => 'WordPress Envato Affiliate Plugin',
					)
				)
			);
			if ( is_wp_error( $response ) or ( wp_remote_retrieve_response_code( $response ) != 200 ) ) {
				EnvatoAffiliate::get_instance()->debug( "API Error: " . var_export( wp_remote_retrieve_body( $response ), true ) );
				$elements_api_result = array();
			} else {
				$elements_api_result = json_decode( wp_remote_retrieve_body( $response ), true );
				EnvatoAffiliate::get_instance()->debug( "Successfull API Result of size " . strlen( wp_remote_retrieve_body( $response ) ) . " characters." );
			}
			if ( ! is_array( $elements_api_result ) ) {
				$elements_api_result = array();
			}
			// We want to format this result array in the same way that market results are returned above.
			$api_result = array(
				'matches' => array(),
			);
			if ( ! empty( $elements_api_result['data'] ) && ! empty( $elements_api_result['data']['attributes'] ) && ! empty( $elements_api_result['data']['attributes']['items'] ) ) {
				foreach ( $elements_api_result['data']['attributes']['items'] as $elements_item ) {
					$item = array(
						'id' => $elements_item['id'],
					);
					$item['title'] = $elements_item['title'];
					$item['url'] = 'https://elements.envato.com/'.$elements_item['slug'] . '-' . $elements_item['id'];

					// https://elements-cover-images-0.imgix.net/b965c6b2-b0f1-498f-953c-117446e15042?w=710&fit=max&auto=compress%2Cformat&s=2a189d4e555106fc5f7b04dc7dbdf73e
					$item['thumbnail_url'] = ''; // thumbs?
					$item['preview_graphic_url'] = '';
					$item['preview_graphic_size'] = 'w316'; // 174 316 710
					if(!empty($elements_item['coverImage']) && !empty($elements_item['coverImage']['imgixQueries'][ $item['preview_graphic_size'] ])){
						$item['preview_graphic_url'] = 'https://' . $elements_item['coverImage']['imgixSubdomain'].'-0.imgix.net/' .$elements_item['coverImage']['id'] . '?' .$elements_item['coverImage']['imgixQueries'][ $item['preview_graphic_size'] ];
					}

					$api_result['matches'][] = $item;
				}
			}
			$api_result = apply_filters( 'envato_affiliate/call_elements_api/results', $api_result, $response, $endpoint, $arguments, $settings, $custom_settings );
			$seconds = max( 600, 3600 * (int) $settings['cache_timeout'] );
			EnvatoAffiliate::get_instance()->debug( "Saving cache for $seconds seconds" );
			set_transient( $api_hash, $api_result, $seconds );
		} else {
			EnvatoAffiliate::get_instance()->debug( "Using cached result." );
		}
		EnvatoAffiliate::get_instance()->debug( "Returning API result " );

		return $api_result;
	}


}