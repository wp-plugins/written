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
					
				wtt_w3tc_force_cache_empty();

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
add_action('template_redirect', 'wtt_redirect_license',1);


/**
* This function clears out the annoying .old file that the W3TC plugin developers forgot to delete.
*/
function wtt_w3tc_force_cache_empty() {
 	
 	if(defined('W3TC_CACHE_PAGE_ENHANCED_DIR'))
 	{
 		$cache_route = W3TC_CACHE_PAGE_ENHANCED_DIR.'/'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'_index.html.old';

 		if(file_exists($cache_route)) unlink($cache_route);
 	}
	
 	if(defined('W3TC_CACHE_PAGE_ENHANCED_DIR'))
 	{
 		$cache_route_2 = W3TC_CACHE_PAGE_ENHANCED_DIR.'/'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'_index.html_gzip.old';

 		if(file_exists($cache_route_2)) unlink($cache_route_2);
 	}
}


/**
* This function migrates posts from the old custom field license structure to the new.
* ------
* We should be able to delete this once all bloggers are on plugin V 2.5+
*/
function wtt_redirect_license_legacy(){
	global $post;

	if(is_singular()){

		if(get_post_meta($post->ID, 'wtt_redirect', true)) {

			$redirect_url = get_post_meta($post->ID, 'wtt_redirect', true);

			update_post_meta($post->ID,'_wtt_license_type','1');
			update_post_meta($post->ID,'_wtt_redirect_location',$redirect_url);
			delete_post_meta($post->ID,'wtt_redirect');


		}

	}	
}
add_action('template_redirect', 'wtt_redirect_license_legacy');



function wtt_guest_author_name( $name ) {
	global $post;

	if($post->post_author == get_option('wtt_user_id')) {
		
		$author = get_post_meta( $post->ID, '_wtt_author_name', true );


		if($author)
			return $author;

		return 'Guest Author';

	} else {

		return $name;
	}

}

function wtt_guest_author_url($url) {
	global $post;

	if($post->post_author == get_option('wtt_user_id')) {

		$guest_url = get_post_meta( $post->ID, '_wtt_authorrank_url', true );


		if($guest_url) {
			return $guest_url;
		} else {
			return $url;
		}

	} else {

		return $url;

	}
	

}


add_filter( 'the_author', 'wtt_guest_author_name' );
add_filter( 'get_the_author_display_name', 'wtt_guest_author_name' );

add_filter( 'author_link', 'wtt_guest_author_url' );