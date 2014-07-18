<?php

/**
* This modifies the existing XMLRPC methods and adds the custom Written.com XMLRPC methods.
*/


add_filter('xmlrpc_methods', 'wtt_xmlrpc_methods');

function wtt_xmlrpc_methods($methods){
	$methods['wtt_hello'] = 'wtt_hello';
	$methods['wtt_get_plugin_info'] = 'wtt_get_plugin_info';
	$methods['wtt_count_posts'] = 'wtt_count_posts';
	$methods['wtt_update_auth'] = 'wtt_update_auth';
	$methods['wtt_clear_post_cache'] = 'wtt_clear_post_cache';
	$methods['wtt_get_post_id_from_url'] = 'wtt_get_post_id_from_url';
	$methods['wtt_get_post_content'] = 'wtt_get_post_content';
	
	return $methods;
}


/**
* This method allows us to check if the blogger has our plugin installed.
* I'd like to deprecate this method on a future release.
*/
function wtt_hello($args) {
	return 'Hello World';
}


/**
* This method returns info on the Written.com WordPress plugin
*/
function wtt_get_plugin_info($args) {
	
	global $written_licensing_plugin;

	$version = get_bloginfo('version');
	
	$plugin_info = $written_licensing_plugin->plugin_info();

	$plugin_info['written_user_id'] = get_option("wtt_user_id");
	$plugin_info['wp_version'] = $version;
	$plugin_info['piwik_id'] = get_option('wtt_tracking_id');

	return $plugin_info;
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
* This method updates XMLRPC auth on Written api
*/
function wtt_update_auth($args){
	
	global $written_licensing_plugin;
	$send_auth = $written_licensing_plugin->send_auth();

	return $send_auth;
}


/**
* This method clears the cache on a specific post.
* Arguments: username,password,post_ID
*/
function wtt_clear_post_cache($args) {

	$username		= $args[0];
	$password		= $args[1];
	$post_ID		= $args[2];

	global $wp_xmlrpc_server;

	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}

	if(function_exists('wp_cache_post_change')) {
		$GLOBALS["super_cache_enabled"]=1;		
		wp_cache_post_change($post_ID);		
	}
	
	if(function_exists('hyper_cache_invalidate_post')) {
		hyper_cache_invalidate_post($post_ID);
	}

	if (function_exists('w3tc_pgcache_flush_post')){

		w3tc_pgcache_flush_post($post_ID);

	}

	if (function_exists('w3tc_flush_pgcache_purge_page')){

		w3tc_flush_pgcache_purge_page($post_ID);

	}


	return 'cache_cleared';

}

/**
* This method returns a post ID based off the inputted URL
* Arguments: username,password,url
*/
function wtt_get_post_id_from_url($args) {
	$username		= $args[0];
	$password		= $args[1];
	$url			= $args[2];

	global $wp_xmlrpc_server;

	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}


	$post_ID = url_to_postid($url);

	return $post_ID;
}

/**
* This method returns the post content
* Arguments: username,password,post_ID
*/
function wtt_get_post_content($args) {

	$username		= $args[0];
	$password		= $args[1];
	$post_ID		= $args[2];

	global $wp_xmlrpc_server;

	if ( !$user = $wp_xmlrpc_server->login($username, $password) ) {
		return $wp_xmlrpc_server->error;
	}

	$post_object = get_post( $post_ID );  

	if($post_object) {
		$content = do_shortcode( $post_object->post_content );
		return wpautop($content);	
	} else {
		return false;
	}
	
}