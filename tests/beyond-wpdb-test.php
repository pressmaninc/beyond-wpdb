<?php
/**
 * Class BeyondWpdbTest
 *
 * @package Beyond_Wpdb
 */

// プラグインの読み込み
require_once( plugin_dir_path( __FILE__ ) . '../beyond-wpdb.php' );

class BeyondWpdbTest extends WP_UnitTestCase {
	protected $metaQuery = '';

	public function setUp()
	{
		$this->metaQuery = new Beyond_Wpdb_Meta_Query();
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

		$method = $this->get_access_protected('check');
		$this->assertTrue( $method->invoke($this->metaQuery, $queries) );
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

		$method = $this->get_access_protected('check');
		$this->assertFalse( $method->invoke($this->metaQuery, $queries) );
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

		$method = $this->get_access_protected('check');
		$this->assertTrue( $method->invoke($this->metaQuery, $queries) );
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

		$method = $this->get_access_protected('check');
		$this->assertFalse( $method->invoke($this->metaQuery, $queries) );
	}

	/**
	 * 独自テーブル - 成功
	 */
	public function test_getMetaTable_success()
	{
		global $wpdb;
		$metaKey = ['post', 'user', 'comment'];

		$method = $this->get_access_protected('_get_meta_table');
		foreach ($metaKey as $type) {
			$expected_table_name = $wpdb->prefix . $type. 'meta_json';
			$this->assertEquals($expected_table_name, $method->invoke($this->metaQuery, $type) );
		}
	}

	/**
	 * 独自テーブル - 失敗
	 */
	public function test_getMetaTable_failure()
	{
		global $wpdb;
		$metaKey = ['postA', 'userA', 'commentA'];

		$method = $this->get_access_protected('_get_meta_table');
		foreach ($metaKey as $type) {
			$expected_table_name = $wpdb->prefix . $type. 'meta_json';
			$this->assertNotEquals($expected_table_name, $method->invoke($this->metaQuery, $type) );
		}
	}

