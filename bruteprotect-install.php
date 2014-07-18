<?php
/**
* Used to install the BruteProtect plugin.
*
* @package BruteProtect
*/

if( !class_exists( 'BruteProtect_Housed_Installer' ) ) {
    class BruteProtect_Housed_Installer
    {
    	
    	function __construct() {
            $this->slug = 'bruteprotect';
			add_action('admin_notices', array( &$this, 'bruteprotect_one_click_admin_notice' ) );
			if( isset( $_GET ) && isset( $_GET[ 'dismiss_oneclick_bruteprotect_shoutout' ] ) )
				add_action('init', array( &$this, 'dismiss_oneclick_bruteprotect_shoutout' ) );
				
    	}
    	
    	function bruteprotect_active() {
    		return is_plugin_active( $this->slug . '/' . $this->slug . '.php' );
    	}
		
		function bruteprotect_one_click_admin_notice() {
			if( (isset($_GET[ 'page' ]) && $_GET[ 'page' ] == 'written_settings') || $this->bruteprotect_active() || (isset($_GET[ 'plugin' ]) && $_GET[ 'plugin' ] == 'bruteprotect') || get_site_option( 'hide_oneclick_bruteprotect_shoutout' ) )
				return;
			?>
			<div class="updated after-h2" style="padding: 20px; border-color: #ff6600; background-color: #fff; position: relative;">Written values your security.  It is imperative for you and for our partners that your site is safe.  That’s why we’ve partnered with BruteProtect, a free service, which will protect you from botnet brute force attacks.<br /> <br /><a href="<?php echo $this->get_install_url(); ?>" class="wp-core-ui button-primary" style="margin-right: 10px" target="_blank">Protect my site in 30 seconds</a>

				

				<strong><a href="<?php echo add_query_arg( 'dismiss_oneclick_bruteprotect_shoutout', 'true' ); ?>">Dismiss this message</a></strong>
			</div>
			<?php 
		}
		
		function dismiss_oneclick_bruteprotect_shoutout() {
			update_site_option( 'hide_oneclick_bruteprotect_shoutout', 1 );
		}

    	function get_install_url() {
            $install_url = wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => 'install-plugin',
                        'plugin' => $this->slug,
                    ),
                    admin_url( 'update.php' )
                ),
                'install-plugin_' . $this->slug
            );
			return $install_url;
    	}
    }
}

$bphi = new BruteProtect_Housed_Installer;



if( !function_exists( 'show_bruteprotect_install_button' ) ) {
	function show_bruteprotect_install_button( $affiliate_code = '' ) {
		$bphi = new BruteProtect_Housed_Installer;
		
		if( $bphi->bruteprotect_active() )
			return;
		
		if( $affiliate_code && !get_site_option( 'bruteprotect_api_key' ) )
			add_site_option( 'bruteprotect_affiliate_code', $affiliate_code );
		
		echo '<a href="' . $bphi->get_install_url() . '"><img src="//bruteprotect.com/assets/written-ad.jpg" /></a>';
	}
}