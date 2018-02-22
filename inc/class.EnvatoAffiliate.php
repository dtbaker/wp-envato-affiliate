<?php

defined( 'ABSPATH' ) || die;


class EnvatoAffiliate{

	private static $instance = null;
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private $settings = [];

	public function __construct() {
		$this->settings = array(
			'api_token' => array(
				'title' => 'API Token',
				'help' => 'Register a new personal token from: https://build.envato.com/create-token/ and paste it here. <br/>
		            Make sure the \'View and search Envato sites\' permission is ticked.',
				'default' => 'xxxxxxx',
                'type' => 'password',
			),
			'affiliate_username' => array(
				'title' => 'Affiliate Username',
				'help' => 'The username added to URL query strings, e.g. `?ref=dtbaker`',
				'default' => 'dtbaker',
			),
			'ir_market_url' => array(
				'title' => 'Impact Radius Market',
				'help' => 'Your personal tracking link available from `https://member.impactradius.com/secure/mediapartner/campaigns/mp-manage-active-ios-flow.ihtml`',
				'default' => 'http://1.envato.market/c/370092/275988/4415',
			),
			'ir_elements_url' => array(
				'title' => 'Impact Radius Elements',
				'help' => 'Your personal tracking link available from `https://member.impactradius.com/secure/mediapartner/campaigns/mp-manage-active-ios-flow.ihtml`',
				'default' => 'http://1.envato.market/c/370092/298927/4662',
			),
			'fallback_search' => array(
				'title' => 'Default Search Term',
				'help' => 'If we cannot find results on the Envato API, we fall back to this default search term.',
				'default' => 'WordPress Themes',
			),

			'marketplace' => array(
				'title' => 'Default Marketplace',
				'help' => 'This is the marketplace we search for API results, e.g. `themeforest.net` or `codecanyon.net`',
				'type' => 'select',
				'default' => 'themeforest.net',
				'options' => array(
					'themeforest.net' => 'ThemeForest',
					'codecanyon.net' => 'CodeCanyon',
					'graphicriver.net' => 'GraphicRiver',
					'videohive.net' => 'VideoHive',
					'audiojungle.net' => 'AudioJungle',
					'3docean.net' => '3DOcean',
					'photodune.net' => 'PhotoDune',
					'elements.envato.com' => 'Elements',
				),
			),
			'category' => array(
				'title' => 'Default Category',
				'help' => 'This is the category we search for API results, e.g. `wordpress` or `php-scripts` or `all`',
				'default' => 'all',
			),
			'layout' => array(
				'title' => 'Default CSS Class',
				'help' => 'This is the CSS class that we add to the API output. Use this to style the item output from your theme CSS code. See sample CSS for more information. The values here are `default`, `row`, and `flex`.',
				'default' => 'default',
				'type' => 'select',
                'options' => array(
                    'default' => 'Default Scrolling Row',
                    'row' => 'Flex Row (for page top)',
                    'flex' => 'Flex Column (for sidebar)'
                ),
			),
			'item_count' => array(
				'title' => 'Item Count',
				'help' => 'How many items should be returned in the API result',
				'default' => '10',
                'type' => 'text',
			),
			'cache_timeout' => array(
				'title' => 'Cache Timeout',
				'help' => 'Results will be cached for this many hours. While results are cached we will not call the Envato API, so your pages will load faster. <br/>
				 We recommend setting this value to `2` for two hours of caching.',
				'default' => '2',
			),
			'default_css' => array(
				'title' => 'Load default CSS',
				'help' => 'Yes or No. If set to `Yes` then the default `frontend.css` styles will be loaded. <br/>You should probably copy these styles into your theme and change this setting to `No`.',
				'default' => 'yes',
				'type' => 'select',
				'options' => array(
					'yes' => 'Yes include default CSS',
					'no' => 'No do not include CSS',
				),
			),
			'debug' => array(
				'title' => 'Show Debug Messages',
				'help' => 'Yes or No. Shows debug messages when set to `Yes`.',
				'default' => 'no',
				'type' => 'select',
				'options' => array(
					'yes' => 'Yes, show debug messages',
					'no' => 'No, hide debug messages',
				),
			),
		);
		$saved_settings = get_option('envato_affiliate_options');
		foreach($this->settings as $key => $options ){
			$this->settings[$key]['value'] = $saved_settings && is_array($saved_settings) && isset($saved_settings[ENVATO_AFFILIATE_SLUG . 'setting-' . $key]) ? $saved_settings[ENVATO_AFFILIATE_SLUG . 'setting-' . $key] : $options['default'];
		}

	}

	public function init(){
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		add_shortcode( 'envato_affiliate', array( $this, 'shortcode_callback') );
		add_filter( 'widget_text', 'do_shortcode' ); // todo: check if theme/plugin already does this.
	}

