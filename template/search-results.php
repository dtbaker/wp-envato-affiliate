<?php

// Copy this file to /wp-content/YOUR-THEME-FOLDER/envato-affiliate/search-results.php if you wish to modify it.

defined( 'ABSPATH' ) || die;


if( isset($items) && is_array($items) && isset( $settings ) && $settings ) {
	?>

	<div class="envato-affiliate-<?php echo esc_attr($settings['layout']);?>">
		<ul class="envato-affiliate-<?php echo esc_attr($settings['layout']);?>--list">
			<?php foreach($items as $item){
				if( $item && !empty( $item['id'])) {
					$thumbnail_url = '';
					$preview_graphic_url = '';
					foreach($item['previews'] as $preview){
						if(!empty($preview['landscape_url'])){
							$preview_graphic_url = $preview['landscape_url'];
						}
						if(!empty($preview['icon_url'])){
							$thumbnail_url = $preview['icon_url'];
						}
					}
					$url       = $item['url'] . '?ref=' . $settings['affiliate_username'];
					$title     = $item['title'];
					if ( $url && $preview_graphic_url ) {
						?>
						<li class="envato-affiliate-<?php echo esc_attr( $settings['layout'] ); ?>--list-item">
							<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="nofollow"
							   class="envato-affiliate-<?php echo esc_attr( $settings['layout'] ); ?>--link">
								<img src="<?php echo esc_url( $preview_graphic_url ); ?>" alt="<?php echo esc_attr( $title ); ?>"
								     class="envato-affiliate-<?php echo esc_attr( $settings['layout'] ); ?>--image">
							</a>
						</li>
						<?php
					}
				}
			} ?>
		</ul>
	</div>

	<?php
}