<?php
/**
 * Class Beyond_Wpdb_User_OrderBy_Test
 *
 * @package Beyond_Wpdb
 */

class Beyond_Wpdb_User_OrderBy_Test extends WP_UnitTestCase {

	public function setUp()
	{
		parent::setUp();

		$register_hook = new Beyond_Wpdb_Register_Hook();
		$register_hook::activation();
	}

	public function tearDown()
	{
		parent::tearDown();

		global $wpdb;
		$register_hook = new Beyond_Wpdb_Register_Hook();
		$register_hook::deactivation();
	}

	/**
	 * WP_User_Query - orderby test
	 */
	public function test_wp_user_query_orderby()
	{
		$alias = esc_sql( constant( beyond_wpdb_get_define_table_name( 'user' ) ) );
		$expected_value = "ORDER BY JSON_EXTRACT($alias.json, '$.hobby') ASC";

		// create a user
		$user_id = $this->factory->user->create();
		add_user_meta( $user_id, 'hobby', 'walking' );

		$args = array(
			'order'    => 'ASC',
			'orderby'  => 'meta_value',
			'meta_key' => 'hobby',
			'meta_value' => array(
				'walking',
				'fishing',
			),
			'compare_key' => 'IN'
		);

		$the_query = new WP_User_Query( $args );
		$pos = strpos( $the_query->request, 'ORDER' );
		$this->assertEquals( $expected_value, trim( substr( $the_query->request, $pos ) ) );
	}

	/**
	 * WP_User_Query - orderby test
	 */
	public function test_wp_user_query_orderby_complex()
	{
		$alias = esc_sql( constant( beyond_wpdb_get_define_table_name( 'user' ) ) );
		$expected_value = "ORDER BY JSON_EXTRACT($alias.json, '$.state') DESC, CAST(JSON_EXTRACT($alias.json, '$.city') AS CHAR) ASC";

		// create a user
		$user_id = $this->factory->user->create();
		add_user_meta( $user_id, 'state', 'Wisconsin' );
		add_user_meta( $user_id, 'city', 'tokyo' );

		$args = array(
			'meta_query' => array(
				'relation' => 'AND',
				'state_clause' => array(
					'key' => 'state',
					'value' => 'Wisconsin',
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
				'state_clause' => 'DESC',
				'city_clause' => 'ASC',
			)
		);

		$the_query = new WP_User_Query( $args );
		$pos = strpos($the_query->request, 'ORDER');
		$this->assertEquals( $expected_value, trim( substr( $the_query->request, $pos ) ) );
	}
}
