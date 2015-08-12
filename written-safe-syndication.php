<?php
/**
* This is the syndication license functionality.
* http://written.com/content-licensing/
*/
function wtt_rel_canonical(){

	if ( !is_singular() )
		return;
	
	global $wp_the_query;
	if ( !$id = $wp_the_query->get_queried_object_id() )
		return;


	$canonical = get_post_meta( $id, '_wtt_license_type' , true );
	if( $canonical == '2' ) {
		add_filter( 'wpseo_canonical', '__return_false' );
		echo "<link rel='canonical' href='".get_post_meta($id, '_wtt_canonical', true )."' />\n";
		
		return;
	}

	$link = get_permalink( $id );
	if ( $page = get_query_var('cpage') )
		$link = get_comments_pagenum_link( $page );
	echo "<link rel='canonical' href='$link' />\n";

}
//remove_action( 'wp_head', 'rel_canonical' );
//add_action( 'wp_head', 'wtt_rel_canonical' ,1);