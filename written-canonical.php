<?php

// Remove WP Canonical function
remove_action( 'wp_head', 'rel_canonical' );
add_action( 'wp_head', 'wtt_rel_canonical' );
function wtt_rel_canonical(){
// original code
  if ( !is_singular() )
    return;
  global $wp_the_query;
  if ( !$id = $wp_the_query->get_queried_object_id() )
    return;
 
  // new code - if there is a meta property defined
  // use that as the canonical url
  $canonical = get_post_meta( $id, 'wtt_canonical' );
  if( !empty($canonical) ) {
  	foreach($canonical as $canonical_url):
    	echo "<link rel='canonical' href='$canonical_url' />\n";
    endforeach;
    return;
  }
 
  // original code
  $link = get_permalink( $id );
  if ( $page = get_query_var('cpage') )
    $link = get_comments_pagenum_link( $page );
  echo "<link rel='canonical' href='$link' />\n";
	
}

?>