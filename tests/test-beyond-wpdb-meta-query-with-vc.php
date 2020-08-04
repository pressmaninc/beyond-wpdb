<?php

require_once( plugin_dir_path( __FILE__ ) . 'beyond-wpdb-ajax-test.php' );

class Beyond_Wpdb_Meta_Query_With_Vc_Test extends Beyond_Wpdb_Ajax_Test {

	/**
	 * test get_meta_sql for virtual columns with equals - Wp_Query
	 */
	public function test_get_meta_sql_for_virtual_columns_with_equals()
	{
		// create a post
		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'country', 'japan', false );
		add_post_meta( $post_id, 'region', 'tokyo', false );

		$this->create_virtual_columns( 'country' . PHP_EOL . 'region' );

		$args = array(
			'meta_query' => array(
				array(
					'key' => 'country',
					'value' => 'japan',
					'compare' => '='
				),
				array(
					'key' => 'region',
					'value' => 'tokyo',
					'compare' => '='
				),
			)
		);

		$the_query = new WP_Query( $args );
		$this->assertCount( 1, $the_query->get_posts() );
	}

	/**
	 * test get_meta_sql for virtual columns with IN OR NOT IN - Wp_Query
	 */
	public function test_get_meta_sql_for_virtual_columns_with_in_or_not_in()
	{
		$this->delete();

		// create a post
		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'country', 'japan', false );
		add_post_meta( $post_id, 'region', 'tokyo', false );

		$this->create_virtual_columns( 'country' . PHP_EOL . 'region' );

		$args = array(
			'meta_query' => array(
				array(
					'key' => 'country',
					'value' => array(
						'japan',
						'USA',
						'china'
					),
					'compare' => 'IN'
				),
				array(
					'key' => 'region',
					'value' => array(
						'osaka',
						'kyoto',
						'hokkaido'
					),
					'compare' => 'NOT IN'
				),
			)
		);

		$the_query = new WP_Query( $args );
		$this->assertCount( 1, $the_query->get_posts() );

	}

	/**
	 * test get_meta_sql for virtual columns with BETWEEN OR NOT BETWEEN - Wp_Query
	 */
	public function test_get_meta_sql_for_virtual_columns_with_between_or_not_between()
	{
		$this->delete();

		// create a post
		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'height', 180, false );
		add_post_meta( $post_id, 'weight', 86, false );

		$this->create_virtual_columns( 'height' . PHP_EOL . 'weight' );

		$args = array(
			'meta_query' => array(
				array(
					'key' => 'height',
					'value' => array(
						'150',
						'190',
					),
					'compare' => 'BETWEEN',
					'type' => 'NUMERIC'
				),
				array(
					'key' => 'weight',
					'value' => array(
						'90',
						'120',
					),
					'compare' => 'NOT BETWEEN',
					'type' => 'NUMERIC'
				),
			)
		);

		$the_query = new WP_Query( $args );
		$this->assertCount( 1, $the_query->get_posts() );
	}

	/**
	 * test get_meta_sql for virtual columns with LIKE OR NOT LIKE - Wp_Query
	 */
	public function test_get_meta_sql_for_virtual_columns_with_like_or_not_like()
	{
		$this->delete();

		// create a post
		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, 'country', 'japan', false );
		add_post_meta( $post_id, 'region', 'tokyo', false );

		$this->create_virtual_columns( 'country' . PHP_EOL . 'region' );

		$args = array(
			'meta_query' => array(
				array(
					'key' => 'country',
					'value' => 'ja',
					'compare' => 'LIKE'
				),
				array(
					'key' => 'region',
					'value' => 'osa',
					'compare' => 'NOT LIKE'
				),
			)
		);

		$the_query = new WP_Query( $args );
		$this->assertCount( 1, $the_query->get_posts() );
	}

	/**
	 * test get_meta_sql for virtual columns - Wp_User_Query
	 */
	public function test_get_meta_sql_for_virtual_columns_wp_user_query()
	{
		$this->delete();

		// create a user
		$user_id = $this->factory->user->create();
		add_user_meta( $user_id, 'language', 'japanese' );
		add_user_meta( $user_id, 'hobby', 'walking' );

		$this->create_virtual_columns( 'language' . PHP_EOL . 'hobby' );

		$args = array(
			'meta_query' => array(
				array(
					'key' => 'language',
					'value' => 'japanese',
					'compare' => '='
				),
				array(
					'key' => 'hobby',
					'value' => 'walking',
					'compare' => '='
				),
			)
		);

		$the_query = new WP_User_Query( $args );
		$this->assertCount( 1, $the_query->get_results() );
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
}
