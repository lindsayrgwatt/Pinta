<?php

include_once('pinta-config.php');

function footerHtml( $place, $add_url ){
	global $SERVICE_HOSTNAME;
	
	if ( isset( $place ) ){
		$lat = $place->lat;
		$lng = $place->lng;
		$pid = $place->id;
		
		$url = $place->map_url;
		$thirdparty_url = $place->google_url;
		$place_url = $SERVICE_HOSTNAME."/places/$pid?src=plugin";
		$name = $place->name;	
	} else {
		$lat = 0;
		$lng = 0;
		$pid = "";		
		$url = "";
		$thirdparty_url = "#";
		$place_url ="#";
		$name = "";		
	}			
				
	return "
	<div id='placeling_footer'>
		<div id='placeling_left_footer'>
			<div id='placeling_add_map'>
				<a id='placeling_add_action' href='#'><img id='placeling_add_image' src='$add_url'/><div id='placeling_add_text'>add to my places</div></a>
			</div>
		</div>
		<div id='placeling_middle_footer'>
			<div id='placeling_place_title'>
				<a href='$place_url' target='_blank'><span id='placeling_place_name'>$name</span></a>
			</div>
			<div id='placeling_contact_info'>
				<a href='$thirdparty_url'  target='_blank'>hours, directions, and contact</a>
			</div>
		</div>
		<div id='placeling_right_footer'>
			<a href='$thirdparty_url'  target='_blank'><img id='placeling_map_image' src='$url'></a>
		</div>
	</div>
	";
}

?>