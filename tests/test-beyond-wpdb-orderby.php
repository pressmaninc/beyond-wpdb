<?php
/**
 * Class Beyond_Wpdb_OrderBy_Test
 *
 * @package Beyond_Wpdb
 */

class Beyond_Wpdb_OrderBy_Test extends WP_UnitTestCase {

	public function setUp()
	{
		parent::setUp();

		$register_hook = new Beyond_Wpdb_Register_Hook();
		$register_hook::activation();
	}

	public function tearDown()
	{
		parent::tearDown();

		$register_hook = new Beyond_Wpdb_Register_Hook();
		$register_hook::deactivation();
	}


	/**
	 * Wp_Query orderby test
	 * Check that the orderby clause is converted correctly
	 */
	public function test_check_orderby_clause_converted() {
		$alias = esc_sql( constant( beyond_wpdb_get_define_table_name( 'post' ) ) );
		$expected_value = "ORDER BY JSON_EXTRACT($alias.json, '$.region') DESC";

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

		$the_query = new WP_Query( $args );

		$pos = strpos( $the_query->request, 'ORDER' );
		$result = substr( $the_query->request, $pos );
		$result = trim( strstr( $result, 'LIMIT', true ) );
		$this->assertEquals( $expected_value, $result );
	}

	/**
	 * Wp_Query orderby test
	 * Check that the complex orderby clause is converted correctly
	 */
	public function test_check_complex_orderby_clause_converted() {
		$alias = esc_sql( constant( beyond_wpdb_get_define_table_name( 'post' ) ) );
		$expected_value = "ORDER BY CAST(JSON_EXTRACT($alias.json, '$.city') AS CHAR) ASC, JSON_EXTRACT($alias.json, '$.state') DESC";

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

		$the_query = new WP_Query( $args );

		$pos = strpos( $the_query->request, 'ORDER' );
		$result = substr( $the_query->request, $pos );
		$result = trim( strstr( $result, 'LIMIT', true ) );
		$this->assertEquals( $expected_value, $result );
	}

}
