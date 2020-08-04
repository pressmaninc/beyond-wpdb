<?php
/**
 * Class Beyond_Wpdb_OrderBy_Test
 *
 * @package Beyond_Wpdb
 */

require_once( plugin_dir_path( __FILE__ ) . 'beyond-wpdb-test.php' );

class Beyond_Wpdb_OrderBy_Test extends Beyond_Wpdb_Test {

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
		// print_r( $the_query->request );

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

	/**
	 * Wp_Comment_Query orderby test
	 * Check that the orderby clause is converted correctly
	 */
	public function test_check_wp_comment_query_orderby_clause_converted()
	{
		$alias = esc_sql( constant( beyond_wpdb_get_define_table_name( 'comment' ) ) );
		$expected_value = "ORDER BY JSON_EXTRACT($alias.json, '$.rating') DESC,  wptests_comments.comment_ID DESC";

		// create a post
		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'language', 'japanese' );
		// create a comment
		$comment_id = $this->factory->comment->create( array( 'post_id' => $post_id ) );
		add_comment_meta( $comment_id, 'rating', '5' );


		$args = array(
			'orderby' => 'meta_value',
			'meta_query' => array(
				array(
					'key' => 'rating',
					'value' => array(
						'3', '5'
					),
					'compare' => 'BETWEEN'
				)
			)
		);

		$the_query = new WP_Comment_Query( $args );
		$pos = strpos($the_query->request, 'ORDER');
		$this->assertEquals( $expected_value, trim( substr( $the_query->request, $pos ) ) );
	}

	/**
	 * Wp_Query orderby test
	 * Check that the complex orderby clause is converted correctly
	 */
	public function test_check__wp_comment_query_complex_orderby_clause_converted()
	{
		$alias = esc_sql( constant( beyond_wpdb_get_define_table_name( 'comment' ) ) );
		$expected_value = "ORDER BY JSON_EXTRACT($alias.json, '$.subjects') DESC, CAST(JSON_EXTRACT($alias.json, '$.rating') AS CHAR) ASC,  wptests_comments.comment_ID DESC";

		// create a post
		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'language', 'japanese' );
		// create a comment
		$comment_id = $this->factory->comment->create( array( 'post_id' => $post_id ) );
		add_comment_meta( $comment_id, 'rating', '5' );
		add_comment_meta( $comment_id, 'subjects', 'mathematics' );


		$args = array(
			'orderby' => array(
				'subjects_clause' => 'DESC',
				'rating_clause' => 'ASC',
			),
			'meta_query' => array(
				'subjects_clause' => array(
					'key' => 'subjects',
					'value' => array(
						'mathematics',
						'chemistry',
						'geography'
					),
					'compare' => 'IN'
				),
				'rating_clause' => array(
					'key' => 'rating',
					'value' => array(
						'1', '5'
					),
					'compare' => 'BETWEEN'
				)
			)
		);

		$the_query = new WP_Comment_Query( $args );
		$pos = strpos($the_query->request, 'ORDER');
		$this->assertEquals( $expected_value, trim( substr( $the_query->request, $pos ) ) );
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
