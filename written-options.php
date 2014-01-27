<?php
// Set the Options Page

add_action('admin_menu', 'wtt_plugin_settings');

function wtt_plugin_settings() {

	add_menu_page('Written Settings', 'Written Settings', 'activate_plugins', 'written_settings', 'wtt_display_settings');

}

function wtt_send_auth(){
	$wtt_api_key  = get_option('wtt_api_key');
	$wtt_password = get_option('wtt_password');

	$user_json = array(
		'api_key'	=> $wtt_api_key,
		'username'	=> get_option('wtt_username'),
		'password'	=> $wtt_password
	);

	$user_json = json_encode($user_json);

	$response = wp_remote_post( WTT_API.'publisher/send_auth', array(
		'method' => 'POST',
		'headers' => array(
			'Accept'       => 'application/json',
			'Content-Type'   => 'application/json',
			'Content-Length' => strlen( $user_json )
		),
		'body' => $user_json
	    )
	);		

	return $response['body'];
}


function wtt_display_settings(){

    $api_key 		= get_option('wtt_api_key', '');
    $username 		= get_option('wtt_username');
    $password 		= get_option('wtt_password');
    $plugin_error 	= get_option('wtt_plugin_error');
    $html_template 	= get_option('wtt_html_template');

	// DEV
    $total_posts	= get_option('wtt_total_posts');
    $total_posts_sent = get_option('wtt_total_posts_sent');
    $wtt_loop 		= get_option('wtt_loop');	    
    $wtt_posts_sent_ok 		= get_option('wtt_posts_sent_ok');

    // Customize Options
    $bg_color 		= get_option('wtt_bg_color');
    $text_color 	= get_option('wtt_text_color');
    $sec_text_color = get_option('wtt_sec_text_color');
    $primary_color 	= get_option('wtt_primary_color');
    $secondary_color= get_option('wtt_secondary_color');
    $logo_url 		= get_option('wtt_logo_url');

    // Label Messages   
    $status = '';
    $statusMessage = '';
?>

<div class="wrap">
	
	<?php screen_icon('options-general'); ?>
	<h2>Written Settings</h2>
	<?php
	
		// Let's save the Settings
		if (isset($_POST["update_settings"])) {  

		    // Customize Options
		    $bg_color 		= esc_attr($_POST["wtt_bg_color"]);
		    $text_color 	= esc_attr($_POST["wtt_text_color"]);
		    $sec_text_color = esc_attr($_POST["wtt_sec_text_color"]);
		    $primary_color 	= esc_attr($_POST["wtt_primary_color"]);
		    $secondary_color= esc_attr($_POST["wtt_secondary_color"]);
		    $logo_url 		= esc_attr($_POST["wtt_logo_url"]);
		    $total_posts	= get_option('wtt_total_posts');
		    $total_posts_sent = get_option('wtt_total_posts_sent');
		    $wtt_loop 		= get_option('wtt_loop');
		    $wtt_posts_sent_ok 		= get_option('wtt_posts_sent_ok');

			if($_POST["wtt_api_key"]===''){
				$status = 'invalid';
				$statusMessage = 'INVALID';
				update_option("wtt_api_key", ""); 
				update_option("wtt_api_valid", "false");
    			update_option('wtt_bg_color', esc_attr($_POST["wtt_bg_color"]));
    			update_option('wtt_text_color', esc_attr($_POST["wtt_text_color"]));
    			update_option('wtt_sec_text_color', esc_attr($_POST["wtt_sec_text_color"]));
    			update_option('wtt_primary_color', esc_attr($_POST["wtt_primary_color"]));
    			update_option('wtt_secondary_color', esc_attr($_POST["wtt_secondary_color"]));
    			update_option('wtt_logo_url', esc_attr($_POST["wtt_logo_url"]));				
	?>
			<div id="setting-error-settings_updated" class="error settings-error"> 
				<p><strong>Please enter an API key.</strong></p>
			</div>	
	<?php				
			} else {
				$api_key = esc_attr($_POST["wtt_api_key"]); 
				update_option("wtt_api_key", $api_key); 
				update_option('wtt_bg_color', esc_attr($_POST["wtt_bg_color"]));
    			update_option('wtt_text_color', esc_attr($_POST["wtt_text_color"]));
    			update_option('wtt_sec_text_color', esc_attr($_POST["wtt_sec_text_color"]));
    			update_option('wtt_primary_color', esc_attr($_POST["wtt_primary_color"]));
    			update_option('wtt_secondary_color', esc_attr($_POST["wtt_secondary_color"]));
    			update_option('wtt_logo_url', esc_attr($_POST["wtt_logo_url"]));	

				$url = wtt_clean_url( get_bloginfo('url') );

				$verify_json = array(
					'url'	=> $url,
					'id'	=> $api_key
				);

				$verify_json = json_encode($verify_json);
				
				$response = wp_remote_post( WTT_API.'publisher/site_verify', array(
					'method' => 'POST',
					'headers' => array(
						'Accept'       => 'application/json',
	        			'Content-Type'   => 'application/json',
	        			'Content-Length' => strlen( $verify_json )
					),
					'body' => $verify_json
				    )
				);	
				
				if($response['body']==='true'){
					$status = 'valid';
					$statusMessage = 'VALID API KEY';
					update_option("wtt_api_valid", "true");
					$send_auth = wtt_send_auth();	
					wtt_send_all_posts();				
	?>					
				<div id="setting-error-settings_updated" class="updated settings-error"> 
					<p><strong>Settings saved. <?php if($send_auth==='true'){ echo 'User sent'; } else{ echo 'User not sent. ERROR: '.$send_auth.'. -'; } ?></strong></p>
				</div>	
	<?php
				} else {
					update_option("wtt_api_valid", "false");
					$status = 'invalid';
					$statusMessage = 'INVALID';					
	?>
				<div id="setting-error-settings_updated" class="error settings-error"> 
					<p><strong>Please enter a valid API key.</strong></p>
				</div>	
	<?php

				}				   				

	?>

	<?php
			}

			if(isset($_POST["wtt_reinstall"])){
				if($_POST["wtt_reinstall"]==='yes'){
					update_option("wtt_install", 'success'); 
					wtt_send_all_posts();
				}
			}
		}  
	?>		
	<form action="" method="POST">		
		<table class="form-table">  

			<tr valign="top">
				<th scope="row">
					<label for="wtt_api_key">Your Written.com API Key:</label>
				</th>
				<td>
					<input type="text" id="wtt_api_key" name="wtt_api_key" value="<?php echo $api_key; ?>" class="regular-text" />
					<?php 
						if(get_option('wtt_api_valid')==='true'){
							$status = 'valid';
							$statusMessage = 'VALID API KEY';
						} else if(get_option('wtt_api_valid')==='false'){
							$status = 'invalid';
							$statusMessage = 'INVALID';
						}
					?>
					<div class="under-input key-status <?php echo $status; ?>"><?php echo $statusMessage; ?></div>
				</td>
			</tr>		

			<tr valign="top" style="display: none">
				<th scope="row">
					<label for="wtt_bg_color">Background Color:</label>
				</th>
				<td>
					<input type="text" name="wtt_bg_color" id="wtt_bg_color" value="<?php echo $bg_color; ?>" class="wp-color-picker-field" data-default-color="#ffffff" />
				</td>
			</tr>	

			<tr valign="top" style="display: none">
				<th scope="row">
					<label for="wtt_text_color">Text Color:</label>
				</th>
				<td>
					<input type="text" name="wtt_text_color" id="wtt_text_color" value="<?php echo $text_color; ?>" class="wp-color-picker-field" data-default-color="#919191" />
				</td>
			</tr>	
			
			<tr valign="top" style="display: none">
				<th scope="row">
					<label for="wtt_sec_text_color">Secondary Text Color:</label>
				</th>
				<td>
					<input type="text" name="wtt_sec_text_color" id="wtt_sec_text_color" value="<?php echo $sec_text_color; ?>" class="wp-color-picker-field" data-default-color="#919191" />
				</td>
			</tr>			

			<tr valign="top" style="display: none">
				<th scope="row">
					<label for="wtt_primary_color">Primary Color:</label>
				</th>
				<td>
					<input type="text" name="wtt_primary_color" id="wtt_primary_color" value="<?php echo $primary_color; ?>" class="wp-color-picker-field" data-default-color="#a5c7ad" />
				</td>
			</tr>	

			<tr valign="top" style="display: none">
				<th scope="row">
					<label for="wtt_secondary_color">Secondary Color:</label>
				</th>
				<td>
					<input type="text" name="wtt_secondary_color" id="wtt_secondary_color" value="<?php echo $secondary_color; ?>" class="wp-color-picker-field" data-default-color="#000000" />
				</td>
			</tr>		
			
			<tr valign="top" style="display: none">
				<th scope="row">
					<label for="wtt_logo_url">Upload Logo:</label>
				</th>
				<td>
					<input id="wtt_logo_url" type="text" name="wtt_logo_url" value="<?php echo $logo_url; ?>" />
					<input id="wtt_logo_url_button" type="button" class="button"  value="Upload Image" />
					<br />Enter an URL or upload an image for the logo.<br/>
					<img class="wtt_logo_image" src="<?php echo $logo_url; ?>" />
				</td>
			</tr>					
			<!-- delete this -->
			<tr valign="top"  style="display: none">
				<th scope="row" >
					<label for="wtt_username">Username:</label>
				</th>
				<td>
					<input type="text" name="wtt_username" value="<?php echo $username; ?>" class="regular-text" />
				</td>
			</tr>
			<!-- /delete this -->	
			<!-- delete this -->
			<tr valign="top"  style="display: none">
				<th scope="row">
					<label for="wtt_password">Password:</label>
				</th>
				<td>
					<input type="text" name="wtt_password" value="<?php echo $password; ?>" class="regular-text" />
				</td>
			</tr>
			<!-- /delete this -->		
			<!-- delete this -->
			<tr valign="top"  style="display: none">
				<th scope="row">
					<label for="wtt_total_posts">Total Posts:</label>
				</th>
				<td>
					<input type="text" name="wtt_total_posts" value="<?php echo $total_posts; ?>" class="regular-text" />
				</td>
			</tr>
			<!-- /delete this -->		
			<!-- delete this -->
			<tr valign="top"  style="display: none">
				<th scope="row">
					<label for="wtt_total_posts_sent">Total Posts Sent:</label>
				</th>
				<td>
					<input type="text" name="wtt_total_posts_sent" value="<?php echo $total_posts_sent; ?>" class="regular-text" />
				</td>
			</tr>
			<!-- /delete this -->		
			<!-- delete this -->
			<tr valign="top"  style="display: none">
				<th scope="row">
					<label for="wtt_loop">Posts Loop:</label>
				</th>
				<td>
					<input type="text" name="wtt_loop" value="<?php echo $wtt_loop; ?>" class="regular-text" />
				</td>
			</tr>
			<!-- /delete this -->		
			<!-- delete this -->
			<tr valign="top"  style="display: none">
				<th scope="row">
					<label for="wtt_posts_sent_ok">Posts Success Sent?:</label>
				</th>
				<td>
					<input type="text" name="wtt_posts_sent_ok" value="<?php echo $wtt_posts_sent_ok; ?>" class="regular-text" />
				</td>
			</tr>
			<!-- /delete this -->	
			<!-- delete this -->
			<tr valign="top"  style="display: none">
				<th scope="row">
					<label for="wtt_plugin_errors">Plugin Errors:</label>
				</th>
				<td>
					<pre><?php echo $plugin_error; ?></pre>
				</td>
			</tr>
			<!-- /delete this -->
			<!-- delete this -->
			<tr valign="top"  style="display: none">
				<th scope="row">
					<label for="wtt_html_template">HTML Template:</label>
				</th>
				<td>
					<textarea style="width:600px; height:400px;"><?php echo $html_template; ?></textarea>
				</td>
			</tr>
			<!-- /delete this -->
		</table>			
<?php
	if(get_option('wtt_install')==='fail'){
?>
		<div id="setting-error-settings_updated" class="error settings-error"> 
				<p>There was an error when trying to install the plugin, click on Save Settings and Reinstall button to complete the installation.</p>
				<input type="hidden" name="wtt_reinstall" id="wtt_reinstall" value="yes" />
		</div>	
<?php		
	}
?>			
		
		<input type="hidden" name="update_settings" value="Y" />  
		<input type="hidden" name="action" value="update" />
		
<?php 
	if(get_option('wtt_install')==='fail'){
		echo '<input type="hidden" name="page_options" value="wtt_api_key, wtt_bg_color, wtt_text_color, wtt_sec_text_color, wtt_primary_color, wtt_secondary_color, wtt_logo_url" />';
		submit_button('Save Settings and Reinstall'); 
		
	} else {
		echo '<input type="hidden" name="page_options" value="wtt_api_key,wtt_bg_color, wtt_text_color, wtt_sec_text_color, wtt_primary_color, wtt_secondary_color, wtt_logo_url,wtt_reinstall" />';
		submit_button('Save Settings'); 
	}

?>
	</form>
</div>
	
<?php

}

?>