<?php

class Beyond_Wpdb_Test extends WP_UnitTestCase {
	public function setUp()
	{
		$this->metaQuery = new Beyond_Wpdb_Meta_Query();
		$register_hook = new Beyond_Wpdb_Register_Hook();
		$register_hook::activation();

		parent::setUp();
	}

	public function tearDown()
	{
		parent::tearDown();

		// remove virtual columns for test
		$this->delete_virtual_columns();
		$register_hook = new Beyond_Wpdb_Register_Hook();
		$register_hook::deactivation();
	}

	/**
	 * delete virtual columns from all meta_json tables for test
	 */
	protected function delete_virtual_columns()
	{
		$beyond_wpdb_settings_page = new Beyond_Wpdb_Settings_page();
		$input = array();
		$input['postmeta_json'] = '';
		$input['usermeta_json'] = '';
		$input['commentmeta_json'] = '';
		$beyond_wpdb_settings_page->delete_virtual_column( $input );
	}
}
