<?php
/**
 * Class Beyond_Wpdb_Options_Page_TEST
 *
 * @package Beyond_Wpdb
 */

require_once( plugin_dir_path( __FILE__ ) . 'beyond-wpdb-test.php' );

class Beyond_Wpdb_Options_Page_TEST extends Beyond_Wpdb_Test {

	/**
	 * create virtual column test
	 */
	public function test_create_virtual_column()
	{
		global $wpdb;
		$beyond_wpdb_settings_page = new Beyond_Wpdb_Settings_page();
		$input = array();
		$expected_array = array( 'country', 'region' );

		// create a post
		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'country', 'japan', false );
		add_post_meta( $post_id, 'region', 'tokyo', false );

		// create virtual columns
		$input_virtual_columns = 'country' . PHP_EOL . 'region';
		$input['postmeta_json'] = $input_virtual_columns;
		$beyond_wpdb_settings_page->create_virtual_column_and_index( $input );

		// get virtual columns
		$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( 'post' ) ) );
		$result = $wpdb->get_results( "show columns from {$table_name} where Field not in ('post_id', 'user_id', 'comment_id', 'json')" );

		$result_array = array();
		foreach ( $result as $value ) {
			array_push( $result_array, $value->Field );
		}

		$this->assertEquals( $expected_array, $result_array );
	}

	/**
	 * delete virtual column test
	 */
	public function test_delete_virtual_column()
	{
		global $wpdb;
		$beyond_wpdb_settings_page = new Beyond_Wpdb_Settings_page();
		$input = array();
		$expected_array = array();

		// delete virtual columns
		$input['postmeta_json'] = '';
		$beyond_wpdb_settings_page->delete_virtual_column( $input );


		$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( 'post' ) ) );
		$result_array = $wpdb->get_results( "show columns from {$table_name} where Field not in ('post_id', 'user_id', 'comment_id', 'json')" );

		$this->assertEquals( $expected_array, $result_array );
	}

}
