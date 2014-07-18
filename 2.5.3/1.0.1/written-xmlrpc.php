<?php

// Let's add new XMLRPC Methods
add_filter('xmlrpc_methods', 'wtt_xmlrpc_methods');

function wtt_xmlrpc_methods($methods){
	$methods['wtt_get_post'] = 'wtt_get_post';
	$methods['wtt_create_post'] = 'wtt_create_post';
	$methods['wtt_edit_post'] = 'wtt_edit_post';
	$methods['wtt_delete_post'] = 'wtt_delete_post';
	$methods['wtt_add_canonical_license'] = 'wtt_add_canonical_license';
	$methods['wtt_update_canonical_license'] = 'wtt_update_canonical_license';
	$methods['wtt_delete_canonical_license'] = 'wtt_delete_canonical_license';
	$methods['wtt_add_pixel_license'] = 'wtt_add_pixel_license';
	$methods['wtt_delete_pixel_license'] = 'wtt_delete_pixel_license';
	$methods['wtt_add_redirect_license'] = 'wtt_add_redirect_license';
	$methods['wtt_add_redirect_expiration'] = 'wtt_add_redirect_expiration';
	$methods['wtt_delete_redirect_license'] = 'wtt_delete_redirect_license';
	$methods['wtt_add_page_license'] = 'wtt_add_page_license';
	$methods['wtt_delete_page_license'] = 'wtt_delete_page_license';
	return $methods;
}

function wtt_get_post($args){
	
	$username	= $args[0];
	$password	= $args[1];
	$post_ID	= $args[2];

	global $wp_xmlrpc_server;

	// Let's run a check to see if credentials are okay
	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}

	$post = get_post($post_ID, ARRAY_A);

	if($post!==null){
		return $post;
	} else {
		return 'No post found';
	}

}

function wtt_create_post($args){
	
	$username	  = $args[0];
	$password	  = $args[1];
	$post_title	  = $args[2];
	$post_content = $args[3];
	$post_author  = $args[4];
	$license_type = $args[5];
	$author_url	  = $args[6];

	global $wp_xmlrpc_server;

	// Let's run a check to see if credentials are okay
	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}	

	$new_post = array(
	  'post_title'    => wp_strip_all_tags( $post_title ),
	  'post_content'  => $post_content,
	  'post_status'   => 'publish'
	);

	// Insert the post into the database
	$post_ID = wp_insert_post( $new_post );

	if ( is_wp_error( $post_ID ) ) {
		return $post_ID->get_error_message();
	} else {
		update_post_meta($post_ID, 'wtt_custom_author', $post_author);
		update_post_meta($post_ID, 'wtt_license_type', $license_type);
		update_post_meta($post_ID, 'wtt_author_url', $author_url);
		update_post_meta($post_ID, '_wtt_author_api', 'yes');

 		$api_key = get_option('wtt_api_key');
 		$get_post = get_post($post_ID);
 		// dev
 		$author_ID = $get_post->post_author;
		$written_user_ID = get_option('wtt_user_id');

		$post_json = array(
			'title' => $post_title,
			'url'	=> get_permalink($post_ID),
			'content' => $post_content,
			'blog'	  => $api_key,
			'author'  => 'writtenapi',
			'post_id' => $post_ID
		);		 	

		$post_json = json_encode($post_json);

		$response = wp_remote_post( WTT_API.'advertiser/add_post', array(
			'method' => 'POST',
			'headers' => array(
				'Accept'       => 'application/json',
    			'Content-Type'   => 'application/json',
    			'Content-Length' => strlen( $post_json )
			),
			'body' => $post_json
		    )
		);	

		update_option('wtt_plugin_error', $response['body']); 

		return $post_ID;
	}

}

function wtt_edit_post($args){

	$username	  = $args[0];
	$password	  = $args[1];
	$post_ID	  = $args[2];
	$post_title	  = $args[3];
	$post_content = $args[4];
	$post_author  = $args[5];
	$license_type = $args[6];

	global $wp_xmlrpc_server;

	// Let's run a check to see if credentials are okay
	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}

	$update_post = array(
	  'ID'			  => $post_ID,
	  'post_title'    => wp_strip_all_tags( $post_title ),
	  'post_content'  => $post_content
	);

	// Insert the post into the database
	$post_ID = wp_update_post( $update_post );

	if ( $post_ID===0 ){
		return 'false';
	} else {

		update_post_meta($post_ID, 'wtt_custom_author', $post_author);
		update_post_meta($post_ID, 'wtt_license_type', $license_type);

		return 'true';
	}	

}


