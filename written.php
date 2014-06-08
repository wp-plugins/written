<?php
/*
Plugin Name: Written
Plugin URI: http://www.written.com/
Description: Plugin for Advertisers and Publishers.
Version: 2.4.1
Author: Written.com
Author URI: http://www.written.com
*/

define("WTT_API", "http://app.written.com/", true);
define("WTT_EMAIL", "api@written.com", true);
define("WTT_USER", "writtenapi_", true);

/**
* This the Written.com activation process.
* In this activation process, a role called Written User is created.
* This user allows us to interact with only the posts that you choose to license through Written.
* This also remove any previously added API key.
* Finally, redirect the user to the Written options panel upon activation.
*/
function wtt_activation() {
	$result = add_role(
		'wtt_user',
		'Written User',
		array(
			'delete_pages'   => true,  
			'delete_published_posts' => true,
			'delete_posts'   => true,
			'edit_pages' 	 => true, 
			'edit_posts' 	 => true, 
			'read' 			 => true, 
			'publish_posts'  => true, 
			'publish_pages'  => true, 
			'edit_others_posts' => true,
			'edit_published_posts' => true,
			'edit_others_pages' => true,
			'edit_published_pages' => true,
			'upload_files' => true,
		)
	);
	
	add_option('wtt_plugin_do_activation_redirect',true);

}
register_activation_hook(__FILE__, 'wtt_activation');



function wtt_register_meta() {
	$remove_stack = array(
		'_wtt_license_type',
		'_wtt_adbuyout_header_link',
		'_wtt_adbuyout_left_link',
		'_wtt_adbuyout_right_link',
		'_wtt_adbuyout_advertiser_name',
		'_wtt_adbuyout_advertiser_link',
		'_wtt_adbuyout_bg_image_url',
		'_wtt_adbuyout_mobile_image_url',
		'_wtt_adbuyout_mobile_link',
		'_wtt_adbuyout_sidebar_html',
		'_wtt_adbuyout_bg_color',
		'_wtt_redirect_location',
		'_wtt_canonical',
	);

	foreach($remove_stack as $key)
		register_meta( 'post', $key , 'wtt_sanitize_cb', 'wtt_yes_you_can');	
}
add_action('init','wtt_register_meta');

function wtt_sanitize_cb ( $meta_value, $meta_key, $meta_type ) {
	return $meta_value;
}

function wtt_yes_you_can ( $can, $key, $post_id, $user_id, $cap, $caps ) {
	return true;
}

/**
* This returns plugin data on the Written.com WordPress plugin. 
*/
function wtt_plugin_info() {
	$plugin_data = get_plugin_data( __FILE__ );
	return $plugin_data;
}

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

	delete_option('wtt_tracking_id');
	delete_option('wtt_api_key');
	delete_option('wtt_email');
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
* This adds the Written takeover stylesheet to the head of all pages.
*/
function wtt_styles() {
	wp_register_style('wtt_takeover_css', plugins_url('css/written-template.css',__FILE__ ));
}
add_action( 'wp_enqueue_scripts', 'wtt_styles' );

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
	$domain = site_url();
	$wtt_api_key  = get_option('wtt_api_key');
	$create_user = wtt_create_xml_user();

	$email = $_POST['wtt_email'];

	$data = array(
		'domain' => $domain,
		'written_username' => $create_user['user_login'],
		'written_password' => $create_user['user_password']
	);

	if($email) {
		$data['email'] = $email;
	}

	if($wtt_api_key) {
		$data['api_key'] = $wtt_api_key;
	}

	
	$response = wp_remote_post( WTT_API.'blogs/plugin_install', array(
		'method' => 'POST',
		'body' => $data
	));


	if(is_wp_error($response)) {
		return 'invalid-api-key';
	} else {
 		

		switch($response['body']) {

			case 'invalid-api-key':

				
				delete_option('wtt_api_key');
				return 'invalid-api-key';
			break;

			default:

			$output = explode(',',$response['body']);

			if(!is_numeric($output[0]))
				return 'invalid-api-key';
			

			update_option('wtt_tracking_id',$output[0]);
			update_option('wtt_api_key',$output[1]);
			update_option('wtt_email',$output[2]);

			return 'success';

			break;

		}
	}
}

function wtt_is_xmlrpc_enabled() {
	$returnBool = false; 
	$enabled = get_option('enable_xmlrpc'); //for ver<3.5

	if($enabled) {
		return true;
	} else {
		global $wp_version;
		
		if (version_compare($wp_version, '3.5', '>=')) {
			return true;
		} else {
			return false;
		}  
	}
}


/* Written Options Panel */
require_once(plugin_dir_path( __FILE__ ) . 'written-options.php');

/* Written Additional XMLRPC methods */
require_once(plugin_dir_path( __FILE__ ) . 'written-xmlrpc.php');

/* Written / Brute Protect Partnership */
require_once(plugin_dir_path( __FILE__ ) . 'bruteprotect-install.php');

/* Written License Types Functionality */
require_once(plugin_dir_path( __FILE__ ) . 'written-adbuyout.php');
require_once(plugin_dir_path( __FILE__ ) . 'written-safe-syndication.php');
require_once(plugin_dir_path( __FILE__ ) . 'written-content-traffic.php');