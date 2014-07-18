<?php		
header('Content-type: text/css');
require '../../../../wp-load.php'; // load wordpress bootstrap, this is what I don't like	
echo 'body.takeover > * { display:none; }';
echo 'body.takeover > .wtt_overlay_loader, body.takeover > #written_ajax, body.takeover > .wtt_loader{ display:block; }';
echo '.wtt_loader{ background:#fff url('.plugins_url('../img/loader.gif', __FILE__).') no-repeat center center; -webkit-border-radius:10px; -moz-border-radius:10px; border-radius:10px; width:50px; height:50px; padding:15px; position:fixed; top:50%; left:50%; margin-left:-25px; margin-top:-25px; display:block; z-index:99999; box-shadow:0 0 8px rgba(0,0,0,0.1); z-index:99999; }';
echo '.wtt_takeover{ background-color:'.get_option('wtt_bg_color').'; color:'.get_option('wtt_text_color').';  }'."\n";
echo '.wtt_takeover p{ color:'.get_option('wtt_text_color', '#919191').'; }'."\n";
echo '.wtt_takeover header{ background:'.get_option('wtt_primary_color', '#a5c7ad').'; }'."\n";
echo '.wtt_takeover a{ color:'.get_option('wtt_primary_color').'; }'."\n";
echo '.wtt_takeover h2, .wtt_takeover .title{ color:'.get_option('wtt_secondary_color', '#000000').'; }'."\n";
echo '.wtt_takeover header p{ color:'.get_option('wtt_sec_text_color', '#919191').'; }'."\n";
if(get_option('wtt_logo_url')!==''){
	echo '.wtt_takeover .logo{ background:url('.get_option('wtt_logo_url').') no-repeat 0 0; text-indent:-9999em; background-size:auto 50px; height:50px; }'."\n";
}
