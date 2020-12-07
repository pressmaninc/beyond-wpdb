<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Beyond_Wpdb_Admin_Ajax
 */
class Beyond_Wpdb_Admin_Ajax {
	/**
	 * Beyond_Wpdb_Admin_Ajax constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_get-exist-tables-action', array( $this, 'get_exist_tables_action' ) );
		add_action( 'wp_ajax_get-virtual-columns-action', array( $this, 'get_virtual_columns_action' ) );
		add_action( 'wp_ajax_create-virtual-columns-action', array( $this, 'create_virtual_columns_action' ) );
		add_action( 'wp_ajax_activate-action', array( $this, 'activate_action' ) );
		add_action( 'wp_ajax_deactivate-action', array( $this, 'deactivate_action' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'my_enqueue' ) );
	}

	/**
	 * load script
	 */
	function my_enqueue() {
		global $wpdb;
		$handle = 'beyond-wpdb-script';

		wp_enqueue_script( $handle, plugin_dir_url( __FILE__ ) . '../js/beyond-wpdb.js', array('jquery'), '1.0', true );
		// Localizing constants.
		wp_localize_script( $handle, 'BEYOND_WPDB_CONFIG', [
			'api'    => admin_url( 'admin-ajax.php' ),
			'prefix' => $wpdb->prefix,
			'json_tables' => array(
				"{$wpdb->prefix}postmeta_beyond",
				"{$wpdb->prefix}usermeta_beyond",
				"{$wpdb->prefix}commentmeta_beyond"
			),
			'exist_tables' => array(
				'get' => array(
					'action' => 'get-exist-tables-action',
					'nonce' => wp_create_nonce( 'get-exist-tables-action' ),
				)
			),
			'data_init' => array(
				'create' => array(
					'action' => 'activate-action',
					'nonce' => wp_create_nonce('activate-action')
				),
				'delete' => array(
					'action' => 'deactivate-action',
					'nonce' => wp_create_nonce('deactivate-action')
				)
 			),
			'virtual_columns' => array(
				'get' => array(
					'action' => 'get-virtual-columns-action',
					'nonce' => wp_create_nonce( 'get-virtual-columns-action' ),
				),
				'create' => array(
					'action' => 'create-virtual-columns-action',
					'nonce' => wp_create_nonce('create-virtual-columns-action')
				)
			)
		]);
	}

	/**
	 * Get exist json tables
	 */
	public function get_exist_tables_action()
	{
		$action = 'get-exist-tables-action';

		if ( check_ajax_referer( $action, 'nonce', false ) ) {
			global $wpdb;
			$data = array();
			$status = '';
			$result = $this->get_exist_json_tables();

			if ( ! $wpdb->last_error ) {
				$data['data'] = $result;
				$data['message'] = "Getting tables succeeded.";
				$status = 200;
			} else {
				$data['data'] = $wpdb->last_error;
				$data['message'] = 'Getting columns failed.';
				$status = 500;
			}
		} else {
			$data['data'] = 'Forbidden';
			$data['message'] = 'Forbidden';
			$status = 403;
		}

		wp_send_json( $data, $status );
		die();
	}

	/**
	 * get exist columns in json table
	 */
	public function get_virtual_columns_action() {
		$action = 'get-virtual-columns-action';

		if ( check_ajax_referer( $action, 'nonce', false ) ) {
			global $wpdb;
			$data = array();
			$status = '';
			$result = $this->get_exist_virtual_columns();

			if ( ! $wpdb->last_error ) {
				$data['data'] = $result;
				$data['message'] = "Getting virtual columns succeeded.";
				$status = 200;
			} else {
				$data['data'] = $wpdb->last_error;
				$data['message'] = 'Getting virtual columns failed.';
				$status = 500;
			}
		} else {
			$data['data'] = 'Forbidden';
			$data['message'] = 'Forbidden';
			$status = 403;
		}

		wp_send_json( $data, $status );
		die();
	}

