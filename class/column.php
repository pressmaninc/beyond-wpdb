<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Beyond_Wpdb_Column
 * A class for managing virtual columns
 */
class Beyond_Wpdb_Column {
	private $columns = array();

	/**
	 * Get the virtual column created
	 */
	public function set_columns() {
		global $wpdb;
		foreach( BEYOND_WPDB_PRIMARYS as $primary => $values ) {
			$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );
			$columns = $wpdb->get_results( "show columns from {$table_name} where Field not in ('post_id', 'user_id', 'comment_id', 'json')" );
			$this->columns[$primary] = array();

			foreach ( $columns as $val ) {
				array_push( $this->columns[$primary], $val->Field );
			}
		}
	}

	/**
	 * @return array
	 * get virtual columns
	 */
	public function get_columns()
	{
		return $this->columns;
	}
}
