<?php

/*
* This serves the AdBuyout template if the current post has an AdBuyout license.
*/
function get_adbuyout_template($single_template) {
	global $post;
	if(is_singular()){
		$license = get_post_meta( $post->ID, '_wtt_license_type', TRUE );

		/* Checks to see if the custom field exists and is equal to 3.  3=adbuyout */
		if ($license == '3' && !isset($_GET['original'])) {
			$single_template = dirname( __FILE__ ) . '/adbuyout-template.php';
		}

		return $single_template;
	}
	
}
add_filter( 'single_template', 'get_adbuyout_template' );
add_filter( 'page_template', 'get_adbuyout_template' );

function wtt_adbuyout_license(){
	global $post;

	if(is_singular()){
		$license_type = get_post_meta($post->ID, '_wtt_license_type', true);
		
		if($license_type == '3'){ //3=adbuyout

		}

	}	
}
add_action('template_redirect', 'wtt_adbuyout_license',1);

/**
* This adds the Written takeover stylesheet to the head of all AdBuyout pages.
*/
function wtt_adbuyout_styles() {

	global $post;

	if(is_singular()) {
		$license = get_post_meta( $post->ID, '_wtt_license_type', TRUE );

		/* Checks to see if the custom field exists and is equal to 3.  3=adbuyout */
		if ($license == '3' && !isset($_GET['original'])) {
			wp_enqueue_style('wtt_takeover_css');
		}
	}

	
}
add_action( 'wp_enqueue_scripts', 'wtt_adbuyout_styles' );

/*
* This outputs AdBuyout stuff into wp_footer assuming the current post is an AdBuyout.
*/
function wtt_takeover_license_code(){
	global $post;

	if(is_singular()){
		$license = get_post_meta( $post->ID, '_wtt_license_type', TRUE );

		if($license == '3' && !isset($_GET['original'])):
			echo '<style type="text/css">body.wtt_takeover {background-image: url("'.get_post_meta( $post->ID, '_wtt_adbuyout_bg_image_url', TRUE ).'"); background-color: '.get_post_meta( $post->ID, '_wtt_adbuyout_bg_color', TRUE ).';}</style>';
		endif;
	}	
}
add_action('wp_head', 'wtt_takeover_license_code');