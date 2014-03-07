<?php
/*
Plugin Name: Written
Plugin URI: http://www.written.com/
Description: Plugin for Advertisers and Publishers.
Version: 2.1
Author: Written.com
Author URI: http://www.written.com
*/

define("WTT_API", "http://app.written.com/", true);
define("WTT_EMAIL", "api@written.com", true);
define("WTT_USER", "writtenapi_", true);


/**
* This the Written.com activation process.
* In this activation process, we create a user role called Written User.
* We also remove any previously added API key.
* Finally, we redirect the user to the Written options panel upon activation.
*/
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

/**
* This is plugin redirect hook.  If the redirect option is present, the user is redirected and the option is deleted.
*/
function wtt_plugin_redirect() {

	if(get_option('wtt_plugin_do_activation_redirect')) {
		wp_redirect(admin_url('admin.php?page=written_settings'));
		delete_option('wtt_plugin_do_activation_redirect');
	}
}
add_action('admin_init', 'wtt_plugin_redirect'); 

/**
* This is the deactivation process which removes the custom user role and deletes the Written user we created.
*/
function wtt_deactivation() {
	remove_role( 'wtt_user' );	
	wp_delete_user( get_option("wtt_user_id") );
}
register_deactivation_hook(__FILE__, 'wtt_deactivation');


/**
* This checks to see if the Written API has stored the analytics tracking ID in the options table.  If so, we output the Written.com tracking analytics.
*/
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

/**
* This creates the XMLRPC user and returns the username and password as an array.
*/
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

/**
* This function sends the username and password to the written api.
*/
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

		switch($response['body']) {

			case 'invalid-api-key':

				return 'invalid-api-key';

			break;

			default:

			$piwik_id = update_option('wtt_tracking_id',$response['body']);
			return 'success';

			break;

		}
	}
}

/**
* This function takes the ID of a user and returns the user role.
*/
function get_user_role($id){
    $user = new WP_User($id);
    return array_shift($user->roles);
}

/**
* This function restricts you from editing a post if it is currently being licensed.
*/
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
//add_filter( 'user_has_cap', 'wtt_restrict_editing_posts', 10, 3 );


/**
* This is the syndication license functionality.
* http://written.com/content-licensing/
*/
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
remove_action( 'wp_head', 'rel_canonical' );
add_action( 'wp_head', 'wtt_rel_canonical' );


/**
* This is the content and traffic license functionality.
* http://written.com/content-licensing/
*/
function wtt_redirect_license(){
	global $post;

	if(is_singular()){
		$redirect_url = get_post_meta($post->ID, 'wtt_redirect', true);
		if($redirect_url){ wp_redirect($redirect_url, 301); }
	}	
}
add_action('template_redirect', 'wtt_redirect_license');


/**
* This is the pixel license functionality.
* http://written.com/content-licensing/
*/
function wtt_pixel_license_code(){
	global $post;
	if(is_singular()){
		$pixel_code = get_post_meta( $post->ID, 'wtt_pixel_code' );

		if( !empty($pixel_code) ) {
			foreach($pixel_code as $pixel_code_url):
				echo $pixel_code_url."\n";
			endforeach;
		}
	}	
}

require_once(plugin_dir_path( __FILE__ ) . 'written-options.php');
require_once(plugin_dir_path( __FILE__ ) . 'written-xmlrpc.php');