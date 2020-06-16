<?php
/**
 * Class Beyond_Wpdb_Meta_Query_Test
 *
 * @package Beyond_Wpdb
 */

class Beyond_Wpdb_Meta_Query_Test extends WP_UnitTestCase {
	protected $metaQuery = '';

	public function setUp()
	{
		parent::setUp();

		$this->metaQuery = new Beyond_Wpdb_Meta_Query();
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
	 * Test the check function - success
	 */
	public function test_check_success() {

		$queries = array(
			array(
				'key'     => 'key2',
				'value'   => 'value2',
				'compare_key' => '=',
			),
			array(
				'key'     => 'key3',
				'value'   => 'value3',
				'compare_key' => 'EXISTS',
			)
		);

		$method = $this->get_access_protected( 'check' );
		$this->assertTrue( $method->invoke( $this->metaQuery, $queries ) );
	}

	/**
	 * Test the check function - failure
	 */
	public function test_check_failure() {
		$queries = array(
			array(
				'key'     => 'key1',
				'value'   => 'value1',
				'compare_key' => '=',
			),
			array(
				'key'     => 'key2',
				'value'   => 'value2',
				'compare_key' => 'IN',
			),
			'meta_key' => 'key3',
			'meta_value' => 'value3',
			'meta_compare_key' => 'LIKE',
		);

		$method = $this->get_access_protected( 'check' );
		$this->assertFalse( $method->invoke( $this->metaQuery, $queries ) );
	}

	/**
	 * Test the check function recursively - success
	 */
	public function test_check_recursively_success() {
		$queries = array(
			array(
				'key'     => 'key1',
				'value'   => 'value1',
				'compare_key' => '=',
			),
			array(
				'key'     => 'key2',
				'value'   => 'value2',
				'compare_key' => 'EXISTS',
			),
			array(
				array(
					'key'     => 'key3',
					'value'   => 'value3',
					'compare_key' => '=',
				),
				array(
					'key'     => 'key4',
					'value'   => 'value4',
					'compare_key' => 'EXISTS',
				)
			)
		);

		$method = $this->get_access_protected( 'check' );
		$this->assertTrue( $method->invoke( $this->metaQuery, $queries ) );
	}

	/**
	 * Test the check function recursively - failure
	 */
	public function test_check_recursively_failure() {
		$queries = array(
			array(
				'key'     => 'key1',
				'value'   => 'value1',
				'compare_key' => '=',
			),
			array(
				'key'     => 'key2',
				'value'   => 'value2',
				'compare_key' => '=',
			),
			array(
				array(
					'key'     => 'key3',
					'value'   => 'value3',
					'compare_key' => '=',
				),
				array(
					'key'     => 'key4',
					'value'   => 'value4',
					'compare_key' => 'IN',
				)
			)
		);

		$method = $this->get_access_protected( 'check' );
		$this->assertFalse( $method->invoke( $this->metaQuery, $queries ) );
	}

	/**
	 * Test the _get_meta_table function recursively - success
	 */
	public function test_get_meta_table_success()
	{
		global $wpdb;
		$metaKey = ['post', 'user', 'comment'];

		$method = $this->get_access_protected( '_get_meta_table' );
		foreach ( $metaKey as $type ) {
			$expected_table_name = $wpdb->prefix . $type. 'meta_json';
			$this->assertEquals( $expected_table_name, $method->invoke($this->metaQuery, $type ) );
		}
	}

	/**
	 * Test the _get_meta_table function recursively - failure
	 */
	public function test_get_meta_table_failure()
	{
		global $wpdb;
		$metaKey = ['postA', 'userA', 'commentA'];

		$method = $this->get_access_protected( '_get_meta_table' );
		foreach ( $metaKey as $type ) {
			$expected_table_name = $wpdb->prefix . $type. 'meta_json';
			$this->assertNotEquals( $expected_table_name, $method->invoke( $this->metaQuery, $type ) );
		}
	}

	/**
	 * Test the get_sql function to see if the join clause is correct
	 */
	public function test_get_sql_join()
	{
		global $wpdb;

		$expected_join = 'INNER JOIN wptests_postmeta_json AS wptests_postmeta_json ON ( wptests_posts.id = wptests_postmeta_json.post_id )';
		$type = 'post';
		$queries = array(
			array(
				'key'     => 'region',
				'value'   => 'tokyo',
				'compare_key' => '=',
			),
			array(
				'key'     => 'language',
				'value'   => 'japanese',
				'compare_key' => 'EXISTS',
			),
		);
		$primary_table = $wpdb->prefix . 'posts';
		$primary_id_column = 'id';
		$context = null;

		// sql[join]チェック
		$method = $this->get_access_protected( 'get_sql' );
		$sql = $method->invoke( $this->metaQuery, $type, $queries, $primary_table, $primary_id_column, $context );
		$this->assertEquals( $expected_join, trim( $sql['join'] ) );
	}

	/**
	 * Test the get_sql function to see if the where clause is correct
	 * When compare is "=" and "EXISTS"
	 *
	 */
	public function test_get_sql_where_equal_or_exists()
	{
		global $wpdb;

		$expected_where = $this->remove_spaces(
			"AND ( JSON_EXTRACT(wptests_postmeta_json.json, '$.region') = 'tokyo' 
					AND JSON_EXTRACT(wptests_postmeta_json.json, '$.language') = 'japanese' )"
		);

		$type = 'post';
		$queries = array(
			array(
				'key'     => 'region',
				'value'   => 'tokyo',
				'compare' => '=',
				'compare_key' => '=',
			),
			array(
				'key'     => 'language',
				'value'   => 'japanese',
				'compare' => 'EXISTS',
				'compare_key' => '=',
			),
		);
		$primary_table = $wpdb->prefix . 'posts';
		$primary_id_column = 'id';
		$context = null;

		$method = $this->get_access_protected( 'get_sql' );
		$sql = $method->invoke( $this->metaQuery, $type, $queries, $primary_table, $primary_id_column, $context );
		$where = $this->remove_spaces( $sql['where'] );
		$this->assertEquals( $expected_where,  $where );
	}

