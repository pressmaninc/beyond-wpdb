<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Beyond_Wpdb_Information
 * A class for managing virtual columns
 */
class Beyond_Wpdb_Information {
	private $exist_columns = '';
	private $exist_tables = '';
	private $exist_triggers = '';

	/**
	 * Get exist virtual columns
	 */
	public function set_columns() {
		global $wpdb;
		$this->exist_columns = array();

		foreach( BEYOND_WPDB_PRIMARYS as $primary => $values ) {
			$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );
			$result = $wpdb->get_results( "SHOW TABLES LIKE '{$table_name}'" );
			if ( count( $result ) > 0 ) {
				$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name} WHERE Field NOT IN ('post_id', 'user_id', 'comment_id', 'json')" );
				$this->exist_columns[$table_name] = array();

				foreach ( $columns as $val ) {
					array_push( $this->exist_columns[$table_name], $val->Field );
				}
			}
		}
	}

	/**
	 * Get exist tables
	 */
	public function set_tables() {
		global $wpdb;
		$this->exist_tables = array();

		foreach ( $wpdb->get_results( "SHOW TABLES LIKE '%meta_beyond%'", ARRAY_A ) as $value ) {
			array_push( $this->exist_tables, array_values( $value )[0] );
		}
	}

	/**
	 * Get exist triggers
	 */
	public function set_triggers() {
		global $wpdb;
		$this->exist_triggers = array();

		foreach ($wpdb->get_results("SHOW TRIGGERS") as $value) {
			array_push( $this->exist_triggers, $value->Trigger );
		}
	}

	/**
	 * @return array
	 * get virtual columns
	 */
	public function get_columns()
	{
		return $this->exist_columns;
	}

	/**
	 * @return array
	 */
	public function get_tables()
	{
		return $this->exist_tables;
	}

	/**
	 * @return array
	 */
	public function get_triggers()
	{
		return $this->exist_triggers;
	}
}
