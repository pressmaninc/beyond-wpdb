<?php

require_once( plugin_dir_path( __FILE__ ) . 'beyond-wpdb-ajax-test.php' );

class Beyond_Wpdb_Orderby_With_Vc_Test extends Beyond_Wpdb_Ajax_Test {

	/**
	 * Wp_Query orderby test for virtual column
	 */
	public function test_check_orderby_clause_converted_for_virtual_column() {
		$alias = esc_sql( constant( beyond_wpdb_get_define_table_name( 'post' ) ) );
		$expected_value = "ORDER BY $alias.region DESC";

		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'region', 'tokyo' );

		$args = array(
			'orderby' => 'meta_value',
			'meta_key'  => 'region',
			'meta_value' => array(
				'tokyo',
				'osaka',
				'kyoto'
			),
			'meta_compare' => 'IN'
		);

		$this->create_virtual_columns( 'region' );

		$the_query = new WP_Query( $args );

		$pos = strpos( $the_query->request, 'ORDER' );
		$result = substr( $the_query->request, $pos );
		$result = trim( strstr( $result, 'LIMIT', true ) );
		$this->assertEquals( $expected_value, $result );
	}

	/**
	 * Wp_Query orderby test
	 */
	public function test_check_complex_orderby_clause_converted_for_virtual_column() {
		$this->delete();

		$alias = esc_sql( constant( beyond_wpdb_get_define_table_name( 'post' ) ) );
		$expected_value = "ORDER BY CAST($alias.city AS CHAR) ASC, wptests_postmeta_beyond.state DESC";

		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'state', 'Wisconsin' );
		add_post_meta( $post_id, 'city', 'tokyo' );

		$args = array(
			'meta_query' => array(
				'relation' => 'AND',
				'state_clause' => array(
					'key' => 'state',
					'value' => 'Wisconsin',
					'compare' => '='
				),
				'city_clause' => array(
					'key' => 'city',
					'value' => array(
						'tokyo',
						'osaka'
					),
					'compare' => 'IN',
				),
			),
			'orderby' => array(
				'city_clause' => 'ASC',
				'state_clause' => 'DESC',
			)
		);

		$this->create_virtual_columns( 'state' . PHP_EOL . 'city' );

		$the_query = new WP_Query( $args );

		$pos = strpos( $the_query->request, 'ORDER' );
		$result = substr( $the_query->request, $pos );
		$result = trim( strstr( $result, 'LIMIT', true ) );
		$this->assertEquals( $expected_value, $result );
	}

	/**
	 * delete from json_table
	 */
	public function delete()
	{
		global $wpdb;
		foreach ( array_keys( BEYOND_WPDB_PRIMARYS ) as $primary ) {
			$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );
			$sql = "delete from {$table_name}";
			$wpdb->query( $sql );
		}
	}

	/**
	 * Create Virtual Columns
	 *
	 * @param $columns
	 */
	public function create_virtual_columns( $columns )
	{
		// create virtual columns
		$action = 'create-virtual-columns-action';
		$_POST['action'] = $action;
		$_POST['nonce'] = wp_create_nonce( $action );
		$_POST['primary'] = 'post';
		$_POST['columns'] = $columns;
		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
	}
}
