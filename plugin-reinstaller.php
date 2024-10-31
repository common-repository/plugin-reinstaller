<?php
/*
Plugin Name: Plugin Reinstaller
Plugin URI: http://wpgogo.com/development/plugin-reinstaller.html
Description: This plugin enables the bulk plugin reinstall.
Author: Hiroaki Miyashita
Version: 1.1
Author URI: http://wpgogo.com/
*/

/*  Copyright 2013 Hiroaki Miyashita

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class plugin_reinstaller {

	function plugin_reinstaller() {
		add_filter( 'site_transient_update_plugins', array(&$this, 'site_transient_update_plugins') );
	}
	
	function site_transient_update_plugins($current) {
		if ( isset($_REQUEST['action']) && $_REQUEST['action'] == 'update-selected' ) :
			$current = $this->wp_update_plugins();
		endif;

		return $current;
	}
	
	function wp_update_plugins() {
		include ABSPATH . WPINC . '/version.php'; // include an unmodified $wp_version

		if ( defined('WP_INSTALLING') )
			return false;

		// If running blog-side, bail unless we've not checked in the last 12 hours
		if ( !function_exists( 'get_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$plugins = get_plugins();
		$active  = get_option( 'active_plugins', array() );
		$current = new stdClass;

		$new_option = new stdClass;
		$new_option->last_checked = time();

		foreach ( $plugins as $file => $p ) {
			$plugins[$file]['Version'] = '0.0.1';
		}

		$to_send = (object) compact('plugins', 'active');
		
		$options = array(
			'timeout' => ( ( defined('DOING_CRON') && DOING_CRON ) ? 30 : 3),
			'body' => array( 'plugins' => serialize( $to_send ) ),
			'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
		);

		$raw_response = wp_remote_post('http://api.wordpress.org/plugins/update-check/1.0/', $options);
		
		if ( is_wp_error( $raw_response ) || 200 != wp_remote_retrieve_response_code( $raw_response ) )
			return false;

		$response = maybe_unserialize( wp_remote_retrieve_body( $raw_response ) );

		if ( is_array( $response ) )
			$new_option->response = $response;
		else
			$new_option->response = array();

		return $new_option;
	}
}
global $plugin_reinstaller;
$plugin_reinstaller = new plugin_reinstaller();
?>