	/**
	 * sqlチェック - join
	 */
	public function test_getSql_check_join()
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
		$method = $this->get_access_protected('get_sql');
		$sql = $method->invoke($this->metaQuery, $type, $queries, $primary_table, $primary_id_column, $context);
		$this->assertEquals($expected_join, trim($sql['join']) );
	}

	/**
	 * sqlチェック - where - compareが=とEXISTS
	 */
	public function test_getSql_check_where_equal_or_exists()
	{
		global $wpdb;

		// sql[where]チェック
		$expected_where = $this->remove_spaces(
			"AND ( ( JSON_EXTRACT(json, '$.region') != '' AND JSON_EXTRACT(json, '$.region') = 'tokyo' ) 
			     AND ( JSON_EXTRACT(json, '$.language') != '' AND JSON_EXTRACT(json, '$.language') = 'japanese' ))"
		);
		// get_sqlの引数
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
		// get_sql実行
		$method = $this->get_access_protected('get_sql');
		$sql = $method->invoke($this->metaQuery, $type, $queries, $primary_table, $primary_id_column, $context);
		$where = $this->remove_spaces($sql['where']);
		$this->assertEquals($expected_where,  $where);
	}

	/**
	 * sqlチェック - where - compareがLIKEとNOT LIKE
	 */
	public function test_getSql_check_where_like_or_notLike()
	{
		global $wpdb;

		// sql[where]チェック
		$metaValue1 = '%' . $wpdb->esc_like( 'tokyo' ) . '%';
		$metaValue1 = $wpdb->prepare('%s', $metaValue1);
		$metaValue2 = '%' . $wpdb->esc_like( 'japanese' ) . '%';
		$metaValue2 = $wpdb->prepare('%s', $metaValue2);
		$expected_where = $this->remove_spaces(
			"AND ( ( JSON_EXTRACT(json, '$.region') != '' AND JSON_EXTRACT(json, '$.region') LIKE $metaValue1 ) 
			     AND ( JSON_EXTRACT(json, '$.language') != '' AND JSON_EXTRACT(json, '$.language') NOT LIKE $metaValue2 ))"
		);
		// get_sqlの引数
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
		// get_sql実行
		$method = $this->get_access_protected('get_sql');
		$sql = $method->invoke($this->metaQuery, $type, $queries, $primary_table, $primary_id_column, $context);
		$where = $this->remove_spaces($sql['where']);
		$this->assertEquals($expected_where,  $where);
	}

	/**
	 * sqlチェック - where - compareがINとNOT IN
	 */
	public function test_getSql_check_where_in_or_notIn()
	{
		global $wpdb;

		// sql[where]チェック
		$expected_where = $this->remove_spaces(
			"AND ( ( JSON_EXTRACT(json, '$.regions') != '' AND JSON_EXTRACT(json, '$.regions') IN ('tokyo','osaka','kyoto') ) 
			     AND ( JSON_EXTRACT(json, '$.languages') != '' AND JSON_EXTRACT(json, '$.languages') NOT IN ('japanese','english','chinese') ))"
		);
		// get_sqlの引数
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
		// get_sql実行
		$method = $this->get_access_protected('get_sql');
		$sql = $method->invoke($this->metaQuery, $type, $queries, $primary_table, $primary_id_column, $context);
		$where = $this->remove_spaces($sql['where']);
		$this->assertEquals($expected_where,  $where);
	}

	/**
	 * sqlチェック - where - compareがBETWEENとNOT BETWEEN
	 */
	public function test_getSql_check_where_between_or_notBetween()
	{
		global $wpdb;

		// sql[where]チェック
		$expected_where = $this->remove_spaces(
			"AND ( ( JSON_EXTRACT(json, '$.date1') != '' AND '2020-05-01' <= JSON_EXTRACT(json, '$.date1') and JSON_EXTRACT(json, '$.date1') <= '2020-06-01' ) 
			     AND ( JSON_EXTRACT(json, '$.date2') != '' AND '2020-05-01' > JSON_EXTRACT(json, '$.date2') and JSON_EXTRACT(json, '$.date2') > '2020-06-01' ))"
		);
		// get_sqlの引数
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
		// get_sql実行
		$method = $this->get_access_protected('get_sql');
		$sql = $method->invoke($this->metaQuery, $type, $queries, $primary_table, $primary_id_column, $context);
		$where = $this->remove_spaces($sql['where']);
		$this->assertEquals($expected_where,  $where);
	}

	/**
	 * sqlチェック - where - 再帰パターン
	 */
	public function test_getSql_check_where_recursive()
	{
		global $wpdb;

		// sql[where]チェック
		$expected_where = $this->remove_spaces(
			"AND ( ( JSON_EXTRACT(json, '$.region') != '' AND JSON_EXTRACT(json, '$.region') = 'tokyo' ) 
			        AND ( JSON_EXTRACT(json, '$.hobbies') != '' AND JSON_EXTRACT(json, '$.hobbies') IN ('walking','fishing') )
			        AND ( ( JSON_EXTRACT(json, '$.language') != '' AND JSON_EXTRACT(json, '$.language') = 'japanese' ) 
			            OR ( JSON_EXTRACT(json, '$.language') != '' AND JSON_EXTRACT(json, '$.language') = 'english' ) 
			     ))"
		);
		// get_sqlの引数
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
		// get_sql実行
		$method = $this->get_access_protected('get_sql');
		$sql = $method->invoke($this->metaQuery, $type, $queries, $primary_table, $primary_id_column, $context);
		$where = $this->remove_spaces($sql['where']);
		$this->assertEquals($expected_where,  $where);
	}

	/**
	 * get_meta_sqlチェック
	 */
	public function test_getMetaSql()
	{
		$args = array(
			'meta_query' => array(
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
				)
			)
		);

		$the_query = new WP_Query($args);
		$this->assertNotEmpty($the_query->request);
	}

	/**
	 * get_meta_sqlチェック - 正しく抽出できているか
	 */
	public function test_getMetaSql_value()
	{
		// 投稿オブジェクトを作成
		$my_post = array(
			'post_title'    => 'My post',
			'post_content'  => 'This is my post.',
			'post_status'   => 'publish',
			'post_author'   => 1
		);

		// 投稿をデータベースへ追加
		wp_insert_post( $my_post );
		$the_query = new WP_Query();
		echo $the_query->post_count;

	}

	protected function get_access_protected($method)
	{
		// protectedなのでアクセス許可
		$reflection = new ReflectionClass($this->metaQuery);
		$method = $reflection->getMethod($method);
		$method->setAccessible(true);
		return $method;
	}

	//左右の空白を取り除いて、改行とタブを取り除いて、複数スペースを１つのスペースに変換
	protected function remove_spaces($where)
	{
		return preg_replace('/\s(?=\s)/', '', preg_replace('/(\t|\r\n|\r|\n)/s', '', trim($where)));
	}
}
