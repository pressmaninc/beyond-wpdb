<?php
/*
Plugin Name: Beyond Wpdb
Plugin URI:
Description: Create a new table and register the meta data in a single row of JSON columns.Registration is done by mysql's trigger function.The data in WordPress is not affected.
Version: 0.5.0
Author: PRESSMAN
Author URI: https://www.pressman.ne.jp/
License: GPLv2 or later
*/


function beyond_wpdb_get_define_table_name( $primary ) {
	return 'BEYOND_WPDB_' . strtoupper( $primary ) . 'META_TABLE';
}

global $wpdb;

// Beyond_table setting.
$beyond_wpdb_primarys = [
	'post' => [
		'primary_table_name' => $wpdb->posts,
		'primary_table_key' => 'ID',
		'meta_table_name' => $wpdb->postmeta,
		'meta_table_key' => 'post_id',
	],
	'user' => [
		'primary_table_name' => $wpdb->users,
		'primary_table_key' => 'ID',
		'meta_table_name' => $wpdb->usermeta,
		'meta_table_key' => 'user_id',
	],
	'comment' => [
		'primary_table_name' => $wpdb->comments,
		'primary_table_key' => 'comment_ID',
		'meta_table_name' => $wpdb->commentmeta,
		'meta_table_key' => 'comment_id',
	]
];
define( 'BEYOND_WPDB_PRIMARYS', $beyond_wpdb_primarys );

// Deifne Beyond_table name.
foreach( array_keys( BEYOND_WPDB_PRIMARYS ) as $primary ) {
	define( beyond_wpdb_get_define_table_name( $primary ), $wpdb->prefix . $primary . 'meta_json' );
}

// Require files.
require_once( plugin_dir_path( __FILE__ ) . 'class/register-hook.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class/sql.php' );

// Plugin activation hook.
register_activation_hook( __FILE__, ['Beyond_Wpdb_Register_Hook', 'activation'] );
register_deactivation_hook( __FILE__, ['Beyond_Wpdb_Register_Hook', 'deactivation'] );
register_uninstall_hook( __FILE__, ['Beyond_Wpdb_Register_Hook', 'uninstall'] );
