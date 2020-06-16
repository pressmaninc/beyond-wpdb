<?php
/**
 * Class Beyond_Wpdb_Performance_Test
 *
 * @package Beyond_Wpdb
 */

class Beyond_Wpdb_Performance_Test extends WP_UnitTestCase {

	public function setUp()
	{
		parent::setUp();

		$register_hook = new Beyond_Wpdb_Register_Hook();
		$register_hook::activation();

		$post_ids = $this->factory->post->create_many( 100 );
		foreach ( $post_ids as $post_id ) {
			foreach ( range( 1, 50 ) as $val ) {
				add_post_meta( $post_id, "key_$val", "val_$val" );
			}
		}
	}

	public function tearDown()
	{
		parent::tearDown();

		$register_hook = new Beyond_Wpdb_Register_Hook();
		$register_hook::deactivation();
	}


	/**
	 * test performance not equal
	 */
	public function test_performance_equal() {

		$meta_query = array();

		foreach ( range( 1, 8 ) as $val ) {
			$key_value = array(
				'key' => "key_$val",
				'value' => "val_$val",
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
		array_push( $meta_query, array(
			'key' => 'key_10'
		) );
		$args = array(
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
			array_push( $in_value, "val_$val" );
		}

		// create a $meta_query
		$meta_query = array();
		foreach ( range( 1, 8 ) as $val ) {
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
		array_push( $meta_query, array(
			'key' => 'key_10'
		) );
		$args = array(
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
		foreach ( range( 1, 8 ) as $val ) {
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
		array_push( $meta_query, array(
			'key' => 'key_10'
		) );
		$args = array(
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
				'value' => "val_$val",
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
		array_push( $meta_query, array(
			'key' => 'key_10'
		) );
		$args = array(
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
				'value' => "val_val",
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
		array_push( $meta_query, array(
			'key' => 'key_10'
		) );
		$args = array(
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
}