	/**
	 * Test the get_sql function to see if the where clause is correct
	 * When compare is "LIKE" and "NOT LIKE"
	 */
	public function test_get_sql_where_like_or_not_like()
	{
		global $wpdb;

		$metaValue1 = '%' . $wpdb->esc_like( 'tokyo' ) . '%';
		$metaValue1 = $wpdb->prepare( '%s', $metaValue1 );
		$metaValue2 = '%' . $wpdb->esc_like( 'japanese' ) . '%';
		$metaValue2 = $wpdb->prepare( '%s', $metaValue2 );
		$expected_where = $this->remove_spaces(
			"AND ( JSON_EXTRACT(wptests_postmeta_json.json, '$.region') LIKE $metaValue1 
					AND JSON_EXTRACT(wptests_postmeta_json.json, '$.language') NOT LIKE $metaValue2 )"
		);

		$type = 'post';
		$queries = array(
			array(
				'key'     => 'region',
				'value'   => 'tokyo',
				'compare' => 'LIKE',
				'compare_key' => '=',
			),
			array(
				'key'     => 'language',
				'value'   => 'japanese',
				'compare' => 'NOT LIKE',
				'compare_key' => '=',
			),
		);
		$primary_table = $wpdb->prefix . 'posts';
		$primary_id_column = 'id';
		$context = null;

