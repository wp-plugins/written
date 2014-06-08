<?php
/*
Plugin Name: Written
Plugin URI: http://www.written.com/
Description: Plugin for Advertisers and Publishers.
Version: 1.0.1
Author: Written
Author URI: http://www.written.com
*/

define("WTT_API", "http://api.written.com/", true);
define("WTT_EMAIL", "api@written.com", true);
define("WTT_USER", "writtenapi_", true);
//define("WTT_HTML_TEMPLATE", "http://written.com/templates/template.html", true);
define("WTT_CSS_TEMPLATE", plugins_url('css/template.css',__FILE__ ), true);
define("WTT_DYNAMIC_CSS_TEMPLATE", plugins_url('css/wtt_dynamic.php',__FILE__ ), true);

// Clean domain names
function wtt_clean_url($url){
	
	$input = trim($url, '/');

	// If scheme not included, prepend it
	if (!preg_match('#^http(s)?://#', $input)) {
	    $input = 'http://' . $input;
	}

	$urlParts = parse_url($input);

	// remove www
	$domain = preg_replace('/^www\./', '', $urlParts['host']);
	
	if(isset($urlParts['path'])){
		$domain = $domain.$urlParts['path'];
	}

	//return $url;
	return $domain;	
}

// Let's run the Activation Process
function wtt_activation() {
	$result = add_role(
		'wtt_user',
		'Written User',
		array(
			'delete_pages'   => true,  
			'delete_posts'   => true,
			'edit_pages' 	 => true, 
			'edit_posts' 	 => true, 
			'read' 			 => true, 
			'publish_posts'  => true, 
			'publish_pages'  => true, 
			'edit_others_posts' => true, 
			'edit_others_pages' => true
		)
	);

	// If not null we will create the Written User
	if ( null !== $result ) {
		$wp_username = WTT_USER.rand(1000, 9999);
		update_option("wtt_username", $wp_username); 	

		$user_id = username_exists( $wp_username );
		if ( !$user_id and email_exists(WTT_EMAIL) == false ) {
			$random_password = wp_generate_password( 12, false );
			update_option("wtt_password", $random_password); 
			$user_id = wp_insert_user( 
				array ( 
					'user_login' => $wp_username, 
					'user_email' => WTT_EMAIL,
					'user_pass'  => $random_password,
					'role'		 => 'wtt_user'
				) 
			);
			update_option("wtt_user_id", $user_id); 	

		} 
	}

	update_option('wtt_total_posts_sent', '0');	
	delete_option('wtt_loop');	
	delete_option('wtt_posts_sent_ok');

	wtt_save_template();
}

// Set up a Cron Job to save the template daily, in case it changes.
if( !wp_next_scheduled( 'wtt_template_refresh' ) ) {  
   wp_schedule_event( time(), 'daily', 'wtt_template_refresh' );  
}  
  
add_action( 'wtt_template_refresh', 'wtt_save_template' );  

function wtt_save_template(){

	$page = '<div class="wtt_takeover">	
	<header>
		<div class="container">
			<div class="col-md-12">
				<p>This post is sponsored by <a href="#" class="wtt_sponsor_by">%wtt_sponsor_by%</a>.  <a href="%wtt_original_article%" class="wt_original_article">View the Original Post</a>.</p>
			</div><!--col-md-12-->
		</div><!--container-->
	</header>
	<div class="container">
		<div class="row">
			<div class="col-md-12">
				<h1 class="logo"><a href="#" class="wtt_logo_title">%wtt_logo_title%</a></h1>
			</div><!--col-md-12-->
		</div><!--row-->

		<div class="row">
			<div class="col-md-7">
				<h2 class="title wtt_post_title">%wtt_post_title%</h2>
				<p class="meta"><i class="icon-calendar"></i> <time class="wtt_post_date">Posted on %wtt_post_date%</time> <i class="icon-user"></i> by <span class="wtt_post_author">%wtt_post_author%</span></p>

				<div class="wtt_post_content">
				%wtt_post_content%

				</div>

				<hr />

				<div class="well-sm well">
					<h5>This post is sponsored by <a href="#" class="wtt_sponsor_by">Sponsor</a>.  You can <a href="#" class="wt_original_article">view the original article</a> on Designshack.net to leave a comment.</h5>

					<p class="text-muted text-right" style="margin: 0; font-size: 10px;"><small>Sponsored post by <a href="%wtt_written_url%" class="wtt_written_text">%wtt_written_text%</a></small></p>
				</div><!--well-->
			</div><!--col-md-7-->

			<div class="col-md-4">
				<div class="wtt_sidebar_code">
					%wtt_sidebar_code%
				</div>
			</div>
		
		</div><!--row-->
	</div><!--container-->
</div>';
	
	update_option("wtt_html_template", $page); 

}

