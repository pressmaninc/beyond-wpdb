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
	 * Wp_Query - orderby - notArray
	 */
	public function test_getMetaSql_orderBy_notArray() {
		$expected_array = array();
		$result_array   = array();

		$post_ids = $this->factory->post->create_many( 10 );
		foreach ( $post_ids as $post_id ) {
			$rand = rand( 45, 75 );
			array_push( $expected_array, $rand );
			add_post_meta( $post_id, 'region', 'tokyo' );
			add_post_meta( $post_id, 'deviation', $rand );
		}

		$args = array(
			'orderby'    => 'deviation',
			'order' => 'ASC',
			'meta_key' => 'region',
			'meta_value' => 'tokyo',
		);

		$the_query = new WP_Query( $args );
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				array_push( $result_array, get_post_meta( get_the_ID(), 'deviation' )[0] );
			}
		}

		sort( $expected_array );
		$this->assertEquals( $expected_array, $result_array );
	}

	/**
	 * Wp_Query - orderby - array
	 */
	public function test_getMetaSql_orderBy_array() {
		$expected_array = array();
		$result_array   = array();

		$height = 170;
		$weight = 65;

		$post_ids = $this->factory->post->create_many( 10 );
		foreach ( $post_ids as $k => $post_id ) {

			if ( $k % 3 === 0 ) {
				$height += 5;
			}
			$weight ++;

			if ( ! array_key_exists( $height, $expected_array ) ) {
				$expected_array[ $height ] = array();
			}

			array_push( $expected_array[ $height ], $weight );

			add_post_meta( $post_id, 'region', 'tokyo' );
			add_post_meta( $post_id, 'height', $height );
			add_post_meta( $post_id, 'weight', $weight );
		}

		$args = array(
			'orderby' => array(
				'height' => 'ASC',
				'weight' => 'DESC'
			),
			'meta_key'   => 'region',
			'meta_value' => 'tokyo',
		);

		$the_query = new WP_Query( $args );
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();

				$height = get_post_meta( get_the_ID(), 'height' )[0];
				$weight = get_post_meta( get_the_ID(), 'weight' )[0];

				if ( ! array_key_exists( $height, $result_array ) ) {
					$result_array[ $height ] = array();
				}

				array_push( $result_array[ $height ], $weight );
			}
		}

		$_expected_array = array();
		foreach ( $expected_array as $k => $val ) {
			rsort( $val );
			$_expected_array[ $k ] = $val;
		}

		$this->assertEquals( $_expected_array, $result_array );
	}

	/**
	 * Wp_Query - orderby - space delimitation
	 */
	public function test_getMetaSql_space_delimitation() {
		$expected_array = array();
		$result_array   = array();

		$height = 170;
		$weight = 65;

		// Multiple Posts
		$post_ids = $this->factory->post->create_many( 10 );
		foreach ( $post_ids as $k => $post_id ) {

			if ( $k % 3 === 0 ) {
				$height += 5;
			}
			$weight ++;

			if ( ! array_key_exists( $height, $expected_array ) ) {
				$expected_array[ $height ] = array();
			}

			array_push( $expected_array[ $height ], $weight );

			add_post_meta( $post_id, 'region', 'tokyo' );
			add_post_meta( $post_id, 'height', $height );
			add_post_meta( $post_id, 'weight', $weight );
		}

		$args = array(
			'orderby' => 'height weight',
			'order'   => 'ASC',
			'meta_key'   => 'region',
			'meta_value' => 'tokyo',
		);

		$the_query = new WP_Query( $args );
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();

				$height = get_post_meta( get_the_ID(), 'height' )[0];
				$weight = get_post_meta( get_the_ID(), 'weight' )[0];

				if ( ! array_key_exists( $height, $result_array ) ) {
					$result_array[ $height ] = array();
				}

				array_push( $result_array[ $height ], $weight );
			}
		}

		$this->assertEquals( $expected_array, $result_array );
	}
}