	public function add_meta_boxes(){
		add_meta_box( ENVATO_AFFILIATE_SLUG . 'metabox', __( 'Envato Affiliate' ), array( $this, 'meta_box_callback' ), 'post', 'side' );
    }
	public function meta_box_callback(){
		wp_nonce_field( 'envato_affiliate_nonce', 'envato_affiliate_nonce' );
		?>
        <p>Please enter a search term to use for the Envato API (e.g. <code>bootstrap</code>). This will default to the blog post title if no custom search term is set.</p>
        <input type="text" name="envato_affiliate_search_term" value="<?php echo esc_attr( trim( get_post_meta( get_the_ID(), 'envato_affiliate_search_term', true ) ) );?>" >
        <?php
	}
	public function save_meta_boxes( $post_id, $post ){
		$nonce_name   = isset( $_POST['envato_affiliate_nonce'] ) ? $_POST['envato_affiliate_nonce'] : '';
		$nonce_action = 'envato_affiliate_nonce';

		// Check if nonce is set.
		if ( ! isset( $nonce_name ) ) {
			return;
		}

		// Check if nonce is valid.
		if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
			return;
		}

		// Check if user has permissions to save data.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if not an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Check if not a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if( !empty($_POST['envato_affiliate_search_term'])){
			update_post_meta( $post_id, 'envato_affiliate_search_term', $_POST['envato_affiliate_search_term'] );
        }

    }
	public function shortcode_callback( $atts ){

	    ob_start();
	    $this->show_results( $atts );
	    return ob_get_clean();

    }

	public function show_results( $custom_settings = [] ){
		$settings = $this->get_settings( $custom_settings );

		$search_term = '';
		// Work out what search keyword we use.
        if(!empty($settings['search_term'])){
            $search_term = trim($settings['search_term']);
        }
        if(!$search_term && is_search() ){
            // user is performing a blog post search, use their search term for the API call
            $search_term = get_search_query( false );
        }
        if(!$search_term && is_single() && $post_id = get_the_ID() ){
            // user is viewing a single blog post.
            // use the meta keyword attached to this blog post, otherwise the blog post title.
            $search_term = trim( get_post_meta( $post_id, 'envato_affiliate_search_term', true ) );
            if( !$search_term ){
                $search_term = trim( get_the_title() );
            }
        }
        if(!$search_term){
            // default to settings search term if none found above.
            $search_term = $settings['fallback_search'];
        }
		$search_term = trim($search_term);
        if( $search_term){
            // do the API query.
            $this->debug( "Searching the API for keyword: '$search_term'");

            $api = EnvatoAffiliateAPI::get_instance();

            if( !empty($settings['marketplace']) && $settings['marketplace'] == 'elements.envato.com' ){
	            $api_result = $api->call_elements_api( 'v1/items.json', [
		            'searchTerms'   => $search_term,
		            'type'          => !empty($settings['category']) && strtolower( $settings['category'] ) !== 'all' ? $settings['category'] : '',
                    'sortBy'        => 'popular', // relevant or latest
	            ], $custom_settings );
	            // Item count happens separately to API request.
	            $item_count = !empty($settings['item_count']) && (int)$settings['item_count'] > 0 ? $settings['item_count'] : 20;
	            if( $api_result && !empty($api_result['matches']) && count($api_result['matches']) > $item_count){
	                $api_result['matches'] = array_slice( $api_result['matches'], 0, $item_count);
                }
            }else {
	            $api_result = $api->call_market_api( 'v1/discovery/search/search/item', [
		            'term'           => $search_term,
		            'site'           => !empty($settings['marketplace']) ? $settings['marketplace'] : '',
		            'category'       => !empty($settings['category']) && strtolower( $settings['category'] ) !== 'all' ? $settings['category'] : '',
		            'page'           => 1,
		            'page_size'      => !empty($settings['item_count']) && (int)$settings['item_count'] > 0 ? $settings['item_count'] : 20,
		            'sort_by'        => 'rating',
		            'sort_direction' => 'desc',
	            ], $custom_settings );
            }

	        $items = array();
            if( !$api_result || !is_array( $api_result ) || empty($api_result['matches']) ){
                if( !isset($custom_settings['doing_fallback']) && $search_term != $settings['fallback_search']){
	                // fallback to default search term if the one above didn't return any results.
	                $this->debug( "Falling back to default search term because the previous one didn't return any results.");
	                $custom_settings['doing_fallback'] = true;
	                $custom_settings['search_term'] = $settings['fallback_search'];
	                $this->show_results( $custom_settings );
                }
            }else{
                // We've got some API results to display!
                $items = $api_result['matches'];
	            $this->debug( "Found ".count($items)." matches.");

	            if( $items ){
	                $template_file = $this->locate_template('search-results.php');
	                if( $template_file ){
	                    include $template_file;
                    }
                }

            }

        }else{
	        $this->debug( "No search term found, not looking at Envato API.");
        }
	}

	public function wp_enqueue_scripts(){
	    $settings = $this->get_settings();
	    if( strtolower($settings['default_css']) == 'yes'){
		    wp_enqueue_style( 'envato-affiliate-css', ENVATO_AFFILIATE_URI . 'assets/css/frontend.css', array(), ENVATO_AFFILIATE_URI );
        }
    }

	public function locate_template( $template_file ){
		$template = locate_template( array(
			'envato-affiliate/' . $template_file,
		) );
		if ( ! $template ) {
			$template = ENVATO_AFFILIATE_PATH . 'template/' . $template_file;
		}
		if( !is_file( $template ) ){
			_doing_it_wrong( __METHOD__, sprintf( 'Affiliate template <code>%s</code> does not exist.', $template_file ), '1.0.0' );
			$template = false;
        }
		return $template;
    }

	public function get_settings( $custom_settings = [] ){
		$settings = [];
		foreach( $this->settings as $key => $setting ){
			$settings[$key] = $setting['value'];
		}
		return apply_filters('envato_affiliate/api/get_settings', array_merge( $settings, $custom_settings ) );
	}

	public function admin_menu() {


		$page = add_options_page( esc_html__( 'Envato Affiliate' ), esc_html__( 'Envato Affiliate' ), 'manage_options', ENVATO_AFFILIATE_SLUG, array(
			$this,
			'admin_page_callback',
		) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'admin_page_assets' ) );

	}

	public function admin_init() {

		register_setting( ENVATO_AFFILIATE_SLUG . 'setting', 'envato_affiliate_options' );

		// register a new section in the "reading" page
		add_settings_section(
			ENVATO_AFFILIATE_SLUG .'section',
			'Settings',
			null, // This works, but unsure if correct way.
			ENVATO_AFFILIATE_SLUG . 'setting'
		);

		foreach( $this->settings as $key => $setting) {
			add_settings_field(
				ENVATO_AFFILIATE_SLUG . 'setting-' . $key,
				$setting['title'],
				array( $this, 'setting_field_cb_'.$key ),
				ENVATO_AFFILIATE_SLUG . 'setting',
				ENVATO_AFFILIATE_SLUG . 'section'
			);
		}

	}

	// Dynamic call for settings fields.
	public function __call($name, $arguments) {
		if (preg_match('/^setting_field_cb_([a-z_]+)/', $name, $matches)) {
			$key = $matches[1];
			if( isset($this->settings[$key])){

				switch($this->settings[$key]['type']){
					case 'select':
						?>
                        <select name="envato_affiliate_options[<?php echo esc_attr(ENVATO_AFFILIATE_SLUG . 'setting-' . $key);?>]">
                            <option value=""> - </option>
	                        <?php foreach($this->settings[$key]['options'] as $option_key=>$option_val){
	                            ?>
                                <option value="<?php echo esc_attr($option_key); ?>"<?php selected( $this->settings[$key]['value'], $option_key, true );?>><?php echo esc_attr($option_val);?></option>
	                        <?php } ?>
                        </select>
						<?php
						break;
					case 'password':
						?>
                        <input type="password" name="envato_affiliate_options[<?php echo esc_attr(ENVATO_AFFILIATE_SLUG . 'setting-' . $key);?>]" value="<?php echo esc_attr( $this->settings[$key]['value'] ); ?>">
						<?php
						break;
					case 'text':
                    default:
						?>
                        <input type="text" name="envato_affiliate_options[<?php echo esc_attr(ENVATO_AFFILIATE_SLUG . 'setting-' . $key);?>]" value="<?php echo esc_attr( $this->settings[$key]['value'] ); ?>">
						<?php
						break;
				}

				?>
				<br/>
				<em><?php echo $this->settings[$key]['help'];?></em>
				<?php
			}
		}
	}

	public function admin_page_assets() {

		wp_enqueue_script( 'envato-affiliate-backend', ENVATO_AFFILIATE_URI . 'assets/js/backend.js', false, ENVATO_AFFILIATE_VERSION, true );
		wp_enqueue_style( 'envato-affiliate-backend', ENVATO_AFFILIATE_URI . 'assets/css/backend.css', false, ENVATO_AFFILIATE_VERSION );

	}

	public function admin_page_callback() {
		include ENVATO_AFFILIATE_PATH . 'inc/settings.php';
	}

	public function debug($message){
		$settings = $this->get_settings( );
		if(strtolower($settings['debug']) == 'yes' && is_user_logged_in()) {
			echo '<div class="envato-affiliate-debug">' . esc_html( $message ) . '</div>';
		}
    }

    public function affiliate_link( $original_url, $settings ) {

	    $new_url = $original_url;
	    if ( strpos( $original_url, 'elements.envato.com' ) ) {
		    // we want to use the elements IR code here.
		    if ( ! empty( $settings['ir_elements_url'] ) ) {
			    $new_url = $settings['ir_elements_url'] . '?u=' . urlencode( $original_url );
		    }
	    } else {

		    if ( ! empty( $settings['ir_market_url'] ) ) {
			    $new_url = $settings['ir_market_url'] . '?u=' . urlencode( $original_url );
		    } else if ( ! empty( $settings['affiliate_username'] ) ) {
			    $new_url = $original_url . '?ref=' . $settings['affiliate_username'];
		    }
	    }

	    return $new_url;
    }

}