		$method = $this->get_access_protected( 'get_sql' );
		$sql = $method->invoke( $this->metaQuery, $type, $queries, $primary_table, $primary_id_column, $context );
		$where = $this->remove_spaces( $sql['where'] );
		$this->assertEquals( $expected_where,  $where );
	}

	/**
	 * Test the get_sql function to see if the where clause is correct
	 * When compare is "IN" and "NOT IN"
	 */
	public function test_get_sql_where_in_or_not_in()
	{
		global $wpdb;

		$json = 'wptests_postmeta_json.json';

		$expected_where = $this->remove_spaces(
			"AND ( ( JSON_EXTRACT($json, '$.regions') = 'tokyo' OR JSON_EXTRACT($json, '$.regions') = 'osaka' OR JSON_EXTRACT($json, '$.regions') = 'kyoto' )
					AND ( JSON_EXTRACT($json, '$.languages') != 'japanese' OR JSON_EXTRACT($json, '$.languages') != 'english' OR JSON_EXTRACT($json, '$.languages') != 'chinese' ) )"
		);

		$type = 'post';
		$queries = array(
			array(
				'key'     => 'regions',
				'value'   => array(
					'tokyo',
					'osaka',
					'kyoto'
				),
				'compare' => 'IN',
				'compare_key' => '=',
			),
			array(
				'key'     => 'languages',
				'value'   => array(
					'japanese',
					'english',
					'chinese'
				),
				'compare' => 'NOT IN',
				'compare_key' => '=',
			),
		);
		$primary_table = $wpdb->prefix . 'posts';
		$primary_id_column = 'id';
		$context = null;

		$method = $this->get_access_protected( 'get_sql' );
		$sql = $method->invoke( $this->metaQuery, $type, $queries, $primary_table, $primary_id_column, $context );
		$where = $this->remove_spaces( $sql['where'] );
		$this->assertEquals( $expected_where,  $where );
	}

	/**
	 * Test the get_sql function to see if the where clause is correct
	 * When compare is "BETWEEN" and "NOT BETWEEN"
	 */
	public function test_get_sql_where_between_or_not_between()
	{
		global $wpdb;

		$json = 'wptests_postmeta_json.json';

		$expected_where = $this->remove_spaces(
			"AND ( ( '2020-05-01' <= JSON_EXTRACT($json, '$.date1') and JSON_EXTRACT($json, '$.date1') <= '2020-06-01' ) 
					AND ( '2020-05-01' > JSON_EXTRACT($json, '$.date2') OR JSON_EXTRACT($json, '$.date2') > '2020-06-01' ) )"
		);

		$type = 'post';
		$queries = array(
			array(
				'key'     => 'date1',
				'value'   => array(
					'2020-05-01',
					'2020-06-01'
				),
				'compare' => 'BETWEEN',
				'compare_key' => '=',
			),
			array(
				'key'     => 'date2',
				'value'   => array(
					'2020-05-01',
					'2020-06-01'
				),
				'compare' => 'NOT BETWEEN',
				'compare_key' => '=',
			),
		);
		$primary_table = $wpdb->prefix . 'posts';
		$primary_id_column = 'id';
		$context = null;

		$method = $this->get_access_protected( 'get_sql' );
		$sql = $method->invoke( $this->metaQuery, $type, $queries, $primary_table, $primary_id_column, $context );
		$where = $this->remove_spaces( $sql['where'] );
		$this->assertEquals( $expected_where,  $where );
	}

	/**
	 * Test the get_sql function to see if the where clause is correct recursively
	 */
	public function test_get_sql_where_recursively()
	{
		global $wpdb;

		$json = 'wptests_postmeta_json.json';

		$expected_where = $this->remove_spaces(
			"AND ( JSON_EXTRACT($json, '$.region') = 'tokyo' 
					AND ( JSON_EXTRACT($json, '$.hobbies') = 'walking' OR JSON_EXTRACT($json, '$.hobbies') = 'fishing' ) 
					AND ( JSON_EXTRACT($json, '$.language') = 'japanese' OR JSON_EXTRACT($json, '$.language') = 'english' ) )"
		);

		$type = 'post';
		$queries = array(
			array(
				'key'     => 'region',
				'value'   => 'tokyo',
				'compare' => '=',
				'compare_key' => '=',
			),
			array(
				'key'     => 'hobbies',
				'value'   => array(
					'walking',
					'fishing'
				),
				'compare' => 'IN',
				'compare_key' => '=',
			),
			array(
				'relation' => 'OR',
				array(
					'key'     => 'language',
					'value'   => 'japanese',
					'compare' => '=',
					'compare_key' => '=',
				),
				array(
					'key'     => 'language',
					'value'   => 'english',
					'compare' => '=',
					'compare_key' => '=',
				)
			)
		);
		$primary_table = $wpdb->prefix . 'posts';
		$primary_id_column = 'id';
		$context = null;

		$method = $this->get_access_protected('get_sql');
		$sql = $method->invoke( $this->metaQuery, $type, $queries, $primary_table, $primary_id_column, $context );
		$where = $this->remove_spaces( $sql['where'] );
		$this->assertEquals( $expected_where,  $where );
	}

	/**
	 * get_meta_sql - "=" or "EXISTS" pattern
	 */
	public function test_get_meta_sql_post_equal_or_exists()
	{
		// create a post
		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'region', 'tokyo', false );
		add_post_meta( $post_id, 'language', 'japanese', false );

		$args = array(
			'meta_compare_key' => '=',
			'meta_key' => 'region',
			'meta_value' => 'tokyo',
			'meta_query' => array(
				array(
					'key'     => 'region',
					'value'   => 'tokyo',
					'compare' => '=',
					'compare_key' => '=',
				),
				array(
					'key'     => 'language',
					'value'   => 'japanese',
					'compare' => 'EXISTS',
					'compare_key' => '=',
				)
			)
		);
		$the_query = new WP_Query( $args );
		$this->assertCount( 1, $the_query->get_posts() );
	}

	/**
	 * get_meta_sql - "LIKE" or "NOT LIKE" pattern
	 */
	public function test_get_meta_sql_post_like_or_notLike()
	{
		// create a post
		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'region', 'tokyo', false );
		add_post_meta( $post_id, 'language', 'japanese', false );

		$args = array(
			'meta_query' => array(
				array(
					'key'     => 'region',
					'value'   => 'tokyo',
					'compare' => 'LIKE',
					'compare_key' => '=',
				),
				array(
					'key'     => 'language',
					'value'   => 'english',
					'compare' => 'NOT LIKE',
					'compare_key' => '=',
				)
			)
		);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$this->assertCount( 1, $the_query->get_posts() );
	}

	/**
	 * get_meta_sql - IN or NOT IN pattern
	 */
	public function test_get_meta_sql_post_in_or_notIn()
	{
		// create a post
		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'hobby', 'waking', false );
		add_post_meta( $post_id, 'language', 'japanese', false );

		$args = array(
			'meta_query' => array(
				array(
					'key'     => 'hobby',
					'value'   => array(
						'waking',
						'fishing'
					),
					'compare' => 'IN',
					'compare_key' => '=',
				),
				array(
					'key'     => 'language',
					'value'   => array(
						'english',
						'chinese'
					),
					'compare' => 'NOT IN',
					'compare_key' => '=',
				)
			)
		);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$this->assertCount( 1, $the_query->get_posts() );

	}

	/**
	 * get_meta_sql- WP_Query - BETWEEN or NOT BETWEEN pattern
	 */
	public function test_get_meta_sql_post_between_or_notBetween()
	{

		// create a post
		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'date1', '2020-06-05', false );
		add_post_meta( $post_id, 'date2', '2080-06-05', false );

		$args = array(
			'meta_query' => array(
				array(
					'key'     => 'date1',
					'value'   => array(
						'2020-06-01',
						'2020-06-30'
					),
					'compare' => 'BETWEEN',
					'compare_key' => '=',
				),
				array(
					'key'     => 'date2',
					'value'   => array(
						'2020-06-01',
						'2020-06-30'
					),
					'compare' => 'NOT BETWEEN',
					'compare_key' => '=',
				)
			)
		);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$this->assertCount( 1, $the_query->get_posts() );

	}

	/**
	 * get_meta_sql - WP_Query - recursive pattern
	 */
	public function test_get_meta_sql_post_recursive()
	{
		// create a post
		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'region', 'tokyo', false );
		add_post_meta( $post_id, 'language', 'japanese', false );
		add_post_meta( $post_id, 'hobby', 'walking', false );
		add_post_meta( $post_id, 'means', 'train', false );

		$args = array(
			'meta_query' => array(
				array(
					'key'     => 'region',
					'value'   => 'tokyo',
					'compare' => '=',
					'compare_key' => '=',
				),
				array(
					'key'     => 'language',
					'value'   => 'japanese',
					'compare' => '=',
					'compare_key' => '=',
				),
				array(
					'key'     => 'hobby',
					'value'   => array(
						'walking',
						'fishing'
					),
					'compare' => 'IN',
					'compare_key' => '=',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => 'means',
						'value'   => 'train',
						'compare' => '=',
						'compare_key' => '=',
					),
					array(
						'key'     => 'means',
						'value'   => 'walking',
						'compare' => '=',
						'compare_key' => '=',
					)
				)
			)
		);
		$the_query = new WP_Query( $args );
		$this->assertCount( 1, $the_query->get_posts() );

	}

	/**
	 * get_meta_sql - WP_User_Query
	 */
	public function test_get_meta_sql_metaUserJson()
	{
		// create a user
		$user_id = $this->factory->user->create();
		add_user_meta( $user_id, 'language', 'japanese' );
		add_user_meta( $user_id, 'hobby', 'walking' );

		$args = array(
			'meta_query' => array(
				array(
					'key'     => 'language',
					'value'   => 'japanese',
					'compare' => '=',
					'compare_key' => '='
				),
				array(
					'key'     => 'hobby',
					'value'   => 'walking',
					'compare' => '=',
					'compare_key' => '='
				)
			)
		);

		$the_query = new WP_User_Query( $args );
		$this->assertCount( 1, $the_query->get_results() );
	}

	/**
	 * get_meta_sql - WP_Comment_Query
	 */
	public function test_get_meta_sql_metaCommentJson()
	{
		// create a post
		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'language', 'japanese' );
		// create a comment
		$comment_id = $this->factory->comment->create( array( 'post_id' => $post_id ) );
		add_comment_meta( $comment_id, 'rating', '5' );


		$args = array(
			'meta_key' => 'rating',
			'meta_value' => '5'
		);

		$the_query = new WP_Comment_Query( $args );
		$this->assertCount( 1, $the_query->get_comments() );
	}

	/**
	 * @param $method
	 *
	 * @return ReflectionMethod
	 * @throws ReflectionException
	 */
	protected function get_access_protected( $method )
	{
		$reflection = new ReflectionClass( $this->metaQuery );
		$method = $reflection->getMethod( $method );
		$method->setAccessible( true );
		return $method;
	}

	/**
	 * @param $where
	 *
	 * @return string
	 */
	protected function remove_spaces( $where )
	{
		return preg_replace( '/\s(?=\s)/', '', preg_replace( '/[\n\r\t]/', ' ', trim( $where ) ) );
	}
}
