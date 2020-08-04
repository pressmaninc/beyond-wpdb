<?php
require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';

class Beyond_Wpdb_Ajax_Test extends WP_Ajax_UnitTestCase {
	public function setUp()
	{
		$register_hook = new Beyond_Wpdb_Register();
		foreach ( array_keys( BEYOND_WPDB_PRIMARYS ) as $primary ) {
			$register_hook::activation( $primary );
		}

		parent::setUp();
	}

	public function tearDown()
	{
		parent::tearDown();

		$register_hook = new Beyond_Wpdb_Register();
		foreach ( array_keys( BEYOND_WPDB_PRIMARYS ) as $primary ) {
			$register_hook::deactivation( $primary );
		}
	}

	/**
	 * Test activation success
	 */
	public function test_activation_success()
	{
		global $wpdb;
		foreach ( array_keys( BEYOND_WPDB_PRIMARYS ) as $primary ) {
			$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );
			$result = $wpdb->get_results( "SHOW TABLES LIKE '{$table_name}'", ARRAY_A );
			$this->assertEquals( $table_name, array_values( $result[0] )[0] );
		}
	}
}
