<?php
/**
 * Class Beyond_Wpdb_Options_Page_TEST
 *
 * @package Beyond_Wpdb
 */

require_once( plugin_dir_path( __FILE__ ) . 'beyond-wpdb-ajax-test.php' );

class Beyond_Wpdb_Options_Page_TEST extends Beyond_Wpdb_Ajax_Test {

	/**
	 * Get exist json tables test
	 */
	public function test_get_exist_json_tables()
	{
		global $wpdb;
		$action = 'get-exist-tables-action';

		$_POST['action'] = $action;
		$_POST['nonce'] = wp_create_nonce( $action );
		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$response = json_decode( $this->_last_response, true );
		$expected = array( "{$wpdb->prefix}commentmeta_beyond", "{$wpdb->prefix}usermeta_beyond", "{$wpdb->prefix}postmeta_beyond" );

		$this->assertEqualSets( $expected, $response['data'] );
	}

	public function test_get_virtual_columns()
	{
		global $wpdb;
		$action = 'get-virtual-columns-action';

		// create virtual columns
		$new_virtual_columns = array( 'country', 'city', 'population' );
		foreach ( $new_virtual_columns as $value ) {
			$json_key = '$.' . $value;
			$sql = "ALTER TABLE {$wpdb->prefix}postmeta_beyond ADD {$value} VARCHAR(255) GENERATED ALWAYS AS ( JSON_UNQUOTE( JSON_EXTRACT( json, '$json_key' ) ) )";
			$wpdb->query( $sql );
		}

		// get virtual columns
		$_POST['action'] = $action;
		$_POST['nonce'] = wp_create_nonce( $action );
		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertEqualSets( $new_virtual_columns, $response['data']["{$wpdb->prefix}postmeta_beyond"] );
	}

	/**
	 * Create virtual columns test
	 */
	public function test_create_virtual_columns()
	{
		global $wpdb;
		$action = 'create-virtual-columns-action';

		// create virtual columns
		$_POST['action'] = $action;
		$_POST['nonce'] = wp_create_nonce( $action );
		$_POST['primary'] = 'post';
		$_POST['columns'] = 'country' . PHP_EOL . 'city' . PHP_EOL . 'population';
		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		// get virtual columns
		$expected = array( "country", "city", "population" );
		$sql = "SHOW COLUMNS FROM {$wpdb->prefix}postmeta_beyond WHERE Field NOT IN ('post_id', 'user_id', 'comment_id', 'json')";
		$virtual_columns = $wpdb->get_results( $sql );
		$result = array();
		foreach ( $virtual_columns as $value ) {
			array_push( $result, $value->Field );
		}

		$this->assertEqualSets( $expected, $result );
	}

	/**
	 * delete virtual column test
	 */
	public function test_delete_virtual_column()
	{
		global $wpdb;
		$action = 'create-virtual-columns-action';

		// create virtual columns
		$new_virtual_columns = array( 'country', 'city', 'population' );
		foreach ( $new_virtual_columns as $value ) {
			$json_key = '$.' . $value;
			$sql = "ALTER TABLE {$wpdb->prefix}postmeta_beyond ADD {$value} VARCHAR(255) GENERATED ALWAYS AS ( JSON_UNQUOTE( JSON_EXTRACT( json, '$json_key' ) ) )";
			$wpdb->query( $sql );
		}

		// delete virtual columns
		$_POST['action'] = $action;
		$_POST['nonce'] = wp_create_nonce( $action );
		$_POST['primary'] = 'post';
		$_POST['columns'] = '';
		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		// get virtual columns
		$sql = "SHOW COLUMNS FROM {$wpdb->prefix}postmeta_beyond WHERE Field NOT IN ('post_id', 'user_id', 'comment_id', 'json')";
		$virtual_columns = $wpdb->get_results( $sql );
		$result = array();
		foreach ( $virtual_columns as $value ) {
			array_push( $result, $value->Field );
		}

		$this->assertEqualSets( array(), $result );
	}

}
