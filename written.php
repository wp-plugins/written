<?php
/*
Plugin Name: Written
Plugin URI: http://www.written.com/
Description: Plugin for Advertisers and Publishers.
Version: 2.0
Author: Written.com
Author URI: http://www.written.com
*/

define("WTT_API", "http://app.written.com/", true);
define("WTT_EMAIL", "api@written.com", true);
define("WTT_USER", "writtenapi_", true);

include_once('written-canonical.php');
include_once('written-crons.php');

include_once('written-full.php');
include_once('written-options.php');
include_once('written-xmlrpc.php');
include_once('written-pixel.php');

/* Not currently supporting takeover license */

//include_once('written-takeover.php');

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
	
	delete_option('wtt_api_key');
	add_option('wtt_plugin_do_activation_redirect',true);

}
register_activation_hook(__FILE__, 'wtt_activation');


function wtt_plugin_redirect() {

	if(get_option('wtt_plugin_do_activation_redirect')) {
		wp_redirect(admin_url('admin.php?page=written_settings'));
		delete_option('wtt_plugin_do_activation_redirect');
	}
}
add_action('admin_init', 'wtt_plugin_redirect'); 

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


function wtt_page_tracking() {

	if(get_option('wtt_tracking_id')):

?>

<!-- Written.com Tracker -->
<script type="text/javascript">
  var _paq = _paq || [];
  _paq.push(["trackPageView"]);
  _paq.push(["enableLinkTracking"]);

  (function() {
    var u=(("https:" == document.location.protocol) ? "https" : "http") + "://analytics.written.com/";
    _paq.push(["setTrackerUrl", u+"piwik.php"]);
    _paq.push(["setSiteId", "<?php echo get_option('wtt_tracking_id'); ?>"]);
    var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0]; g.type="text/javascript";
    g.defer=true; g.async=true; g.src=u+"piwik.js"; s.parentNode.insertBefore(g,s);
  })();
</script>

<?php

	endif;
}

add_action('wp_footer', 'wtt_page_tracking');

/* This function creates the xmlrpc user and returns the username and password for that user */
function wtt_create_xml_user() {

	$wp_username = WTT_USER.rand(1000, 9999);

	$user_id = username_exists( $wp_username );
	$random_password = wp_generate_password( 12, false );

	if ( !$user_id  and email_exists(WTT_EMAIL) == false ) {
		
		$user_id = wp_insert_user( 
			array ( 
				'user_login' => $wp_username, 
				'user_email' => WTT_EMAIL,
				'user_pass'  => $random_password,
				'role'		 => 'wtt_user'
			) 
		);
		update_option("wtt_user_id", $user_id);

		$output = array(
			'user_login' => $wp_username,
			'user_password' => $random_password
		);

	}  else {
		// this means that we already have a wtt_user and just need to change password and resend.
		update_option("wtt_user_id", email_exists(WTT_EMAIL));
		$user_data = get_userdata(get_option('wtt_user_id'));

		wp_update_user(array(
			'ID' => get_option('wtt_user_id'),
			'user_pass' => $random_password
		));
		$output = array(
			'user_login' => $user_data->user_login,
			'user_password' => $random_password
		);

	}

	return $output;
}

/* This function sends the username and password to the written api */
function wtt_send_auth(){
	$wtt_api_key  = get_option('wtt_api_key');
	$create_user = wtt_create_xml_user();

	$data = array(
		'api_key' => $wtt_api_key,
		'written_username' => $create_user['user_login'],
		'written_password' => $create_user['user_password']
	);

	$response = wp_remote_post( WTT_API.'blogs/plugin_install', array(
		'method' => 'POST',
		'body' => $data
	));


	if(is_wp_error($response)) {
		return false;
	} else {
		$piwik_id = update_option('wtt_tracking_id',$response['body']);
		return true;
	}
}

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

/*

Not sure if this is all needed right now:

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
}*/