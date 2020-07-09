<?php
/**
 * Class Beyond_Wpdb_Performance_Test
 *
 * @package Beyond_Wpdb
 */

require_once( plugin_dir_path( __FILE__ ) . 'beyond-wpdb-test.php' );

class Beyond_Wpdb_Performance_Test extends Beyond_Wpdb_Test {

	public function setUp()
	{

		parent::setUp();

		$post_ids = $this->factory->post->create_many( 100 );
		foreach ( $post_ids as $post_id ) {
			foreach ( range( 1, 50 ) as $val ) {
				add_post_meta( $post_id, "key_$val", "$val" );
			}
		}
	}

	/**
	 * test performance not equal
	 */
	public function test_performance_equal() {

		$meta_query = array();

		foreach ( range( 1, 9 ) as $val ) {
			$key_value = array(
				'key' => "key_$val",
				'value' => "$val",
				'compare' => '='
			);
			array_push( $meta_query, $key_value );
		}

		$args = array(
			'posts_per_page' => '100',
 			'meta_query' => $meta_query
		);

		// postmeta_json
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$postmeta_json_time = round( ($end - $start), 2 );

		echo "postmeta_json:Equal:$postmeta_json_time" . PHP_EOL;
		$this->assertCount( 100, $the_query->get_posts() );

		// postmeta
		$args = array(
			'suppress_filters' => True,
			'posts_per_page' => '100',
			'meta_query' => $meta_query
		);
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$postmeta_time = round( ($end - $start), 2 );

		echo "postmeta:Equal:$postmeta_time" . PHP_EOL;
		$this->assertCount( 100, $the_query->get_posts() );

		$this->assertGreaterThanOrEqual( $postmeta_json_time, $postmeta_time );
	}

	/**
	 * test performance in
	 */
	public function test_performance_in() {

		$in_value = array();
		foreach ( range( 1, 50 ) as $val ) {
			array_push( $in_value, "$val" );
		}

		// create a $meta_query
		$meta_query = array();
		foreach ( range( 1, 9 ) as $val ) {
			$key_value = array(
				'key' => "key_$val",
				'value' => $in_value,
				'compare' => 'IN'
			);
			array_push( $meta_query, $key_value );
		}

		$args = array(
			'posts_per_page' => '100',
			'meta_query' => $meta_query
		);

		// postmeta_json
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$result = $the_query->get_posts();
		$end = microtime(true);
		$postmeta_json_time = round( ($end - $start), 2 );

		echo "postmeta_json:IN:$postmeta_json_time" . PHP_EOL;
		$this->assertCount( 100, $result );

		// postmeta
		$args = array(
			'suppress_filters' => True,
			'posts_per_page' => '100',
			'meta_query' => $meta_query
		);

		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$postmeta_time = round( ($end - $start), 2 );

		echo "postmeta:IN:$postmeta_time" . PHP_EOL;
		$this->assertCount( 100, $the_query->get_posts() );

		$this->assertGreaterThanOrEqual( $postmeta_json_time, $postmeta_time );
	}

	/**
	 * test performance not in
	 */
	public function test_performance_not_in() {

		$in_value = array();
		foreach ( range( 51, 100 ) as $val ) {
			array_push( $in_value, $val );
		}

		$meta_query = array();
		foreach ( range( 1, 9 ) as $val ) {
			$key_value = array(
				'key' => "key_$val",
				'value' => $in_value,
				'compare' => 'NOT IN'
			);
			array_push( $meta_query, $key_value );
		}

		$args = array(
			'posts_per_page' => '100',
			'meta_query' => $meta_query
		);

		// postmeta_json
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$postmeta_json_time = round( ($end - $start), 2 );

		echo "postmeta_json:NOT IN:$postmeta_json_time" . PHP_EOL;
		$this->assertCount( 100, $the_query->get_posts() );

		// postmeta
		$args = array(
			'suppress_filters' => True,
			'posts_per_page' => '100',
			'meta_query' => $meta_query
		);

		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$postmeta_time = round( ($end - $start), 2 );

		echo "postmeta:NOT IN:$postmeta_time" . PHP_EOL;
		$this->assertCount( 100, $the_query->get_posts() );

		$this->assertGreaterThanOrEqual( $postmeta_json_time, $postmeta_time );
	}

