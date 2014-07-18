<?php
// Set the Options Page

add_action('admin_menu', 'wtt_plugin_settings');

function wtt_plugin_settings() {

	add_menu_page('Written Settings', 'Written Settings', 'activate_plugins', 'written_settings', 'wtt_display_settings',plugins_url('img/written-icon.png', __FILE__ ));

}

function wtt_display_settings(){

	$api_key = get_option('wtt_api_key', '');

?>

<div class="wrap">
	<h2>Written Settings</h2>
<?php

	if (isset($_POST["update_settings"])) { 
		if($_POST["wtt_api_key"]===''){
			update_option("wtt_api_key", ""); 
			echo '<div id="setting-error-settings_updated" class="error settings-error"> <p><strong>You did not enter an API key.  Please enter your API key.</strong></p></div>';
		} else {
			$api_key = esc_attr($_POST["wtt_api_key"]); 
			update_option("wtt_api_key", $api_key);
			$send_auth = wtt_send_auth();

			if($send_auth) {

				switch($send_auth) {

					case 'invalid-api-key':

						echo '<div id="setting-error-settings_updated" class="error settings-error"> <p><strong>You did not enter a valid API key.  Please try again.</strong></p></div>';

					break;


					case 'success':

						echo '<div id="setting-error-settings_updated" class="updated settings-error"> <p><strong>Success!  Your plugin has been installed.  Please clear the cache on your blog.</strong></p></div>';

					break;
				}

				
			} else {

				echo '<div id="setting-error-settings_updated" class="error settings-error"> <p><strong>Something went wrong.  Please enter your API key and click "Save Settings" again.</strong></p></div>';
			
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
				</td>
			</tr>
		</table>			
		
		<?php submit_button('Save Settings');  ?>
	</form>
</div>
	
<?php

}

?>