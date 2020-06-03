<?php
/**
 * Class BeyondWpdbTest
 *
 * @package Beyond_Wpdb
 */
require_once( plugin_dir_path( __FILE__ ) . '../beyond-wpdb.php' );

class BeyondWpdbTest extends WP_UnitTestCase {

	/**
	 * queriesチェック - 成功
	 */
	public function check_success() {
		$queries = array(
			array(
				'key'     => 'key1',
				'value'   => 'value1',
				'compare' => '=',
			),
			array(
				'key'     => 'key2',
				'value'   => 'value2',
				'compare' => '=',
			)
		);
		// mergeされてなくてBeyond_Wpdb_Meta_Queryが存在しないのでコメントアウト
		// $metaQuery = new Beyond_Wpdb_Meta_Query();
		// protectedなのでどうするか考え中
		// $this->assertTrue( $metaQuery->check($queries) );
	}

	/**
	 * queries再帰的チェック - 成功
	 */
	public function check_recursive_success() {
		$queries = array(
			array(
				'key'     => 'key1',
				'value'   => 'value1',
				'compare' => '=',
			),
			array(
				'key'     => 'key2',
				'value'   => 'value2',
				'compare' => '=',
			),
			array(
				array(
					'key'     => 'key3',
					'value'   => 'value3',
					'compare' => '=',
				),
				array(
					'key'     => 'key4',
					'value'   => 'value4',
					'compare' => '=',
				)
			)
		);
		// mergeされてなくてBeyond_Wpdb_Meta_Queryが存在しないのでコメントアウト
		// $metaQuery = new Beyond_Wpdb_Meta_Query();
		// protectedなのでどうするか考え中
		// $this->assertTrue( $metaQuery->check($queries) );
	}

	/**
	 * queriesチェック - 失敗
	 */
	public function check_failure() {
		$queries = array(
			array(
				'key'     => 'key1',
				'value'   => 'value1',
				'compare' => '=',
			),
			array(
				'key'     => 'key2',
				'value'   => 'value2',
				'compare' => 'IN',
			)
		);
		// mergeされてなくてBeyond_Wpdb_Meta_Queryが存在しないのでコメントアウト
		// $metaQuery = new Beyond_Wpdb_Meta_Query();
		// protectedなのでどうするか考え中
		// $this->assertFalse( $metaQuery->check($queries) );
	}

	/**
	 * queries再帰的チェック - 失敗
	 */
	public function check_recursive_failure() {
		$queries = array(
			array(
				'key'     => 'key1',
				'value'   => 'value1',
				'compare' => '=',
			),
			array(
				'key'     => 'key2',
				'value'   => 'value2',
				'compare' => '=',
			),
			array(
				array(
					'key'     => 'key3',
					'value'   => 'value3',
					'compare' => '=',
				),
				array(
					'key'     => 'key4',
					'value'   => 'value4',
					'compare' => '=',
				)
			)
		);
		// mergeされてなくてBeyond_Wpdb_Meta_Queryが存在しないのでコメントアウト
		// $metaQuery = new Beyond_Wpdb_Meta_Query();
		// protectedなのでどうするか考え中
		// $this->assertFalse( $metaQuery->check($queries) );
	}
}
