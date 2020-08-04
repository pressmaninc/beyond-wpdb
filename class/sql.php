<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Beyond Wpdb table and trigger SQL.
 */
class Beyond_Wpdb_Sql {

	function __construct() {
		global $wpdb;

		foreach( array_keys( BEYOND_WPDB_PRIMARYS ) as $primary ) {
			$this->triggers[$primary] = array();

			$insert_trigger = 'insert_' . $primary . '_trigger';
			$this->$insert_trigger = esc_sql( $wpdb->prefix . 'insert_' . $primary . '_trigger' );
			array_push( $this->triggers[$primary], $this->$insert_trigger );

			$delete_trigger = 'delete_' . $primary . '_trigger';
			$this->$delete_trigger = esc_sql( $wpdb->prefix . 'delete_' . $primary . '_trigger' );
			array_push( $this->triggers[$primary], $this->$delete_trigger );

			$insert_meta_trigger = 'insert_' . $primary . 'meta_trigger';
			$this->$insert_meta_trigger = esc_sql( $wpdb->prefix . 'insert_' . $primary . 'meta_trigger' );
			array_push( $this->triggers[$primary], $this->$insert_meta_trigger );

			$update_meta_trigger = 'update_' . $primary . 'meta_trigger';
			$this->$update_meta_trigger = esc_sql( $wpdb->prefix . 'update_' . $primary . 'meta_trigger' );
			array_push( $this->triggers[$primary], $this->$update_meta_trigger );

			$delete_meta_trigger = 'delete_' . $primary . 'meta_trigger';
			$this->$delete_meta_trigger = esc_sql( $wpdb->prefix . 'delete_' . $primary . 'meta_trigger' );
			array_push( $this->triggers[$primary], $this->$delete_meta_trigger );
		}
	}

	/**
	 * Create table
	 *
	 * @param string $type
	 * @return void
	 */
	function create_table( $primary ) : void {
		$values = BEYOND_WPDB_PRIMARYS[$primary];
		$this->create_table_sql( $primary, $values['meta_table_key'] );
	}

	/**
	 * Create table sql
	 *
	 * @param string $primary
	 * @param string $meta_table_key
	 * @return void
	 */
	protected function create_table_sql( $primary, $meta_table_key ) {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );
		$meta_table_key = esc_sql( $meta_table_key );

