<?php
/**
 * Class BeyondWpdbTest
 *
 * @package Beyond_Wpdb
 */

// プラグインの読み込み
require_once( plugin_dir_path( __FILE__ ) . '../beyond-wpdb.php' );

class BeyondWpdbTest extends WP_UnitTestCase {

	public function test_sample() {
		$args = array(
			'meta_query' => array(
				array(
					'key'     => 'region',
					'value'   => '東京都',
					'compare' => '=',
					'compare_key' => '=',
				),
				array(
					'key'     => 'hobbies',
					'value'   => array(
						'散歩',
						'サイクリング',
						'山登り'
					),
					'compare' => 'IN',
					'compare_key' => 'EXISTS',
				),
				array(
					'key'     => 'imageUrl',
					'value'   => 'http://localhost/images/a.jpg',
					'compare' => 'EXISTS',
					'compare_key' => 'EXISTS',
				)
			)
		);
		$the_query = new WP_Query($args);
		$the_query->have_posts();
		$this->assertTrue( true );
	}

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

	/**
	 * 独自テーブル - 成功
	 */
	public function test_getMetaTable_success()
	{
		global $wpdb;
		$metaKey = ['post', 'user', 'comment'];

		// protectedなのでアクセス許可
		$metaQuery = new Beyond_Wpdb_Meta_Query();
		$reflection = new ReflectionClass($metaQuery);
		$method = $reflection->getMethod('_get_meta_table');
		$method->setAccessible(true);

		foreach ($metaKey as $type) {
			$expected_table_name = $wpdb->prefix . $type. 'meta_json';
			$method->invoke($metaQuery, $type);
			$this->assertEquals($expected_table_name, $method->invoke($metaQuery, $type) );
		}
	}

	/**
	 * 独自テーブル - 失敗
	 */
	public function test_getMetaTable_failure()
	{
		global $wpdb;
		$metaKey = ['postA', 'userA', 'commentA'];

		// protectedなのでアクセス許可
		$metaQuery = new Beyond_Wpdb_Meta_Query();
		$reflection = new ReflectionClass($metaQuery);
		$method = $reflection->getMethod('_get_meta_table');
		$method->setAccessible(true);

		foreach ($metaKey as $type) {
			$expected_table_name = $wpdb->prefix . $type. 'meta_json';
			$method->invoke($metaQuery, $type);
			$this->assertNotEquals($expected_table_name, $method->invoke($metaQuery, $type) );
		}
	}
}
