<?php
/**
 * Class Beyond_Wpdb_Query_OrderBy_Test
 *
 * @package Beyond_Wpdb
 */

require_once( plugin_dir_path( __FILE__ ) . '../beyond-wpdb.php' );

class Beyond_Wpdb_Query_OrderBy_Test extends WP_UnitTestCase {

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
		$wpdb->get_results( "delete from wptests_postmeta_json" );
		$register_hook = new Beyond_Wpdb_Register_Hook();
		$register_hook::deactivation();
	}


	/**
	 * Wp_Query - orderby
	 * Check that the orderby clause is converted correctly
	 */
	public function test_check_orderby_clause() {
		$expected_value = "ORDER BY JSON_EXTRACT(json, '$.region') DESC LIMIT 0, 10";

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

		$pos = strpos($the_query->request, 'ORDER');
		$this->assertEquals( $expected_value, substr($the_query->request, $pos) );
	}

	/**
	 * Wp_Query - orderby
	 * Check that the orderby clause is converted correctly
	 */
	public function test_check_orderby_complex() {
		$expected_value = "ORDER BY wptests_posts.post_name DESC, CAST(JSON_EXTRACT(json, '$.city') AS CHAR) ASC, JSON_EXTRACT(json, '$.state') DESC LIMIT 0, 10";

		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'state', 'Wisconsin' );
		add_post_meta( $post_id, 'city', 'tokyo' );

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
				'post_name' => 'desc',
				'city_clause' => 'ASC',
				'state_clause' => 'DESC',
			),
		);

		$the_query = new WP_Query( $args );

		$pos = strpos($the_query->request, 'ORDER');
		$this->assertEquals( $expected_value, substr($the_query->request, $pos) );
	}
}
