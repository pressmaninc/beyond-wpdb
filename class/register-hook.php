<?php
/**
 * WordPress Plugin register hook class.
 */
class Beyond_Wpdb_Register_Hook {
	/**
	 * Plugin activation hook.
	 *
	 * @return void
	 */
	public static function activation() : void {
		global $wpdb, $beyond_wpdb_sql;

		$beyond_wpdb_sql->create_table();
		if( $wpdb->last_error ) {
			self::activation_error( $wpdb->last_error );
		}
		$beyond_wpdb_sql->create_trigger();
		if( $wpdb->last_error ) {
			self::activation_error( $wpdb->last_error );
		}
		$beyond_wpdb_sql->data_init();
		if( $wpdb->last_error ) {
			self::activation_error( $wpdb->last_error );
		}
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @return void
	 */
	public static function deactivation() : void {
		global $beyond_wpdb_sql;
		$beyond_wpdb_sql->drop_triggers();
	}

	/**
	 * Plugin uninstall hook.
	 *
	 * @return void
	 */
	public static function uninstall() : void {
		global $beyond_wpdb_sql;
		$beyond_wpdb_sql->drop_table();
	}

	/**
	 * Plugin activation error.
	 * Stop the process by using wp_die() so that the plugin is not enabled.
	 *
	 * @param string $error
	 * @return void
	 */
	public static function activation_error( string $error ) : void {
		// Duplicate error avoidance on re-activation.
		global $beyond_wpdb_sql;
		$beyond_wpdb_sql->drop_triggers();

		wp_die( $error );
		exit;
	}
}