	/**
	 * test performance like
	 */
	public function test_performance_like() {

		$meta_query = array();
		foreach ( range( 1, 8 ) as $val ) {
			$key_value = array(
				'key' => "key_$val",
				'value' => "$val",
				'compare' => 'LIKE'
			);
			array_push( $meta_query, $key_value );
		}

		$args = array(
			'posts_per_page' => '100',
			'meta_query' => $meta_query
		);

		// postmeta_json
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$postmeta_json_time = round( ($end - $start), 2 );

		echo "postmeta_json:LIKE:$postmeta_json_time" . PHP_EOL;
		$this->assertCount( 100, $the_query->get_posts() );

		// postmeta
		$args = array(
			'suppress_filters' => True,
			'posts_per_page' => '100',
			'meta_query' => $meta_query
		);

		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$postmeta_time = round( ($end - $start), 2 );

		echo "postmeta:LIKE:$postmeta_time" . PHP_EOL;
		$this->assertCount( 100, $the_query->get_posts() );

		$this->assertGreaterThanOrEqual( $postmeta_json_time, $postmeta_time );
	}

	/**
	 * test performance not like
	 */
	public function test_performance_not_like() {

		$meta_query = array();
		foreach ( range( 1, 8 ) as $val ) {
			$key_value = array(
				'key' => "key_$val",
				'value' => "val",
				'compare' => 'NOT LIKE'
			);
			array_push( $meta_query, $key_value );
		}

		$args = array(
			'posts_per_page' => '100',
			'meta_query' => $meta_query
		);

		// postmeta_json
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$postmeta_json_time = round( ($end - $start), 2 );

		echo "postmeta_json:NOT LIKE:$postmeta_json_time" . PHP_EOL;
		$this->assertCount( 100, $the_query->get_posts() );

		// postmeta
		$args = array(
			'suppress_filters' => True,
			'posts_per_page' => '100',
			'meta_query' => $meta_query
		);

		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$postmeta_time = round( ($end - $start), 2 );

		echo "postmeta:NOT LIKE:$postmeta_time" . PHP_EOL;
		$this->assertCount( 100, $the_query->get_posts() );

		$this->assertGreaterThanOrEqual( $postmeta_json_time, $postmeta_time );
	}

	/**
	 * test performance BETWEEN
	 */
	public function test_performance_between() {

		$meta_query = array();
		foreach ( range( 1, 8 ) as $val ) {
			$key_value = array(
				'key' => "key_$val",
				'value' => array(
					'1', '50'
				),
				'compare' => 'BETWEEN',
				'type' => 'NUMERIC'
			);
			array_push( $meta_query, $key_value );
		}

		$args = array(
			'posts_per_page' => '100',
			'meta_query' => $meta_query
		);

		// postmeta_json
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$postmeta_json_time = round( ($end - $start), 2 );

		echo "postmeta_json:BETWEEN:$postmeta_json_time" . PHP_EOL;
		$this->assertCount( 100, $the_query->get_posts() );

		// postmeta
		$args = array(
			'suppress_filters' => True,
			'posts_per_page' => '100',
			'meta_query' => $meta_query
		);

		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$postmeta_time = round( ($end - $start), 2 );

		echo "postmeta:NOT BETWEEN:$postmeta_time" . PHP_EOL;
		$this->assertCount( 100, $the_query->get_posts() );

		$this->assertGreaterThanOrEqual( $postmeta_json_time, $postmeta_time );
	}

	/**
	 * test performance NOT BETWEEN
	 */
	public function test_performance_not_between() {

		$meta_query = array();
		foreach ( range( 1, 8 ) as $val ) {
			$key_value = array(
				'key' => "key_$val",
				'value' => array(
					'51', '100'
				),
				'compare' => 'NOT BETWEEN',
				'type' => 'NUMERIC'
			);
			array_push( $meta_query, $key_value );
		}

		$args = array(
			'posts_per_page' => '100',
			'meta_query' => $meta_query
		);

		// postmeta_json
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$postmeta_json_time = round( ($end - $start), 2 );

		echo "postmeta_json:BETWEEN:$postmeta_json_time" . PHP_EOL;
		$this->assertCount( 100, $the_query->get_posts() );

		// postmeta
		$args = array(
			'suppress_filters' => True,
			'posts_per_page' => '100',
			'meta_query' => $meta_query
		);

		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$postmeta_time = round( ($end - $start), 2 );

		echo "postmeta:NOT BETWEEN:$postmeta_time" . PHP_EOL;
		$this->assertCount( 100, $the_query->get_posts() );

		$this->assertGreaterThanOrEqual( $postmeta_json_time, $postmeta_time );
	}

	// ---------------↑ without virtual columns ↑---------------
	// ---------------↓ with virtual columns    ↓---------------