		$sql = 'CREATE TABLE ' . $table_name . ' (
		' . $meta_table_key . ' INT NOT NULL,
		json JSON,
		PRIMARY KEY  (' . $meta_table_key . ')
		)  ' . $charset_collate;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Drop table
	 *
	 * @param $primary
	 * @return void
	 * @throws Exception
	 */
	function drop_table( $primary ) {
		$this->drop_table_sql( $primary );
	}

	/**
	 * Drop table sql
	 *
	 * @param string $primary
	 * @return void
	 * @throws Exception
	 */
	protected function drop_table_sql( $primary ) {
		global $wpdb;

		$exist_tables = $this->get_exist_json_tables();
		$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );

		if ( in_array( $table_name, $exist_tables ) ) {
			$sql = 'DROP TABLE ' . $table_name;
			$wpdb->query( $sql );

			if ( $wpdb->last_error ) {
				throw new Exception( $wpdb->last_error );
			}
		}
	}

	/**
	 * Create trigger
	 * Insert/Delete posts trigger
	 * Insert/Update/Delete postmeta trigger
	 *
	 * @param string $primary
	 * @return void
	 * @throws Exception
	 */
	function create_trigger( $primary ) {
		$values = BEYOND_WPDB_PRIMARYS[$primary];
		$this->insert_primary_trigger( $primary, $values['primary_table_name'], $values['primary_table_key'], $values['meta_table_key'] );
		$this->delete_primary_trigger( $primary, $values['primary_table_name'], $values['primary_table_key'], $values['meta_table_key'] );
		$this->insert_meta_trigger( $primary, $values['meta_table_name'], $values['meta_table_key'] );
		$this->update_meta_trigger( $primary, $values['meta_table_name'], $values['meta_table_key'] );
		$this->delete_meta_trigger( $primary, $values['meta_table_name'], $values['meta_table_key'] );
	}

	/**
	 * Insert primary trigger
	 *
	 * @param string $primary
	 * @param string $primaty_table_name
	 * @param string $primary_table_key
	 * @param string $meta_table_key
	 * @return void
	 * @throws Exception
	 */
	protected function insert_primary_trigger( $primary, $primaty_table_name, $primary_table_key, $meta_table_key ) {
		global $wpdb;

		$insert_trigger = 'insert_' . $primary . '_trigger';
		$exist_triggers = $this->get_exist_triggers();
		$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );
		$primaty_table_name = esc_sql( $primaty_table_name );
		$meta_table_key = esc_sql( $meta_table_key );
		$primary_table_key = esc_sql( $primary_table_key );

		if ( ! in_array( $this->$insert_trigger, $exist_triggers ) ) {
			$sql = 'CREATE TRIGGER ' . $this->$insert_trigger . ' AFTER
				INSERT ON
				' . $primaty_table_name . '
				FOR
				EACH
				ROW
				INSERT INTO ' . $table_name . '
					(' . $meta_table_key . ', json)
				VALUES
					(NEW.' . $primary_table_key . ', "{}" )';
			$wpdb->query( $sql );

			if ( $wpdb->last_error ) {
				throw new Exception( $wpdb->last_error );
			}
		}
	}

	/**
	 * Delete primary trigger
	 *
	 * @param string $primary
	 * @param string $primaty_table_name
	 * @param string $primary_table_key
	 * @param string $meta_table_key
	 * @return void
	 * @throws Exception
	 */
	protected function delete_primary_trigger( $primary, $primaty_table_name, $primary_table_key, $meta_table_key ) {
		global $wpdb;

		$delete_trigger = 'delete_' . $primary . '_trigger';
		$exist_triggers = $this->get_exist_triggers();
		$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );
		$primaty_table_name = esc_sql( $primaty_table_name );
		$meta_table_key = esc_sql( $meta_table_key );
		$primary_table_key = esc_sql( $primary_table_key );

		if ( ! in_array( $this->$delete_trigger, $exist_triggers ) ) {
			$sql = 'CREATE TRIGGER ' . $this->$delete_trigger . ' BEFORE
			DELETE ON
			' . $primaty_table_name . '
			FOR
			EACH
			ROW
			DELETE FROM ' . $table_name . '
			WHERE ' . $meta_table_key . ' = OLD.' . $primary_table_key;
			$wpdb->query( $sql );

			if ( $wpdb->last_error ) {
				throw new Exception( $wpdb->last_error );
			}
		}
	}

	/**
	 * Insert meta trigger
	 *
	 * @param string $primary
	 * @param string $meta_table_name
	 * @param string $meta_table_key
	 * @return void
	 * @throws Exception
	 */
	protected function insert_meta_trigger( $primary, $meta_table_name, $meta_table_key ) {
		global $wpdb;

		$exist_triggers = $this->get_exist_triggers();
		$insert_meta_trigger = 'insert_' . $primary . 'meta_trigger';
		$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );
		$meta_table_name = esc_sql( $meta_table_name );
		$meta_table_key = esc_sql( $meta_table_key );

		if ( ! in_array( $this->$insert_meta_trigger, $exist_triggers ) ) {
			$sql = 'CREATE TRIGGER ' . $this->$insert_meta_trigger . ' AFTER
				INSERT ON
				' . $meta_table_name . '
				FOR
				EACH
				ROW
				UPDATE ' . $table_name . '
				SET
				`json` = JSON_SET
				(`json`, CONCAT
				("$.",NEW.meta_key), NEW.meta_value) WHERE ' . $meta_table_key . ' = NEW.'.$meta_table_key;
			$wpdb->query( $sql );

			if ( $wpdb->last_error ) {
				throw new Exception( $wpdb->last_error );
			}
		}
	}

	/**
	 * Update meta trigger
	 *
	 * @param string $primary
	 * @param string $meta_table_name
	 * @param string $meta_table_key
	 * @return void
	 * @throws Exception
	 */
	protected function update_meta_trigger( $primary, $meta_table_name, $meta_table_key ) {
		global $wpdb;

		$exist_triggers = $this->get_exist_triggers();
		$update_meta_trigger = 'update_' . $primary . 'meta_trigger';
		$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );
		$meta_table_name = esc_sql( $meta_table_name );
		$meta_table_key = esc_sql( $meta_table_key );

		if ( ! in_array( $this->$update_meta_trigger, $exist_triggers ) ) {
			$sql = 'CREATE TRIGGER ' . $this->$update_meta_trigger . ' AFTER
				UPDATE ON
				' . $meta_table_name . '
				FOR
				EACH
				ROW
				UPDATE ' . $table_name . '
				SET
				`json` = JSON_SET
				(`json`, CONCAT
				("$.",NEW.meta_key), NEW.meta_value) WHERE ' . $meta_table_key . ' = NEW.' . $meta_table_key;
			$wpdb->query( $sql );

			if ( $wpdb->last_error ) {
				throw new Exception( $wpdb->last_error );
			}
		}
	}

	/**
	 * Delete meta trigger
	 *
	 * @param string $primary
	 * @param string $meta_table_name
	 * @param string $meta_table_key
	 * @return void
	 * @throws Exception
	 */
	protected function delete_meta_trigger( $primary, $meta_table_name, $meta_table_key ) {
		global $wpdb;

		$exist_triggers = $this->get_exist_triggers();
		$delete_meta_trigger = 'delete_' . $primary . 'meta_trigger';
		$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );
		$meta_table_name = esc_sql( $meta_table_name );
		$meta_table_key = esc_sql( $meta_table_key );

		if ( ! in_array( $this->$delete_meta_trigger, $exist_triggers ) ) {
			$sql = 'CREATE TRIGGER ' . $this->$delete_meta_trigger . ' AFTER
				DELETE ON
				' . $meta_table_name . '
				FOR
				EACH
				ROW
				UPDATE ' . $table_name . '
				SET
				`json` = JSON_REMOVE(`json`, CONCAT
				("$.",OLD.meta_key)) WHERE ' . $meta_table_key . ' = OLD.'. $meta_table_key;
			$wpdb->query( $sql );

			if ( $wpdb->last_error ) {
				throw new Exception( $wpdb->last_error );
			}
		}
	}

	/**
	 * Drop triggers
	 *
	 * @param $primary
	 * @return void
	 * @throws Exception
	 */
	function drop_triggers( $primary ) {
		global $wpdb;

		$exist_triggers = $this->get_exist_triggers();

		foreach ( $this->triggers[$primary] as $value ) {
			if ( in_array( $value, $exist_triggers ) ) {
				$sql = 'DROP TRIGGER ' . esc_sql( $value );
				$wpdb->query( $sql );
			}
		}

		if ( $wpdb->last_error ) {
			throw new Exception( $wpdb->last_error );
		}
	}

	/**
	 * Update table from post, postmeta table.
	 *
	 * @param $primary
	 *
	 * @throws Exception
	 */
	function data_init( $primary ) {
		global $wpdb;
		$values = BEYOND_WPDB_PRIMARYS[$primary];
		$maxlen = 4294967295;

		$maxlen = apply_filters( 'beyond_group_concat_max_len', $maxlen );
		$sql = 'SET SESSION group_concat_max_len = ' . $maxlen;
		$wpdb->query( $sql );

		$this->data_init_sql( $primary, $values['primary_table_name'], $values['primary_table_key'], $values['meta_table_name'], $values['meta_table_key'] );
		$this->delete_non_existent_data_from_json( $primary, $values['primary_table_name'], $values['primary_table_key'] );
	}

	/**
	 * data init sql
	 *
	 * @param $primary
	 * @param $primary_table_name
	 * @param $primary_table_key
	 * @param $meta_table_name
	 * @param $meta_table_key
	 *
	 * @throws Exception
	 */
	protected function data_init_sql( $primary, $primary_table_name, $primary_table_key, $meta_table_name, $meta_table_key ) {
		global $wpdb;

		$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );
		$primary_table_name = esc_sql( $primary_table_name );
		$primary_table_key = esc_sql( $primary_table_key );
		$meta_table_name = esc_sql( $meta_table_name );
		$meta_table_key = esc_sql( $meta_table_key );

		$sql = 'INSERT INTO ' . $table_name . ' (' . $meta_table_key . ', json)
					SELECT ' . $primary_table_name . '.' . $primary_table_key . ',
					CONCAT("{",(group_concat(CONCAT(JSON_QUOTE(meta_key), ":", JSON_QUOTE(meta_value)))), "}")
				FROM ' . $primary_table_name . '
					INNER JOIN ' . $meta_table_name . '
					ON ' . $primary_table_name . '.' . $primary_table_key . ' = ' . $meta_table_name . '.' . $meta_table_key . '
				GROUP BY ' .  $primary_table_key . '
				ON DUPLICATE
				KEY
				UPDATE json = VALUES(json)';
		$wpdb->query( $sql );

		if ( $wpdb->last_error ) {
			throw new Exception( $wpdb->last_error );
		}
	}

	/**
	 * delete non-existent data from json table to posts table
	 *
	 * @param $primary
	 * @param $primary_table_name
	 * @param $primary_table_key
	 * @return void
	 * @throws Exception
	 */
	protected function delete_non_existent_data_from_json( $primary, $primary_table_name, $primary_table_key ) {
		global $wpdb;

		$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );
		$primary_table_name = esc_sql( $primary_table_name );
		$primary_table_key = esc_sql( $primary_table_key );
		$id = $primary . '_id';

		$sql = 'DELETE FROM ' . $table_name .
		       ' WHERE ' . $table_name . '.' . $id .
		       ' NOT IN (SELECT ' . $primary_table_key . ' from ' . $primary_table_name . ')';

		$wpdb->query( $sql );

		if ( $wpdb->last_error ) {
			throw new Exception( $wpdb->last_error );
		}
	}

	/**
	 * Get exist virtual columns
	 * @return array
	 */
	protected function get_exist_triggers() {
		$beyond_wpdb_info = new Beyond_Wpdb_Information();
		$beyond_wpdb_info->set_triggers();
		return $beyond_wpdb_info->get_triggers();
	}

	/**
	 * Get exist json tables
	 * @return array
	 */
	protected function get_exist_json_tables(){
		$beyond_wpdb_info = new Beyond_Wpdb_Information();
		$beyond_wpdb_info->set_tables();
		return $beyond_wpdb_info->get_tables();
	}
}

global $beyond_wpdb_sql;
$beyond_wpdb_sql = new Beyond_Wpdb_Sql();
