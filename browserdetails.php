<?php
	
/*
Plugin Name: Browser Details for Gravity Forms
Plugin URI: http://travislop.es/
Description: Attach your users' browser details to form submissions and notifications
Version: 1.0
Author: Travis Lopes
Author URI: http://travislop.es
*/

define( 'GF_BROWSERDETAILS_VERSION', '1.0' );

add_action( 'gform_loaded', array( 'GF_BrowserDetails_Bootstrap', 'load' ), 5 );

class GF_BrowserDetails_Bootstrap {

	public static function load() {
		
		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}
		
		require_once( 'class-gf-browserdetails.php' );
		
		GFAddOn::register( 'GFBrowserDetails' );
		
	}

}

function gf_browserdetails() {
	return GFBrowserDetails::get_instance();
}