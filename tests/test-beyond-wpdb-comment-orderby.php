<?php
/**
 * Class Beyond_Wpdb_CommentQuery_OrderBy_Test
 *
 * @package Beyond_Wpdb
 */

class Beyond_Wpdb_CommentQuery_OrderBy_Test extends WP_UnitTestCase {

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
	 * Wp_Comment_Query orderby test
	 * Check that the orderby clause is converted correctly
	 */
	public function test_check_orderby_clause_converted()
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
	public function test_check_complex_orderby_clause_converted()
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
}
