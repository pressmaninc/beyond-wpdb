<?php

require_once( plugin_dir_path( __FILE__ ) . 'beyond-wpdb-ajax-test.php' );

class Beyond_Wpdb_Performance_With_Vc_Test extends Beyond_Wpdb_Ajax_Test {

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
		$this->create_virtual_columns( 'category' );
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$with_virtual_column_time = round( ($end - $start), 2 );

		echo "postmeta_beyond(without virtual column):Equal:$without_virtual_column_time" . PHP_EOL;
		echo "postmeta_beyond(with virtual column):Equal:$with_virtual_column_time" . PHP_EOL;

		$this->assertGreaterThanOrEqual( $with_virtual_column_time, $without_virtual_column_time );
	}

	/**
	 * test performance in for virtual column
	 */
	public function test_performance_in_for_virtual_column() {

		$this->delete();
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
		$this->create_virtual_columns( 'category' );
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$with_virtual_column_time = round( ($end - $start), 2 );

		echo "postmeta_beyond(without virtual column):IN:$without_virtual_column_time" . PHP_EOL;
		echo "postmeta_beyond(with virtual column):IN:$with_virtual_column_time" . PHP_EOL;

		$this->assertGreaterThanOrEqual( $with_virtual_column_time, $without_virtual_column_time );
	}

	/**
	 * test performance BETWEEN for virtual column
	 */
	public function test_performance_between_for_virtual_column() {

		$this->delete();
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
		$this->create_virtual_columns( 'category' );
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$with_virtual_column_time = round( ($end - $start), 2 );

		echo "postmeta_beyond(without virtual column):BETWEEN:$without_virtual_column_time" . PHP_EOL;
		echo "postmeta_beyond(with virtual column):BETWEEN:$with_virtual_column_time" . PHP_EOL;

		$this->assertGreaterThanOrEqual( $with_virtual_column_time, $without_virtual_column_time );
	}

	/**
	 * test performance NOT BETWEEN for virtual column
	 */
	public function test_performance_not_between_for_vitual_column() {

		$this->delete();
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
		$this->create_virtual_columns( 'category' );
		$start = microtime(true);
		$the_query = new WP_Query( $args );
		$the_query->get_posts();
		$end = microtime(true);
		$with_virtual_column_time = round( ($end - $start), 2 );

		echo "postmeta_beyond(without virtual column):NOT BETWEEN:$without_virtual_column_time" . PHP_EOL;
		echo "postmeta_beyond(with virtual column):NOT BETWEEN:$with_virtual_column_time" . PHP_EOL;

		$this->assertGreaterThanOrEqual( $with_virtual_column_time, $without_virtual_column_time );
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

	/**
	 * @param $type
	 *
	 * @return array
	 */
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
}