add_filter( 'cron_schedules', 'wtt_cron_add_ten_minutes' );
 
function wtt_cron_add_ten_minutes( $schedules ) {
	// Adds once weekly to the existing schedules.
	$schedules['tenminutes'] = array(
		'interval' => 600,
		'display' => __( 'Ten Minutes' )
	);
	return $schedules;
}

add_filter( 'cron_schedules', 'wtt_cron_add_two_minutes' );
 
function wtt_cron_add_two_minutes( $schedules ) {
	// Adds once weekly to the existing schedules.
	$schedules['twominutes'] = array(
		'interval' => 120,
		'display' => __( 'Two Minutes' )
	);
	return $schedules;
}

add_filter( 'cron_schedules', 'wtt_cron_add_one_minute' );
 
function wtt_cron_add_one_minute( $schedules ) {
	// Adds once weekly to the existing schedules.
	$schedules['oneminute'] = array(
		'interval' => 60,
		'display' => __( 'One Minutes' )
	);
	return $schedules;
}
  
add_action( 'wtt_send_more_posts', 'wtt_send_all_posts' );  

function wtt_send_all_posts(){
	
	if(!get_option('wtt_posts_sent_ok')){	

		// Variables
		$badBatch = false;
		$count_posts = wp_count_posts('post');
		$published_posts = $count_posts->publish;
		$url = wtt_clean_url( get_bloginfo('url') );
		$api_key = get_option('wtt_api_key');

		update_option('wtt_total_posts', $published_posts);	

		$posts_to_send = 5;
		
		if( !get_option('wtt_loop') ){ // If there is no loop, run for the first time.

			update_option('wtt_loop', '0');
			$offset = 0;

			$args = array( 
				'posts_per_page' => $posts_to_send, 
				'post_type' => 'post',
				'orderby' => 'ID',
				'order' => 'ASC'
			);

			// the query
			$the_query = new WP_Query( $args );
			
			if ( $the_query->have_posts() ) :

			  $counter = 0;

			  while ( $the_query->have_posts() ) : $the_query->the_post();

				$post_content = str_replace(array("\r\n","\n","\r"),'<br />',get_the_content());

				$post_json = array(
					'title' => htmlentities(get_the_title(), ENT_QUOTES),
					'url'	=> get_permalink(),
					'content' => htmlentities($post_content, ENT_QUOTES),
					'blog'	  => $api_key,
					'author'  => htmlentities(get_the_author()),
					'post_id' => get_the_ID()
				);				

				$post_json = json_encode($post_json);

				$response = wp_remote_post( WTT_API.'advertiser/add_post', array(
					'method' => 'POST',
					'headers' => array(
						'Accept'       => 'application/json',
	        			'Content-Type'   => 'application/json',
	        			'Content-Length' => strlen( $post_json )
					),
					'body' => $post_json
				    )
				);		
							
				if($response['body']==='false'){
					$badBatch = true;		
				}

				$counter++;

			  endwhile;

			  wp_reset_postdata();

			  if($badBatch){
			  	delete_option('wtt_loop');
			  } else {
			  	update_option('wtt_loop', '1');
			  	update_option('wtt_total_posts_sent', $counter);
			  }
			  
			endif;

			if( !wp_next_scheduled('wtt_send_more_posts')  && get_option('wtt_total_posts')!==get_option('wtt_total_posts_sent') ) {  
				// If we haven't reach the limit.
				wp_schedule_event( time(), 'oneminute', 'wtt_send_more_posts' );  
			}  elseif ( get_option('wtt_total_posts')===get_option('wtt_total_posts_sent') ){
				// If we reach the limit then delete the WP cron hook.
				$timestamp = wp_next_scheduled( 'wtt_send_more_posts' );
				wp_unschedule_event( $timestamp, 'wtt_send_more_posts' );
				update_option('wtt_posts_sent_ok','yes');
			}

		} else { // if there is loop, run again.

			$wtt_loop = get_option('wtt_loop');
			$offset = $posts_to_send * $wtt_loop;

			$args = array( 
				'posts_per_page' => $posts_to_send, 
				'offset' => $offset,
				'post_type' => 'post',
				'orderby' => 'ID',
				'order' => 'ASC'
			);

			// the query
			$the_query = new WP_Query( $args );			

			if ( $the_query->have_posts() ) :

			  $counter = 0;

			  while ( $the_query->have_posts() ) : $the_query->the_post();

				$post_content = str_replace(array("\r\n","\n","\r"),'<br />',get_the_content());

				$post_json = array(
					'title' => htmlentities(get_the_title(), ENT_QUOTES),
					'url'	=> get_permalink(),
					'content' => htmlentities($post_content, ENT_QUOTES),
					'blog'	  => $api_key,
					'author'  => htmlentities(get_the_author()),
					'post_id' => get_the_ID()
				);				

				$post_json = json_encode($post_json);

				$response = wp_remote_post( WTT_API.'advertiser/add_post', array(
					'method' => 'POST',
					'headers' => array(
						'Accept'       => 'application/json',
	        			'Content-Type'   => 'application/json',
	        			'Content-Length' => strlen( $post_json )
					),
					'body' => $post_json
				    )
				);		
							
				if($response['body']==='false'){
					$badBatch = true;		
				}

				$counter++;

			  endwhile;

			  wp_reset_postdata();

			  if($badBatch!==true){
			  	update_option('wtt_loop', ($wtt_loop+1) );
			  	$total_posts_sent = get_option('wtt_total_posts_sent');
			  	update_option('wtt_total_posts_sent', ($total_posts_sent+$counter) );
			  }
			  
			endif;

			if( !wp_next_scheduled('wtt_send_more_posts')  && get_option('wtt_total_posts')!==get_option('wtt_total_posts_sent') ) {  
				// If we haven't reach the limit.
				wp_schedule_event( time(), 'oneminute', 'wtt_send_more_posts' );  
			}  elseif ( get_option('wtt_total_posts')===get_option('wtt_total_posts_sent') ){
				// If we reach the limit then delete the WP cron hook.
				$timestamp = wp_next_scheduled( 'wtt_send_more_posts' );
				wp_unschedule_event( $timestamp, 'wtt_send_more_posts' );
				update_option('wtt_posts_sent_ok','yes');
			}

		}
	}	
}

