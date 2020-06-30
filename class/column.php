<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Column {
	private $columns = array();

	public function set_columns() {
		global $wpdb;
		foreach( BEYOND_WPDB_PRIMARYS as $primary => $values ) {
			$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );
			$columns = $wpdb->get_results( "show columns from {$table_name} like '%virtual_%'" );
			$this->columns[$table_name] = array();

			foreach ( $columns as $val ) {
				array_push( $this->columns[$table_name], $val->Field );
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
