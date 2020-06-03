<?php
/**
 * Class BeyondWpdbTest
 *
 * @package Beyond_Wpdb
 */

// プラグインの読み込み
require_once( plugin_dir_path( __FILE__ ) . '../beyond-wpdb.php' );

class BeyondWpdbTest extends WP_UnitTestCase {

	/**
	 * queriesチェック - 成功
	 */
	public function test_check_success() {
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
			)
		);

		// protectedなのでアクセス許可
		$metaQuery = new Beyond_Wpdb_Meta_Query();
		$reflection = new ReflectionClass($metaQuery);
		$method = $reflection->getMethod('check');
		$method->setAccessible(true);
		$this->assertTrue( $method->invoke($metaQuery, $queries) );
	}

	/**
	 * queriesチェック - 失敗
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
			)
		);

		// protectedなのでアクセス許可
		$metaQuery = new Beyond_Wpdb_Meta_Query();
		$reflection = new ReflectionClass($metaQuery);
		$method = $reflection->getMethod('check');
		$method->setAccessible(true);
		$this->assertFalse( $method->invoke($metaQuery, $queries) );
	}

	/**
	 * queries再帰的チェック - 成功
	 */
	public function test_check_recursive_success() {
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

		// protectedなのでアクセス許可
		$metaQuery = new Beyond_Wpdb_Meta_Query();
		$reflection = new ReflectionClass($metaQuery);
		$method = $reflection->getMethod('check');
		$method->setAccessible(true);
		$this->assertTrue( $method->invoke($metaQuery, $queries) );
	}

	/**
	 * queries再帰的チェック - 失敗
	 */
	public function test_check_recursive_failure() {
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

		// protectedなのでアクセス許可
		$metaQuery = new Beyond_Wpdb_Meta_Query();
		$reflection = new ReflectionClass($metaQuery);
		$method = $reflection->getMethod('check');
		$method->setAccessible(true);
		$this->assertFalse( $method->invoke($metaQuery, $queries) );
	}
}
