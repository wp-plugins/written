<?php
/*
Plugin Name: Written
Plugin URI: http://www.written.com/
Description: Plugin for Advertisers and Publishers.
Version: 2.5.5
Author: Written.com
Author URI: http://www.written.com
*/

define("WTT_API", "http://app.written.com/", true);
define("WTT_EMAIL", "api@written.com", true);
define("WTT_USER", "writtenapi_", true);

class Written_Licensing_Plugin {

	var $version = 2.5;

	public function bootstrap() {
		/* Written Options Panel */
		require_once(plugin_dir_path( __FILE__ ) . 'written-options.php');

		/* Written Additional XMLRPC methods */
		require_once(plugin_dir_path( __FILE__ ) . 'written-xmlrpc.php');

		/* Written / Brute Protect Partnership */
		//require_once(plugin_dir_path( __FILE__ ) . 'bruteprotect-install.php');

		/* Written License Types Functionality */
		require_once(plugin_dir_path( __FILE__ ) . 'written-adbuyout.php');
		require_once(plugin_dir_path( __FILE__ ) . 'written-safe-syndication.php');
		require_once(plugin_dir_path( __FILE__ ) . 'written-content-traffic.php');

		register_activation_hook(__FILE__, array($this,'activate'));
		register_deactivation_hook(__FILE__, array($this,'deactivate'));

		add_action('admin_menu', array($this,'plugin_settings'));
		add_action('init',array($this,'register_meta'));
		add_action('admin_init', array($this,'plugin_redirect'));
		add_action('wp_footer', array($this,'page_tracking'));
		add_action( 'wp_enqueue_scripts', array($this,'written_styles') ,1);
		//add_action('save_post', array($this,'strip_back_slashses'));

		if(get_option('wtt_plugin_version_number') != $this->version)
			$this->activate();
	}

	/**
	* This the Written.com activation process.
	* In this activation process, a role called Written User is created.
	* This user allows us to interact with only the posts that you choose to license through Written.
	* This also remove any previously added API key.
	* Finally, redirect the user to the Written options panel upon activation.
	*/
	public function activate() {

		remove_role('wtt_user');
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
		update_option('wtt_plugin_version_number',$this->version);
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
		delete_option('wtt_plugin_version_number');
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

	/*
	A better solution for this is needed.  Security risk by implementing this way 
	Removed on version 2.5.1
	public function strip_back_slashses($post_id)	{

		$post = get_post($post_id);

		$data['ID'] = $post_id;
		$data['post_title'] =  str_replace('\\','',$post->post_title);
		$data['post_content'] =  str_replace('\\','',$post->post_content);

		remove_action( 'save_post', array($this,'strip_back_slashses') );
		$update = wp_update_post($data);

		add_action( 'save_post', array($this,'strip_back_slashses') );
		return $update;

	}*/
	

	/**
	* This returns plugin data on the Written.com WordPress plugin. 
	*/
	public function plugin_info() {
		$plugin_data = get_plugin_data( __FILE__ );
		return $plugin_data;
	}

	/**
	* This is plugin redirect hook.  If the redirect option is present, the user is redirected and the option is deleted.
	*/
	public function plugin_redirect() {

		if(get_option('wtt_plugin_do_activation_redirect')) {
			wp_redirect(admin_url('admin.php?page=written_settings'));
			delete_option('wtt_plugin_do_activation_redirect');
		}
	}
	

	/**
	* This checks to see if the Written API has stored the analytics tracking ID in the options table.  If so, we output the Written.com tracking analytics.
	*/
	public function page_tracking() {

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

	/**
	* This adds the Written takeover stylesheet to the head of all pages.
	*/
	public function written_styles() {
		wp_register_style('wtt_takeover_css', plugins_url('css/written-template.css',__FILE__ ));
	}

	/**
	* This creates the XMLRPC user and returns the username and password as an array.
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
	*/
	public function send_auth(){
		$domain = site_url();
		$wtt_api_key  = get_option('wtt_api_key');
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

	public function xmlrpc_check() {
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
}


global $written_licensing_plugin;
$written_licensing_plugin = new Written_Licensing_Plugin();
$written_licensing_plugin->bootstrap();