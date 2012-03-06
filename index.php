
<?php
/*
Plugin Name: Placeling
Plugin URI: https://www.placeling.com
Description: Tag your posts with location data from Placeling!
Version: 0.1
Author: Placeling (Internet Services) Inc.
Author URI: https://www.placeling.com
*/

include('OAuthSimple.php');


if (!class_exists("Placeling")) {
	class Placeling {
		
		var $path = '';
		function Placeling() { 
			$this->path = WP_PLUGIN_URL . '/Pinta/';
			
			// Add Options Page
			add_action( 'admin_menu',  array(&$this, 'admin_menu') );
			add_action( 'save_post', array( &$this, 'save_post') );
			add_filter( 'media_buttons_context', array(&$this, 'placeling_media_button') );
			add_filter( 'the_content', array(&$this, 'addPlacelingFooter') );
			
		}
		
			// Hook the options mage
		function admin_menu() {
			add_meta_box( 'WPPlaceling', 'Placeling', array(&$this,'draw_placeling'), 'post', 'normal', 'high' );
		} 
		
		
		function addPlacelingFooter( $content ){
		
			if ( !is_single() ){
				//we only want to show on single views, for now,  so as not to crowd
				return $content;
			}
			
		  	$post_ID = $GLOBALS['post']->ID;
			
		  	$meta_value = get_post_meta($post_ID, '_placeling_place_json', true);			
			
			if ( strlen( $meta_value ) > 0 ){
				$place_json = urldecode( $meta_value );
				$place = json_decode( $place_json );
				
				wp_enqueue_style( 'footer', $this->path.'/css/footer.css' );
				
				include("footer.php");
		  		$content = $content .footerHtml( $place, $this->path.'/img/addPlace.png' );
		  	}
		  	
		  	return $content;
		}
		
		function draw_placeling(){
			global $post_ID;
			
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-validate',  $this->path.'/js/jquery.validate.min.js', array('jquery') );
			wp_enqueue_script( 'postnew', $this->path.'/js/postnew.js', array('jquery', 'underscore') );
			wp_enqueue_script( 'underscore', $this->path.'/js/underscore-min.js', array('jquery') );
			wp_enqueue_style( 'pinta', $this->path.'/css/pinta.css');
			
			$path = $this->path;
			$empty_marker_button = $path . 'img/EmptyMarker.png';
			
			$meta_value = get_post_meta($post_ID, '_placeling_place_json', true);
			
			?>
				<input id="placeling_place_json" name="placeling_place_json" type="hidden" value="<?php echo $meta_value ; ?>" />				
				
				<div id="placeling_dialog_form" title="Post to Placeling">
			
				</div>
				
				<div id="empty_place">
					<a id='add_place' href='<?php echo $path; ?>/popup/index.php?TB_iframe=true&height=500&width=660' class='thickbox' alt='foo' title='Tag Place'><img src='<?php echo $empty_marker_button; ?>' />Add place</a>
				</div>
				
				<div id="placeling_tagged_place" style="display:none;">

				</div>
	
			<?php
		}
		
		function postToPlaceling( $post_ID ){
			
			$placemark_memo = $_POST['placeling_placemark_memo'];
			
			$permalink = get_permalink( $post_ID );
			$current_user = wp_get_current_user();
			
			$accessToken = get_user_meta($current_user->ID, 'placeling_access_token', true);
			$secretToken = get_user_meta($current_user->ID, 'placeling_access_secret', true);
			
			$hostname = "http://localhost:3000";
	
			$oauthObject = new OAuthSimple();
			$oauthObject->setAction("POST");
			
			$signatures = array( 'consumer_key'     => 'IR3hVvWRYBp1ah3PJUiPirgFzKlMHTeujbORNzAK',
				'shared_secret'    => 'PqsYkO2smE7gkz9txhzN0bHoPMtDLfp73kIc3RSY');
			
			$placemarker_json = urldecode( $_POST['placeling_place_json'] );
			$placemarker_json = preg_replace('/\\\\\'/', '\'', $placemarker_json);
			$placemarker = json_decode( $placemarker_json );
			
			$placemark_memo = preg_replace('/\\\\\'/', '\'', $placemark_memo);
			
			if ( empty($accessToken) || empty($secretToken) || $accessToken == "" || $secretToken == "" ) {
				//this is a weird state that probably shouldn't happen, but I don't want it to break their post
			} else {
				$signatures['oauth_token'] = $accessToken;
				$signatures['oauth_secret'] = $secretToken;
				
				$url = $hostname.'/v1/places/'.$placemarker->id.'/perspectives';
				
				$result = $oauthObject->sign(array(
					'path'      => $url,
					'parameters'=> array(
						'memo' => $placemark_memo,
						'url'  => $permalink ),
					'signatures'=> $signatures));
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $result['parameters']);
				$r = curl_exec($ch);
				// has to be "fire and forget"
				var_dump( $r );
			}
			
			die();
		}
		
		function save_post( $post_ID ){
			if ( array_key_exists( 'placeling_place_json', $_POST ) ){
				$place_json = $_POST['placeling_place_json'];
				if ( strlen( $place_json ) > 0 ){
					update_post_meta( $post_ID, '_placeling_place_json', $place_json );
				} else {
					delete_post_meta( $post_ID, '_placeling_place_json' );
				}
			}
			
			if ( array_key_exists( 'placeling_placemark_memo', $_POST ) ){
				$this->postToPlaceling( $post_ID );
			}	
		}
		
		
		function placeling_media_button($context) {
			global $post_ID;
			$path = $this->path;
			
		  	$meta_value = get_post_meta($post_ID, '_placeling_place_json', true);
		  	
		  	$empty_button_image = $this->path . 'img/EmptyMarker.png';
		  	$placed_button_image = $this->path . 'img/MyMarker.png';
		  	if ( strlen($meta_value) > 0 ){
		  		$place_json = urldecode( $meta_value );
				$place = json_decode( $place_json );
		  		$name = $place->name;
		  	} else {
	        	$name = "";
	        }
	        $placesApi_media_button = ' %s' . "<a id='add_place' href='{$path}/popup/index.php?TB_iframe=true&height=500&width=660' class='thickbox' alt='foo' title='Add Place'><img id='placeling_untagged' style='display:none;' height=16 width=16 src='" . $empty_button_image . "' /><img id='placeling_tagged' height=16 width=16 style='display:none;' src='" . $placed_button_image . "' /><span class='placeling_place_name'>".$name."</span></a>";
	        return sprintf($context, $placesApi_media_button);
	    }

	}
}

if ( class_exists('Placeling') ) :
	
	$Placeling = new Placeling();
	if (isset($Placeling)) {
		register_activation_hook( __FILE__, array(&$Placeling, 'install') );
	}
endif;

?>







