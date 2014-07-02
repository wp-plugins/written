<?php

// WP Header 301 Redirect
function wtt_redirect_license(){
	global $post;

	if(is_singular()){
		$redirect_url = get_post_meta($post->ID, 'wtt_redirect', true);
		if($redirect_url){ wp_redirect($redirect_url, 301); }
	}	
}

add_action('template_redirect', 'wtt_redirect_license');

?>