function wtt_delete_post($args){

	$username	  = $args[0];
	$password	  = $args[1];
	$post_ID	  = $args[2];

	global $wp_xmlrpc_server;

	// Let's run a check to see if credentials are okay
	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}

	$delete_post = wp_delete_post($post_ID, true);

	if ( !$delete_post ) {
		return 'false';
	} else {
		return 'true';
	}

}

function wtt_add_canonical_license($args){

	$username	  = $args[0];
	$password	  = $args[1];
	$post_ID	  = $args[2];	
	$canonical_url= $args[3];

	global $wp_xmlrpc_server;

	// Let's run a check to see if credentials are okay
	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}
	
	$postExists = get_post($post_ID);
	
	if( !$postExists || !is_numeric($post_ID) ){
		return 'false';
	}

	$set_canonical = add_post_meta($post_ID, 'wtt_canonical', $canonical_url);

	if($set_canonical!==false && $set_canonical>1){
		return 'true';
	} else {
		return 'false';
	}

}

function wtt_update_canonical_license($args){

	$username	  = $args[0];
	$password	  = $args[1];
	$post_ID	  = $args[2];	
	$new_url	  = $args[3];
	$old_url	  = $args[4];
	
	global $wp_xmlrpc_server;

	// Let's run a check to see if credentials are okay
	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}

	$postExists = get_post($post_ID);
	
	if( !$postExists || !is_numeric($post_ID) ){
		return 'false';
	}
	
	$set_canonical = update_post_meta($post_ID, 'wtt_canonical', $new_url, $old_url);

	if($set_canonical===true){
		return 'true';
	} else {
		return 'false';
	}
		
}

function wtt_delete_canonical_license($args){
	
	$username	  = $args[0];
	$password	  = $args[1];
	$post_ID	  = $args[2];	
	$canonical_url= $args[3];

	global $wp_xmlrpc_server;

	// Let's run a check to see if credentials are okay
	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}
	
	$delete_canonical = delete_post_meta($post_ID, 'wtt_canonical', $canonical_url);

	$delete_post = wp_delete_post($post_ID, true);

	if($delete_post==='false' || $delete_post===false || $delete_post==='' || !$delete_post){
		return 'false';
	} else {
		return 'true';
	}	

}

function wtt_add_pixel_license($args){
	
	$username	  = $args[0];
	$password	  = $args[1];
	$post_ID	  = $args[2];	
	$script_url	  = $args[3];

	global $wp_xmlrpc_server;

	// Let's run a check to see if credentials are okay
	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}
	
	$postExists = get_post($post_ID);
	
	if( !$postExists || !is_numeric($post_ID) ){
		return 'false';
	}

	$redirect_license = get_post_meta($post_ID, 'wtt_redirect', true);
	$written_author = get_post_meta($post_ID, '_wtt_author_api', true);

	if($written_author){
		return 'Post locked. Not original content.';
	}

	if($redirect_license){
		return 'Post locked. Active 301 redirect license set.';
	}

	if(!$redirect_license){
		$set_pixel = add_post_meta($post_ID, 'wtt_pixel_code', $script_url);
		if($set_pixel!==false && $set_pixel>1){
			return 'true';
		} else {
			return 'false';
		}			
	}

}

function wtt_delete_pixel_license($args){
	
	$username	  = $args[0];
	$password	  = $args[1];
	$post_ID	  = $args[2];	
	$script_url	  = $args[3];

	global $wp_xmlrpc_server;

	// Let's run a check to see if credentials are okay
	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}
	
	$delete_pixel = delete_post_meta($post_ID, 'wtt_pixel_code', $script_url);

	if($delete_pixel===true){
		return 'true';
	} else {
		return 'false';
	}	

}


