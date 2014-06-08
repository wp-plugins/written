<?php


/**
* This is the content and traffic license functionality.
* http://written.com/content-licensing/
*/
function wtt_redirect_license(){
	global $post;

	if(is_singular()){
		$license_type = get_post_meta($post->ID, '_wtt_license_type', true);
		
		if($license_type == '1' || $license_type == '4'){ //1=301,4=302 
				
			$redirect_url = get_post_meta($post->ID, '_wtt_redirect_location', true);
			/* make sure there is no cache being generated on these pages so that the redirect stays put */

			if($redirect_url) {
				define('DONOTCACHEPAGE',true);
				global $hyper_cache_stop;

				$hyper_cache_stop = true;

				if($license_type == '1')
					wp_redirect($redirect_url, 301);

				if($license_type == '4')
					wp_redirect($redirect_url, 302);
			}

		}
	}	
}
add_action('template_redirect', 'wtt_redirect_license');


/**
* This is a legacy function.  We should be able to remove this in a future release.
* This function uses the old custom field naming convention that we used in V1 of the plugin.
* Be careful removing this as it could break old licenses.  Check with Connor before removing.
* ------
* This is the content and traffic license functionality.
* http://written.com/content-licensing/
*/
function wtt_redirect_license_legacy(){
	global $post;

	if(is_singular()){
		$redirect_url = get_post_meta($post->ID, 'wtt_redirect', true);
		if($redirect_url){ 
			
			define('DONOTCACHEPAGE',true);
			global $hyper_cache_stop;
			$hyper_cache_stop = true;

			if(get_post_meta($post->ID,'wtt_redirect_temp')) {
				wp_redirect($redirect_url, 302); 
			} else {
				wp_redirect($redirect_url, 301); 
			}
			

		}
	}	
}
add_action('template_redirect', 'wtt_redirect_license_legacy');

/* Everything below is legacy and can be removed on a future release.  Consult with Connor. */
function wtt_guest_author_name( $name ) {
	global $post;

	$author = get_post_meta( $post->ID, 'wtt_custom_author', true );

	if ( $author )
	$name = $author;

	return $name;
}

function wtt_guest_author_url($url) {
	global $post;

	$guest_url = get_post_meta( $post->ID, 'wtt_author_url', true );

	if ( $guest_url!=='' ) {
		return $guest_url;
	} elseif ( get_post_meta( $post->ID, 'wtt_custom_author', true ) ) {
		return '#';
	}

	return $url;
}


add_filter( 'the_author', 'wtt_guest_author_name' );
add_filter( 'get_the_author_display_name', 'wtt_guest_author_name' );

add_filter( 'author_link', 'wtt_guest_author_url' );