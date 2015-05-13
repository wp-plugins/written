<?php
// Set the Options Page

function wtt_display_settings(){
	global $written_licensing_plugin;
	$send_auth = '';
?>

<div class="wrap">
	<h2>Written Settings</h2>
		
	<?php

	if (isset($_POST["update_settings"])) { 
		if($_POST["wtt_email"]==='' || !is_email($_POST['wtt_email'])){
			echo '<div id="setting-error-settings_updated" class="error settings-error"> <p><strong>You did not enter a valid email address.  Please enter your email address.</strong></p></div>';
		} else {

			$send_auth = $written_licensing_plugin->send_auth();

			if($send_auth) {

				switch($send_auth) {

					case 'success':

						echo '<div id="setting-error-settings_updated" class="updated settings-error"> <p><strong>Success!  Your plugin has been installed.  Please clear the cache on your blog.</strong></p></div>';

					break;

					default:

						echo '<div id="setting-error-settings_updated" class="error settings-error"> <p><strong>'.$send_auth.'</strong></p></div>';

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

		<p><strong>Your blog is connected to Written!  You can login to your Written account at <a href="https://written.com/auth/login">https://written.com/auth/login</a>.<br /><br />Your login email is <?php echo get_option('wtt_email'); ?>.</strong></p>

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

		<p><small><a href="https://written.com/bloggers/" target="_blank">What is Written?</a></small></p>
		<?php endif; ?>		
	</form>
</div>
	
<?php

}

?>