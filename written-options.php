<?php
// Set the Options Page

add_action('admin_menu', 'wtt_plugin_settings');

function wtt_plugin_settings() {

	add_menu_page('Written Settings', 'Written Settings', 'activate_plugins', 'written_settings', 'wtt_display_settings');

}

function wtt_display_settings(){

    $api_key 		= get_option('wtt_api_key', '');
    $plugin_error 	= get_option('wtt_plugin_error');

    $status = '';
    $statusMessage = '';
?>

<div class="wrap">
	
	<?php screen_icon('options-general'); ?>

	<h2>Written Settings</h2>
<?php

	if (isset($_POST["update_settings"])) { 
		if($_POST["wtt_api_key"]===''){
			$status = 'invalid';
			$statusMessage = 'INVALID';
			update_option("wtt_api_key", ""); 

			echo '<div id="setting-error-settings_updated" class="error settings-error"> <p><strong>Please enter an API key.</strong></p></div>';
		} else {
			$api_key = esc_attr($_POST["wtt_api_key"]); 
			update_option("wtt_api_key", $api_key);
			$send_auth = wtt_send_auth();

			if($send_auth) {
				echo '<div id="setting-error-settings_updated" class="updated settings-error"> <p><strong>Success!  Your plugin has been installed.</strong></p></div>';
			} else {
				echo '<div id="setting-error-settings_updated" class="error settings-error"> <p><strong>Something went wrong.  Please try again.</strong></p></div>';
			}
		}

	}
?>
	<form action="" method="POST">
		<input type="hidden" name="update_settings" value="Y" />  
		<input type="hidden" name="action" value="update" />		
		<table class="form-table"> 
			<tr valign="top">
				<th scope="row">
					<label for="wtt_api_key">Your Written.com API Key:</label>
				</th>
				<td>
					<input type="text" id="wtt_api_key" name="wtt_api_key" value="<?php echo $api_key; ?>" class="regular-text" />
					<?php 
						if(get_option('wtt_api_key')){
							$status = 'valid';
							$statusMessage = 'VALID API KEY';
						} else {
							$status = 'invalid';
							$statusMessage = 'Please enter a valid API key.';
						}
					?>
					<div class="under-input key-status <?php echo $status; ?>"><?php echo $statusMessage; ?></div>
				</td>
			</tr>
		</table>			
		
		<?php submit_button('Save Settings');  ?>
	</form>
</div>
	
<?php

}

?>