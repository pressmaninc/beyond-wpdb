<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress Plugin register hook class.
 */
class Beyond_Wpdb_Register {
	/**
	 * activation
	 *
	 * @param string $primary
	 * @return void
	 * @throws Exception
	 */
	public static function activation( $primary ) : void {
		global $wpdb, $beyond_wpdb_sql;

		$beyond_wpdb_sql->create_table( $primary );
		if( $wpdb->last_error ) {
			self::activation_error( $wpdb->last_error );
		}
		$beyond_wpdb_sql->create_trigger( $primary );
		if( $wpdb->last_error ) {
			self::activation_error( $wpdb->last_error );
		}
		$beyond_wpdb_sql->data_init( $primary );
		if( $wpdb->last_error ) {
			self::activation_error( $wpdb->last_error );
		}
	}

	/**
	 * deactivation
	 *
	 * @param $primary
	 * @return void
	 * @throws Exception
	 */
	public static function deactivation( $primary ) : void {
		global $beyond_wpdb_sql;
		$beyond_wpdb_sql->drop_table( $primary );
		$beyond_wpdb_sql->drop_triggers( $primary );
	}


	/**
	 * Plugin activation error.
	 * Stop the process by using wp_die() so that the plugin is not enabled.
	 *
	 * @param string $error
	 * @return void
	 * @throws Exception
	 */
	public static function activation_error( string $error ) : void {
		// Duplicate error avoidance on re-activation.
		global $beyond_wpdb_sql;

		foreach ( array_keys( BEYOND_WPDB_PRIMARYS ) as $primary ) {
			$beyond_wpdb_sql->drop_triggers( $primary );
		}

		wp_die( $error );
		exit;
	}
}
