<?php

//add_action('wp_footer', 'wtt_pixel_license_code');

// Takeover License Query Vars Set up
function wtt_parse_request($wp) {
    // only process requests with "my-plugin=ajax-handler"
    if (array_key_exists('written', $wp->query_vars) 
            && $wp->query_vars['written'] === 'takeover') {

		global $post;
		if(is_singular()){
			$takeover_code = get_post_meta( $post->ID, 'wtt_takeover_code', TRUE );

			$iframe_code = $takeover_code;

			if( $takeover_code ) {
				$template = get_option('wtt_html_template');

				$replacements = array(
					'%wtt_sponsor_by%'=>'Design Hack', 
					'%wtt_original_article%'=>'#',
					'%wtt_logo_title%'=>$post->post_title,
					'%wtt_post_title%'=>$post->post_title,
					'%wtt_post_date%'=>$post->post_date,
					'%wtt_post_author%'=>get_the_author_meta( 'user_nicename', $post->post_author),
					'%wtt_post_content%'=>$post->post_content,
					'%wtt_written_text%'=>'Written.com',
					'%wtt_written_url%'=>'http://written.com',
					'%wtt_sidebar_code%'=>$iframe_code
				);

				$rendered_template = str_replace(array_keys($replacements), $replacements, $template);

				die($rendered_template);
			}
		}
    }
}

//add_action('wp', 'wtt_parse_request');

function wtt_query_vars($vars) {
    $vars[] = 'written';
    return $vars;
}

//add_filter('query_vars', 'wtt_query_vars');


function wtt_body_class_names($classes) {

	global $post;
	if(is_singular()){
		$takeover_code = get_post_meta( $post->ID, 'wtt_takeover_code', TRUE );
		if( $takeover_code ) {	
			$classes[] = 'takeover';
		}
	}
	// return the $classes array
	return $classes;
}

//add_filter('body_class','wtt_body_class_names');

// WP Footer JS Injection for Takeover License
function wtt_takeover_license_code(){
	global $post;
	if(is_singular()){
		$takeover_code = get_post_meta( $post->ID, 'wtt_takeover_code', TRUE );

		if( $takeover_code ) {

			echo '<div id="written_ajax"></div><div class="wtt_loader"></div><div class="wtt_overlay_loader"></div>';
			echo "\n"."<style>"."\n".".wtt_overlay_loader{ z-index:99990;  background-color: #ffffff; width: 100%;  height: 100%;  top: 0;  left: 0;  position: fixed;  overflow:scroll; display:block; }"."\n"."</style>"."\n";

			wp_enqueue_script('wtt_takeover_js',plugins_url('js/wtt_takeover.js', __FILE__),array('jquery'),'',true);

			wp_register_style('wtt_takeover_css', WTT_CSS_TEMPLATE);
			wp_enqueue_style('wtt_takeover_css');

			wp_register_style('wtt_takeover_dynamic_css', WTT_DYNAMIC_CSS_TEMPLATE);
			wp_enqueue_style('wtt_takeover_dynamic_css');

		}
	}	
}

//add_action('wp_footer', 'wtt_takeover_license_code');

?>