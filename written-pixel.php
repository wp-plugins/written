<?php
// WP Footer JS Injection for Pixel License
function wtt_pixel_license_code(){
	global $post;
	if(is_singular()){
		$pixel_code = get_post_meta( $post->ID, 'wtt_pixel_code' );

		if( !empty($pixel_code) ) {
			foreach($pixel_code as $pixel_code_url):
				echo $pixel_code_url."\n";
				//echo "<script type='text/javascript' src='$pixel_code_url'></script>\n";
			endforeach;
		}
	}	
}

?>