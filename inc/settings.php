<?php

defined( 'ABSPATH' ) || die;


?>
<div class="wrap envato-affiliate">
	<h1>Envato Affiliate (Beta)</h1>
	<div>
		<h2>Sample Code</h2>
		<p>This is an example shortcode. Put this shortcode anywhere on your site (e.g. post content, sidebar text widget).</p>
		<pre>[envato_affiliate]</pre>
		<p>You can optionally overwrite the default settings (at bottom) by including them as shortcode parameters:</p>
		<pre>[envato_affiliate<?php
			$settings = EnvatoAffiliate::get_instance()->get_settings();
			foreach($settings as $key=>$val){
				if( !in_array( $key, [ 'api_token', 'default_css' ] ) ){
					echo ' '.$key.'="' . esc_attr($val) . '"';
				}
			}
			?>]</pre>
		<p>If you would like to include this in your PHP code (e.g. theme header.php or search.php files) then use this snippet:</p>
		<pre><?php echo htmlspecialchars('<?php 
if( class_exists( "EnvatoAffiliate" ) ){
	EnvatoAffiliate::get_instance()->show_results();
}
?>');?></pre>
		<p>Or similar to the shortcode example above, you can overwrite default settings with custom ones like below. <br/>
		If you pass in a 'search_term' that will be used in the API query. If you leave 'search_term' empty then it will default to the current users search term or blog post title.</p>
		<pre><?php echo htmlspecialchars('<?php 
if( class_exists( "EnvatoAffiliate" ) ){
	EnvatoAffiliate::get_instance()->show_results( array(
		"search_term" => "PUT YOUR CUSTOM SEARCH TERM HERE, OR LEAVE EMPTY",' . "\n");
			foreach($settings as $key=>$val){
				if( !in_array( $key, [ 'api_token', 'default_css' ] ) ){
					echo ' 		"'.$key.'" => "' . esc_attr($val) . '",' . "\n";
				}
			}
echo htmlspecialchars('        ) );
}
?>');?></pre>

	</div>
	<form method="post" action="options.php">
		<?php
		settings_fields( ENVATO_AFFILIATE_SLUG .'setting' );
		do_settings_sections( ENVATO_AFFILIATE_SLUG .'setting' );
		submit_button();
		?>
	</form>
</div>