register_activation_hook(__FILE__, 'wtt_activation');


// Let's run the Deactivation Process
function wtt_deactivation() {

	remove_role( 'wtt_user' );	
	wp_delete_user( get_option("wtt_user_id") );

}

register_deactivation_hook(__FILE__, 'wtt_deactivation');

// Register Plugin Errors
add_action('activated_plugin','save_error');

function save_error(){
    update_option('wtt_plugin_error',  ob_get_contents());
}

// Remove WP Canonical function
remove_action( 'wp_head', 'rel_canonical' );
add_action( 'wp_head', 'wtt_rel_canonical' );


function wtt_rel_canonical(){
// original code
  if ( !is_singular() )
    return;
  global $wp_the_query;
  if ( !$id = $wp_the_query->get_queried_object_id() )
    return;
 
  // new code - if there is a meta property defined
  // use that as the canonical url
  $canonical = get_post_meta( $id, 'wtt_canonical' );
  if( !empty($canonical) ) {
  	foreach($canonical as $canonical_url):
    	echo "<link rel='canonical' href='$canonical_url' />\n";
    endforeach;
    return;
  }
 
  // original code
  $link = get_permalink( $id );
  if ( $page = get_query_var('cpage') )
    $link = get_comments_pagenum_link( $page );
  echo "<link rel='canonical' href='$link' />\n";
	
}

// WP Footer JS Injection for Pixel License
function wtt_pixel_license_code(){
	global $post;
	if(is_singular()){
		$pixel_code = get_post_meta( $post->ID, 'wtt_pixel_code' );

		if( !empty($pixel_code) ) {
			foreach($pixel_code as $pixel_code_url):
				echo $pixel_code_url."\n";
				//echo "<script type='text/javascript' src='$pixel_code_url'></script>\n";
			endforeach;
		}
	}	
}

add_action('wp_footer', 'wtt_pixel_license_code');

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

add_action('wp', 'wtt_parse_request');

function wtt_query_vars($vars) {
    $vars[] = 'written';
    return $vars;
}

add_filter('query_vars', 'wtt_query_vars');


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

add_filter('body_class','wtt_body_class_names');

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

add_action('wp_footer', 'wtt_takeover_license_code');


// WP Header 301 Redirect
function wtt_redirect_license(){
	global $post;

	if(is_singular()){
		$redirect_url = get_post_meta($post->ID, 'wtt_redirect', true);
		if($redirect_url){ wp_redirect($redirect_url, 301); }
	}	
}

add_action('template_redirect', 'wtt_redirect_license');