	/**
	 * test performance not equal for virtual column
	 */
	public function test_performance_equal_for_virtual_column() {

		$categories = $this->init_for_virtual_column_test( 'post' );
		$category = array_rand( $categories );
		$args = array(
			'posts_per_page' => '2000',
			'meta_query' => array(
				array(
					'key' => 'category',
					'value' => $categories[$category]
				)
			)
		);

		// without virtual column
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$without_virtual_column_time = round( ($end - $start), 2 );

		// with virtual column
		$this->create_virtual_columns( 'post' );
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		// print_r( $the_query->request );
		$the_query->get_posts();
		$end = microtime(true);
		$with_virtual_column_time = round( ($end - $start), 2 );

		echo "postmeta_json(without virtual column):Equal:$without_virtual_column_time" . PHP_EOL;
		echo "postmeta_json(with virtual column):Equal:$with_virtual_column_time" . PHP_EOL;

		$this->assertGreaterThanOrEqual( $with_virtual_column_time, $without_virtual_column_time );
	}

	/**
	 * test performance in for virtual column
	 */
	public function test_performance_in_for_virtual_column() {

		$categories = $this->init_for_virtual_column_test( 'post' );
		$args = array(
			'posts_per_page' => '2000',
			'meta_query' => array(
				array(
					'key' => 'category',
					'value' => $categories,
					'compare' => 'IN'
				)
			)
		);

		// without virtual column
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$without_virtual_column_time = round( ($end - $start), 2 );

		// with virtual column
		$this->create_virtual_columns( 'post' );
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$with_virtual_column_time = round( ($end - $start), 2 );

		echo "postmeta_json(without virtual column):IN:$without_virtual_column_time" . PHP_EOL;
		echo "postmeta_json(with virtual column):IN:$with_virtual_column_time" . PHP_EOL;

		$this->assertGreaterThanOrEqual( $with_virtual_column_time, $without_virtual_column_time );
	}

	/**
	 * test performance BETWEEN for virtual column
	 */
	public function test_performance_between_for_virtual_column() {
		$this->init_for_virtual_column_test( 'post' );
		$args = array(
			'posts_per_page' => '2000',
			'meta_query' => array(
				array(
					'key' => 'category',
					'value' => array( 0, 9999 ),
					'compare' => 'BETWEEN'
				)
			)
		);

		// without virtual column
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$without_virtual_column_time = round( ($end - $start), 2 );

		// with virtual column
		$this->create_virtual_columns( 'post' );
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$with_virtual_column_time = round( ($end - $start), 2 );

		echo "postmeta_json(without virtual column):BETWEEN:$without_virtual_column_time" . PHP_EOL;
		echo "postmeta_json(with virtual column):BETWEEN:$with_virtual_column_time" . PHP_EOL;

		$this->assertGreaterThanOrEqual( $with_virtual_column_time, $without_virtual_column_time );
	}

	/**
	 * test performance NOT BETWEEN for virtual column
	 */
	public function test_performance_not_between_for_vitual_column() {
		$this->init_for_virtual_column_test( 'post' );
		$args = array(
			'posts_per_page' => '2000',
			'meta_query' => array(
				array(
					'key' => 'category',
					'value' => array( 10001, 20000 ),
					'compare' => 'BETWEEN'
				)
			)
		);

		// without virtual column
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$without_virtual_column_time = round( ($end - $start), 2 );

		// with virtual column
		$this->create_virtual_columns( 'post' );
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$with_virtual_column_time = round( ($end - $start), 2 );

		echo "postmeta_json(without virtual column):NOT BETWEEN:$without_virtual_column_time" . PHP_EOL;
		echo "postmeta_json(with virtual column):NOT BETWEEN:$with_virtual_column_time" . PHP_EOL;

		$this->assertGreaterThanOrEqual( $with_virtual_column_time, $without_virtual_column_time );
	}

	protected function init_for_virtual_column_test( $type )
	{
		global $wpdb;
		$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $type ) ) );
		$wpdb->query( "delete from {$table_name}" );

		$categories = array();

		$post_ids = $this->factory->post->create_many( 2000 );
		foreach ( $post_ids as $post_id ) {
			$category = rand( 0, 9999 );
			add_post_meta( $post_id, "category", $category );
			if ( $post_id % 7 === 0 ) {
				array_push( $categories, $category );
			}
		}

		return $categories;
	}

	protected function create_virtual_columns( $type )
	{
		// create virtual columns
		$beyond_wpdb_settings_page = new Beyond_Wpdb_Settings_page();
		$input = array();
		$input[$type . 'meta_json'] = 'category';
		$beyond_wpdb_settings_page->create_virtual_column_and_index( $input );
	}

}
