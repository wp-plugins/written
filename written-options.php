<?php
// Set the Options Page

add_action('admin_menu', 'wtt_plugin_settings');

function wtt_plugin_settings() {

	add_menu_page('Written Settings', 'Written Settings', 'activate_plugins', 'written_settings', 'wtt_display_settings',plugins_url('img/written-icon.png', __FILE__ ));

}

function wtt_display_settings(){

	$api_key = get_option('wtt_api_key');
	$send_auth = '';
?>

<div class="wrap">
	<h2>Written Settings</h2>
		

	<?php

	$xmlrpc_status = wtt_is_xmlrpc_enabled();

	if($xmlrpc_status === false) {
		echo '<div id="setting-error-settings_updated" class="error settings-error"> <p><strong>In order to use Written on your blog, you need to enable XMLRPC.</strong>  Please contact bloggers@written.com if you need help enabling XMLRPC on your blog.</p></div>';
	}

	if (isset($_POST["update_settings"])) { 
		if($_POST["wtt_email"]==='' || !is_email($_POST['wtt_email']) && !$api_key ){
			echo '<div id="setting-error-settings_updated" class="error settings-error"> <p><strong>You did not enter a valid email address.  Please enter your email address.</strong></p></div>';
		} else {

			$send_auth = wtt_send_auth();

			if($send_auth) {

				switch($send_auth) {

					case 'invalid-api-key':

						echo '<div id="setting-error-settings_updated" class="error settings-error"> <p><strong>Something went wrong.  Please try again.</strong></p></div>';

					break;


					case 'success':

						echo '<div id="setting-error-settings_updated" class="updated settings-error"> <p><strong>Success!  Your plugin has been installed.  Please clear the cache on your blog.</strong></p></div>';

					break;
				}

				
			} else {

				echo '<div id="setting-error-settings_updated" class="error settings-error"> <p><strong>Something went wrong.  Please try again.</strong></p></div>';
			
			}
		}

	}
	?>
	<form action="" method="POST">
		<input type="hidden" name="update_settings" value="Y" />  
		<input type="hidden" name="action" value="update" />		
		<input type="hidden" name="wtt_email" value="<?php echo get_option('wtt_email'); ?>" />		
		<input type="hidden" name="wtt_api_key" value="<?php echo get_option('wtt_api_key'); ?>" />		

		<?php if(get_option('wtt_email') || $send_auth == 'success'): ?>

		<p><strong>Your blog is connected to Written!  You can login to your Written account at <a href="http://app.written.com">http://app.written.com</a>.<br /><br />Your login email is <?php echo get_option('wtt_email'); ?>.</strong></p>

		<div style="display: inline">
		<?php submit_button('Resync with Written');  ?>
		</div>
		<?php else: ?>
		<p>To add your blog to Written, please enter your email and click "Connect to Written".<br />If you already created an account on Written, please enter the email you signed up with below.</p>
		<table class="form-table"> 
			<tr valign="top">
				<th scope="row">
					<label for="wtt_email">Email Address:</label>
				</th>
				<td>
					<input type="text" id="wtt_email" name="wtt_email" value="" class="regular-text" />
				</td>
			</tr>

			<tr valign="top">
				<th scope="row">
					<label for="wtt_wp_address">WP Address:</label>
				</th>
				<td>
					<input type="text" id="wtt_wp_address" disabled="disabled" name="wtt_wp_address" value="<?php echo site_url(); ?>" class="regular-text" />
				</td>
			</tr>
		</table>
		<?php submit_button('Connect to Written');  ?>

		<p><small><a href="http://written.com/bloggers/" target="_blank">What is Written?</a></small></p>
		<?php endif; ?>			
		
		

		
	</form>
	<?php show_bruteprotect_install_button( 'written' ); ?>
</div>
	
<?php

}

?>