// Hook to send new published or update posts to Written API
function wtt_send_post($new_status, $old_status, $post_id){
	$api_key = get_option('wtt_api_key');
	$post = get_post($post_id);
	$author_ID = $post->post_author;
	$written_user_ID = get_option('wtt_user_id');

	if( $new_status == 'publish' && $post->post_type == 'post' && $author_ID!==$written_user_ID ){
			
			$post_content = str_replace(array("\r\n","\n","\r"),'<br />',$post->post_content);

			$post_json = array(
				'title' => htmlentities($post->post_title, ENT_QUOTES),
				'url'	=> get_permalink($post->ID),
				'content' => htmlentities($post_content, ENT_QUOTES),
				'blog'	  => $api_key,
				'author'  => htmlentities(get_the_author_meta('display_name',$post->post_author), ENT_QUOTES),
				'post_id' => $post->ID
			);		 	

			$post_json = json_encode($post_json);
			update_option('wtt_plugin_error', $post_json);

			$response = wp_remote_post( WTT_API.'advertiser/add_post', array(
				'method' => 'POST',
				'headers' => array(
					'Accept'       => 'application/json',
        			'Content-Type'   => 'application/json',
        			'Content-Length' => strlen( $post_json )
				),
				'body' => $post_json
			    )
			);				
	}
}

add_action( 'transition_post_status', 'wtt_send_post', 10, 3 );


// Hook to send delete notification to Written API
function wtt_delete_post_notification($post_id) {
	global $post_type;   
    if ( $post_type !== 'post' ) return;
    $wtt_api_key  = get_option('wtt_api_key');

	$response = wp_remote_post( WTT_API.'admin/delPost/'.$wtt_api_key.'/'.$post_id, array(
		'method' => 'DELETE'
	    )
	);		    

	update_option('wtt_plugin_error', '');
	update_option('wtt_plugin_error',  $response['body']);
}

add_action( 'delete_post', 'wtt_delete_post_notification', 10 );

// Add CSS
function wtt_js_css(){
	// Color Picker CSS and JS
	wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker-script', plugins_url('js/scripts.js', __FILE__ ), array( 'wp-color-picker' ), false, true );	

    // Written CSS
	wp_register_style('written_css', plugins_url('css/written.css',__FILE__ ));
	wp_enqueue_style('written_css');

	// Media Upload CSS and JS
	/*
	if(function_exists( 'wp_enqueue_media' )){
	    wp_enqueue_media();
	}else{
	    wp_enqueue_style('thickbox');
	    wp_enqueue_script('media-upload');
	    wp_enqueue_script('thickbox');
	}*/
	wp_enqueue_script( 'wtt-upload', plugins_url('js/upload.js', __FILE__ ));
	wp_enqueue_script('wtt-upload');	
}

add_action( 'admin_init','wtt_js_css');

// Get user Role
function get_user_role($id){
    $user = new WP_User($id);
    return array_shift($user->roles);
}

// Disable edit post if was created by writtenbot
function wtt_restrict_editing_posts( $allcaps, $cap, $args ) {

    // Bail out if we're not asking to edit a post ...
    if( 'edit_post' != $args[0] && 'delete_post' != $args[0] || empty( $allcaps['edit_posts'] ) )
        return $allcaps;

    // Load the post data:
    $post = get_post( $args[2] );
    $aid = $post->post_author;
    $redirect = get_post_meta( $post->ID, 'wtt_redirect', true);

    // Bail out if the post isn't published:
    if( 'publish' != $post->post_status )
        return $allcaps;

    if( get_user_role($aid)==='wtt_user' || !empty($redirect) ) {
        //Then disallow editing.
        $allcaps[$cap[0]] = FALSE;
    }
    return $allcaps;
}
add_filter( 'user_has_cap', 'wtt_restrict_editing_posts', 10, 3 );

// Change author name
add_filter( 'get_the_author_user_url', 'wtt_guest_author_url' ); 
add_filter( 'author_link', 'wtt_guest_author_url' ); 
add_filter( 'the_author', 'wtt_guest_author_link' ); 
add_filter( 'get_the_author_display_name', 'wtt_guest_author_name' );

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

function wtt_guest_author_link($name) {
	global $post;

	$guest_url = get_post_meta( $post->ID, 'wtt_author_url', true );
	$guest_name = get_post_meta( $post->ID, 'wtt_custom_author', true );

	if ( $guest_name && filter_var($guest_url, FILTER_VALIDATE_URL) ) {
		return '<a href="'.$guest_url.'" title="' . esc_attr( sprintf(__("Visit %s&#8217;s website"), $guest_name) ) . '" rel="nofollow">' . $guest_name . '</a>';
	} elseif( $guest_name ) {
		return $guest_name;
	}

	return $name;
}

function wtt_guest_author_name( $name ) {
	global $post;
	$guest_name = get_post_meta( $post->ID, 'wtt_custom_author', true );

	if ( $guest_name ) return $guest_name;
	return $name;
}

// Options Page and Verify Ownership
include_once('written-options.php');

// Custom XMLRPC Calls
include_once('written-xmlrpc.php');

// Check for updates
require('written-update-notifier.php');