	/**
	 * create virtual columns
	 */
	public function create_virtual_columns_action() {
		$action = 'create-virtual-columns-action';

		if ( check_ajax_referer( $action, 'nonce', false ) ) {
			global $wpdb;
			$data = array();
			$status = '';
			$primary = $_POST['primary'];
			$columns = $_POST['columns'];
			$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );
			$exist_columns = $this->get_exist_virtual_columns();
			$exist_tables = $this->get_exist_json_tables();
			$new_columns = explode( PHP_EOL, $columns );

			if ( in_array( $table_name, $exist_tables) ) {
				foreach ( $new_columns as $value ) {
					$value = esc_sql( $value );

					// If $value already exists, continue
					if ( in_array( $value, $exist_columns[$table_name] ) || ! $value ) {
						continue;
					}

					$json_key = '$.' . $value;

					// create virtual column
					$sql = "ALTER TABLE {$table_name} ADD {$value} VARCHAR(255) GENERATED ALWAYS AS ( JSON_UNQUOTE( JSON_EXTRACT( json, '$json_key' ) ) )";
					$wpdb->query( $sql );

					// create index
					$sql = "ALTER TABLE {$table_name} ADD INDEX ({$value})";
					$wpdb->query( $sql );
				}

				// If $new_columns are not in the virtual column that exists, delete it
				foreach ( $exist_columns["{$table_name}"] as $column ) {
					if ( ! in_array( $column, $new_columns ) ) {
						$sql = "ALTER TABLE {$table_name} DROP COLUMN {$column}";
						$wpdb->query( $sql );
					}
				}

				if ( ! $wpdb->last_error ) {
					$data['data'] = '';
					$data['message'] = "Creating virtual columns succeeded.";
					$status = 201;
				} else {
					$data['data'] = $wpdb->last_error;
					$data['message'] = 'Creating virtual columns failed.';
					$status = 500;
				}
			}

		} else {
			$data['data'] = 'Forbidden';
			$data['message'] = 'Forbidden';
			$status = 403;
		}

		wp_send_json( $data, $status );
		die();
	}

	/**
	 * activate table
	 */
	public function activate_action() {
		$action = 'activate-action';

		if ( check_ajax_referer( $action, 'nonce', false ) ) {
			$beyond_wpdb_register = new Beyond_Wpdb_Register();
			$data = array();
			$status = '';
			$primary = $_POST['primary'];
			$table_name  = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );

			try {
				$beyond_wpdb_register::activation( $primary );
				$data['data'] = $table_name;
				$data['message'] = 'Activation succeeded.';
				$status = 201;
			} catch ( Exception $e ) {
				$beyond_wpdb_register::deactivation( $primary );
				$data['data'] = $e->getMessage();
				$data['message'] = 'Activation failed.';
				$status = 500;
			};

		} else {
			$data['data'] = 'Forbidden';
			$data['message'] = 'Forbidden';
			$status = 403;
		}

		wp_send_json( $data, $status );
		die();
	}

	/**
	 * deactivate table
	 */
	public function deactivate_action() {
		$action = 'deactivate-action';

		if ( check_ajax_referer( $action, 'nonce', false ) ) {
			$beyond_wpdb_register = new Beyond_Wpdb_Register();
			$data = array();
			$status = '';
			$primary = $_POST['primary'];
			$beyond_wpdb_register::deactivation( $primary );
			$table_name  = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );

			try {
				$data['data'] = $table_name;
				$data['message'] = 'Deactivation succeeded.';
				$status = 201;
			} catch ( Exception $e ) {
				$data['data'] = $e->getMessage();
				$data['message'] = 'Deactivation failed.';
				$status = 500;
			}
		} else {
			$data['data'] = 'Forbidden';
			$data['message'] = 'Forbidden';
			$status = 403;
		}

		wp_send_json( $data, $status );
		die();
	}

	/**
	 * Get exist virtual columns
	 * @return array
	 */
	public function get_exist_virtual_columns() {
		$beyond_wpdb_info = new Beyond_Wpdb_Information();
		$beyond_wpdb_info->set_columns();
		return $beyond_wpdb_info->get_columns();
	}

	/**
	 * Get exist json tables
	 * @return array
	 */
	public function get_exist_json_tables(){
		$beyond_wpdb_info = new Beyond_Wpdb_Information();
		$beyond_wpdb_info->set_tables();
		return $beyond_wpdb_info->get_tables();
	}
}

new Beyond_Wpdb_Admin_Ajax();