function wtt_add_redirect_license($args){
	
	$username	  = $args[0];
	$password	  = $args[1];
	$post_ID	  = $args[2];	
	$redirect_url = $args[3];

	global $wp_xmlrpc_server;

	// Let's run a check to see if credentials are okay
	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}

	$postExists = get_post($post_ID);
	
	if( !$postExists || !is_numeric($post_ID) ){
		return 'false';
	}		

	$redirect_license = get_post_meta($post_ID, 'wtt_redirect', true);
	$takeover_license = get_post_meta($post_ID, 'wtt_takeover_code', true);	
	$pixel_license = get_post_meta($post_ID, 'wtt_pixel_code', true);
	$written_author = get_post_meta($post_ID, '_wtt_author_api', true);

	if($written_author){
		return 'Post locked. Not original content.';
	}

	if($redirect_license){
		return 'Post locked. Active 301 redirect license set.';
	}

	if($takeover_license){
		return 'Post already has an active takeover license.';
	}	

	if($pixel_license){
		return 'Post already has an active pixel license.';
	}		

	if(!$takeover_license && !$pixel_license){
		update_post_meta($post_ID, 'wtt_redirect','');
		$set_redirect = update_post_meta($post_ID, 'wtt_redirect', $redirect_url);		

		if($set_redirect===true){
			return 'true';
		} else{
			return 'false';
		}
	} else {
		return 'false';
	}

}

function wtt_delete_redirect_license($args){
	
	$username	  = $args[0];
	$password	  = $args[1];
	$post_ID	  = $args[2];	
	$redirect_url = $args[3];

	global $wp_xmlrpc_server;

	// Let's run a check to see if credentials are okay
	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}
	
	$delete_redirect = delete_post_meta($post_ID, 'wtt_redirect');

	if($delete_redirect===true){
		return 'true';
	} else {
		return 'false';
	}	

}

function wtt_add_redirect_expiration($args){
	$username	  = $args[0];
	$password	  = $args[1];
	$post_ID	  = $args[2];	
	$redirect_url = $args[3];

	global $wp_xmlrpc_server;

	// Let's run a check to see if credentials are okay
	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}	

	update_post_meta($post_ID, 'wtt_redirect','');
	$set_redirect = update_post_meta($post_ID, 'wtt_redirect', $redirect_url);		

	// 30 days = 2592000 seconds

	if($set_redirect===true){
		wp_schedule_single_event( time() + 120, 'wtt_remove_redirect', array($post_ID) );
		return 'true';
	} else{
		return 'false';
	}	

}

function wtt_redirect_expire_license($post_ID) {
    $delete_redirect = delete_post_meta($post_ID, 'wtt_redirect');
    $delete_post = wp_delete_post($post_ID, true);
}

add_action( 'wtt_remove_redirect', 'wtt_redirect_expire_license');


function wtt_add_page_license($args){
	
	$username	  = $args[0];
	$password	  = $args[1];
	$post_ID	  = $args[2];	
	$code	 	  = $args[3];

	global $wp_xmlrpc_server;

	// Let's run a check to see if credentials are okay
	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}
	
	$postExists = get_post($post_ID);
	
	if( !$postExists || !is_numeric($post_ID) ){
		return 'false';
	}

	$redirect_license = get_post_meta($post_ID, 'wtt_redirect', true);
	$takeover_license = get_post_meta($post_ID, 'wtt_takeover_code', true);	
	$written_author = get_post_meta($post_ID, '_wtt_author_api', true);

	if($written_author){
		return 'Post locked. Not original content.';
	}

	if($redirect_license){
		return 'Post locked. Active 301 redirect license set.';
	}

	if($takeover_license){
		return 'Post already has an active takeover license.';
	}		

	if(!$redirect_license){

		update_post_meta($post_ID, 'wtt_takeover_code','');
		$set_takeover = update_post_meta($post_ID, 'wtt_takeover_code', $code);

		if($set_takeover===true){
			return 'true';
		} else {
			return 'false';
		}
		
	} 

}

function wtt_delete_page_license($args){
	
	$username	  = $args[0];
	$password	  = $args[1];
	$post_ID	  = $args[2];	

	global $wp_xmlrpc_server;

	// Let's run a check to see if credentials are okay
	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}
	
	$delete_page = delete_post_meta($post_ID, 'wtt_takeover_code');

	if($delete_page===true){
		return 'true';
	} else {
		return 'false';
	}	

}