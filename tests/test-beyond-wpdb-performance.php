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
	}

	public function tearDown()
	{
		parent::tearDown();

		$register_hook = new Beyond_Wpdb_Register_Hook();
		$register_hook::deactivation();
	}


	/**
	 *
	 */
	public function test_performance() {

		$post_ids = $this->factory->post->create_many( 100 );
		foreach ( $post_ids as $post_id ) {
			foreach ( range( 1, 100 ) as $val ) {
				add_post_meta( $post_id, "key_$val", "val_$val" );
			}
		}

		$meta_query = array();

		foreach ( range( 1, 50 ) as $val ) {
			$key_value = array(
				'key' => "key_$val",
				'value' => "val_$val"
			);
			array_push( $meta_query, $key_value );
		}

		$args = array(
			'posts_per_page' => '50',
 			'meta_query' => $meta_query
		);

		// postmeta_json_table
		$the_query = new WP_Query( $args );
		$start = microtime(true);
		$the_query->get_posts();
		$end = microtime(true);
		echo "#######" . ($end - $start) . "######" .PHP_EOL;

		$args = array(
			'supress_filters' => true,
			'meta_query' => $meta_query
		);

		// postmeta_table
		$the_query = new WP_Query( $args );
		$start = microtime(true);
		$the_query->get_posts();
		$end = microtime(true);
		echo "#######" . ($end - $start) . "######" .PHP_EOL;
	}
}
