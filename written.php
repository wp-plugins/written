<?php
/*
Plugin Name: Written
Plugin URI: http://www.written.com/
Description: Plugin for Advertisers and Publishers.
Version: 3.0.8
Author: Written.com
Author URI: http://www.written.com
*/

define("WTT_API", "http://written.com/", true);
define("WTT_EMAIL", "api@written.com", true);
define("WTT_USER", "writtenapi_", true);

class Written_Licensing_Plugin {

	var $version = '3.0.8';

	var $redirect_key = 'wtt_plugin_do_activation_redirect';
	var $plugin_version_key = 'wtt_plugin_version_number';

	public function bootstrap() {
		/* Written Options Panel */
		require_once(plugin_dir_path( __FILE__ ) . 'written-options.php');

		/* Written API */
		require_once(plugin_dir_path( __FILE__ ) . 'written-api.php');

		/* Written License Types Functionality */
		require_once(plugin_dir_path( __FILE__ ) . 'written-adbuyout.php');
		require_once(plugin_dir_path( __FILE__ ) . 'written-safe-syndication.php');
		require_once(plugin_dir_path( __FILE__ ) . 'written-content-traffic.php');

		register_activation_hook(__FILE__, array($this,'activate'));
		register_deactivation_hook(__FILE__, array($this,'deactivate'));

		add_action('admin_menu', array($this,'plugin_settings'));
		add_action('init',array($this,'register_meta'));
		add_action('admin_init', array($this,'plugin_redirect'));
		add_action( 'wp_enqueue_scripts', array($this,'written_styles') ,1);

		if(get_option( $this->plugin_version_key ) != $this->version)
		{
			$this->activate();
		}
			

		$written_api = new Written_API_Endpoint();
	}

	/**
	* This the Written.com activation process.
	* In this activation process, a role called Written User is created.
	* This user allows us to interact with only the posts that you choose to license through Written.
	* This also remove any previously added API key.  API key not needed.
	* Finally, redirect the user to the Written options panel upon activation.
	*/
	public function activate() {
		
		remove_role('wtt_user');
		$result = add_role(
			'wtt_user',
			'Written User',
			array(
				'delete_pages'   => false,  
				'delete_published_posts' => false,
				'delete_posts'   => false,
				'edit_pages' 	 => false, 
				'edit_posts' 	 => false, 
				'read' 			 => false, 
				'publish_posts'  => false, 
				'publish_pages'  => false, 
				'edit_others_posts' => false,
				'edit_published_posts' => false,
				'edit_others_pages' => false,
				'edit_published_pages' => false,
				'upload_files' => false,
			)
		);	

		add_option( $this->redirect_key ,true);
		update_option($this->plugin_version_key ,$this->version);
	}

	/**
	* This is the deactivation process which removes the custom user role and deletes the Written user we created.
	*/
	public function deactivate() {
		remove_role( 'wtt_user' );	
		wp_delete_user( get_option("wtt_user_id") );

		delete_option('wtt_tracking_id');
		delete_option('wtt_api_key');
		delete_option('wtt_email');
		delete_option( $this->plugin_version_key );
	}



	public function plugin_settings() {

		add_menu_page('Written Settings', 'Written Settings', 'activate_plugins', 'written_settings', 'wtt_display_settings',plugins_url('img/written-icon.png', __FILE__ ));

	}

	

	public function register_meta() {
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
			'_wtt_is_written_post'
		);

		foreach($remove_stack as $key)
			register_meta( 'post', $key , array($this,'sanitize_cb'), array($this,'yes_you_can'));	
	}

	public function sanitize_cb ( $meta_value, $meta_key, $meta_type ) {
		return $meta_value;
	}

	public function yes_you_can ( $can, $key, $post_id, $user_id, $cap, $caps ) {
		return true;
	}
	

	/**
	* This is plugin redirect hook.  If the redirect option is present, the user is redirected and the option is deleted.
	*/
	public function plugin_redirect() {

		if(get_option( $this->redirect_key ))
		{
			delete_option( $this->redirect_key );
			wp_redirect(admin_url('admin.php?page=written_settings'));
		}
	}
	

	/**
	* This adds the Written takeover stylesheet to the head of all pages.
	*/
	public function written_styles() {
		wp_register_style('wtt_takeover_css', plugins_url('css/written-template.css',__FILE__ ));
	}

	/**
	* This creates the XMLRPC user and returns the username and password as an array.
	* @return array with user_login,user_password values
	*/
	public function create_xml_user() {

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
	* @return 'success' or 'error'
	*/
	public function send_auth(){
		$domain = site_url();
		$create_user = $this->create_xml_user();

		$email = $_POST['wtt_email'];

		$data = array(
			'domain' => $domain,
			'written_username' => $create_user['user_login'],
			'written_password' => $create_user['user_password']
		);

		if($email) {
			$data['email'] = $email;
		}


		$written_api = new Written_API_Endpoint();
		$written_api->add_endpoint();
		flush_rewrite_rules();
		
		$request = wp_remote_post( WTT_API.'blogs/plugin_install', array(
			'method' => 'POST',
			'sslverify' => false,
			'body' => $data
		));



		if(is_wp_error($request))
			return $request->get_error_message();
		
		/* maybe need more error handling here */
		$response = json_decode($request['body']);
	 	
	 	if($response->status == 'success') {
			update_option('wtt_email',$response->blog_user_email);
			return 'success';	
	 	}


	 	return 'Something went wrong.  Please try again.';
	 	
	}

}


global $written_licensing_plugin;
$written_licensing_plugin = new Written_Licensing_Plugin();
$written_licensing_plugin->bootstrap();