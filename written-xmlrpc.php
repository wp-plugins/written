<?php

/**
* This modifies the existing XMLRPC methods and adds the custom Written.com XMLRPC methods.
*/
add_filter('xmlrpc_methods', 'wtt_xmlrpc_methods');

function wtt_xmlrpc_methods($methods){
	$methods['wtt_hello'] = 'wtt_hello';
	$methods['wtt_get_all_posts'] = 'wtt_get_all_posts';
	$methods['wtt_count_posts'] = 'wtt_count_posts';
	$methods['wtt_get_post'] = 'wtt_get_post';
	$methods['wtt_get_post_from_slug'] = 'wtt_get_post_from_slug';
	$methods['wtt_create_post'] = 'wtt_create_post';
	$methods['wtt_edit_post'] = 'wtt_edit_post';
	$methods['wtt_delete_post'] = 'wtt_delete_post';
	$methods['wtt_add_redirect_license'] = 'wtt_add_redirect_license';
	$methods['wtt_delete_redirect_license'] = 'wtt_delete_redirect_license';
	$methods['wtt_get_plugins'] = 'wtt_get_plugins';
	$methods['wtt_update_auth'] = 'wtt_update_auth';
	$methods['wtt_get_piwik_id'] = 'wtt_get_piwik_id';
	$methods['wtt_update_custom_fields'] = 'wtt_update_custom_fields';
		

	//not currently in use
	$methods['wtt_add_canonical_license'] = 'wtt_add_canonical_license';
	$methods['wtt_update_canonical_license'] = 'wtt_update_canonical_license';
	$methods['wtt_delete_canonical_license'] = 'wtt_delete_canonical_license';
	$methods['wtt_add_pixel_license'] = 'wtt_add_pixel_license';
	$methods['wtt_delete_pixel_license'] = 'wtt_delete_pixel_license';
		
	
	return $methods;
}


/**
* This method allows us to check if the blogger has our plugin installed.
*/
function wtt_hello($args) {
	return 'Hello World';
}


function wtt_update_custom_fields($args) {
	$username		= $args[0];
	$password		= $args[1];
	$author_url		= $args[2];
	$author_name	= $args[3];
	$post_id		= $args[4];

	global $wp_xmlrpc_server;

	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}

	$update = update_post_meta($post_id,'wtt_author_url',$author_url);
	$update = update_post_meta($post_id,'wtt_custom_author',$author_name);

	return $update;
}


/**
* This method returns all posts and pages.
* Arguments: username,password,posts per page,offset
*/
function wtt_get_all_posts($args) {

	$username		= $args[0];
	$password		= $args[1];
	$posts_per_page = $args[2];
	$offset			= $args[3];

	global $wp_xmlrpc_server;

	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}

	$args = array(
		'post_type' => array('post','page'),
		'posts_per_page' => $posts_per_page,
		'offset'	=> $offset,
		'post_status' => 'publish'
	);
	$posts = get_posts($args);

	return $posts;
}

/**
* This method returns the total number of posts and pages.
* Arguments: username,password
*/
function wtt_count_posts($args) {
	$username		= $args[0];
	$password		= $args[1];

	global $wp_xmlrpc_server;

	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}

	$published_posts = wp_count_posts()->publish;
	$published_pages = wp_count_posts('page')->publish;

	return $published_pages + $published_posts;
}


/**
* This method returns a post object.
* Arguments: username,password,post id
*/
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

/**
* This method returns a post object.
* Arguments: username,password,post slug
*/
function wtt_get_post_from_slug($args){
	
	$username	= $args[0];
	$password	= $args[1];
	$post_slug	= $args[2];

	global $wp_xmlrpc_server;
	global $wpdb;

	// Let's run a check to see if credentials are okay
	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}

	$post_ID = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_name = '".$post_slug."'");

	$post = get_post($post_ID, ARRAY_A);

	if($post!==null){
		return $post;
	} else {
		return 'No post found';
	}

}

/**
* This method creates a post.
* Arguments: username,password,post title,post content
*/
function wtt_create_post($args){
	
	$username	  = $args[0];
	$password	  = $args[1];
	$post_title	  = $args[2];
	$post_content = urldecode($args[3]);

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
		
		return $post_ID;
	}

}

/**
* This method deletes a post.
* Arguments: username,password,post id
*/
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


/**
* This method adds a content and traffic license.
* Arguments: username,password,post id,redirect url
*/
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

	update_post_meta($post_ID, 'wtt_redirect','');
	$set_redirect = update_post_meta($post_ID, 'wtt_redirect', $redirect_url);
	
	if($set_redirect===true){
		return 'true';
	} else{
		return 'false';
	}
}


/**
* This method removes a content and traffic license.
* Arguments: username,password,post id
*/
function wtt_delete_redirect_license($args){
	
	$username	  = $args[0];
	$password	  = $args[1];
	$post_ID	  = $args[2];	

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

/**
* This method returns a list of active plugins
* Arguments: username,password
*/
function wtt_get_plugins($args){
	
	$username	  = $args[0];
	$password	  = $args[1];

	global $wp_xmlrpc_server;

	// Let's run a check to see if credentials are okay
	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}
	
	$plugins = wp_get_active_and_valid_plugins();
	if($plugins){
		return $plugins;
	} else {
		return 'false';
	}	
}


/**
* This method updates XMLRPC auth on Written api
*/
function wtt_update_auth($args){

	$send_auth = wtt_send_auth();

	return $send_auth;
}


/**
* This method checks the piwik id
*/
function wtt_get_piwik_id($args){

	return get_option('wtt_tracking_id');
}

/**
* This method creates a canonical license.
* Not currently in use.
* Arguments: TBD
*/
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


/**
* This method updates a canonical license.
* Not currently in use.
* Arguments: TBD
*/
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


/**
* This method deletes a canonical license.
* Not currently in use.
* Arguments: TBD
*/
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


/**
* This method creates a pixel license.
* Not currently in use.
* Arguments: TBD
*/
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


/**
* This method deletes a pixel license.
* Not currently in use.
* Arguments: TBD
*/
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


/**
* This method edits a post.
* Not currently in use.
* Arguments: username,password,post title,post content
*/
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
