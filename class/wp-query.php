<?php
/**
 * Class Beyond_Wpdb_Wp_Query
 * Wordpress version: 5.4.1
 */

class Beyond_Wpdb_Wp_Query {
	protected $meta_query = false;

	public function parse_orderby( $orderby, $query ) {

		if ( ! $this->check( $query ) ) {
			return $orderby;
		}

		$query_vars = $query->query_vars;
		$orderby    = '';
		$_orderby   = $query_vars['orderby'];

		// orderbyが配列の場合連結
		if ( is_array( $_orderby ) ) {
			// 連想配列の最後の値を取得
			$offset       = count( $_orderby ) - 1;
			$copy_orderby = $_orderby;
			$last_ele     = rtrim( array_keys( array_slice( $copy_orderby, $offset, 1, true ) )[0] );

			foreach ( $_orderby as $col => $order ) {
				$order   = $order !== '' ? $order : 'DESC';
				$col     = rtrim( $col );
				$orderby .= $last_ele === $col
					? "JSON_EXTRACT(json, '$.$col')" . ' ' . $order . ' '
					: "JSON_EXTRACT(json, '$.$col')" . ' ' . $order . ', ';
			}
		} else {
			$order    = $query_vars['order'] !== '' ? $query_vars['order'] : 'DESC';
			$_orderby = rtrim( $_orderby );
			$orderby  = "JSON_EXTRACT(json, '$.$_orderby')" . ' ' . $order . ' ';
		}

		return $orderby;
	}

	/*
	 * 変換条件チェック
	 */
	protected function check( $query ) {
		$query_vars = $query->query_vars;

		// meta_queryが指定されている場合、postmeta_jsonテーブルとinner joinされていると判定しています。
		return array_key_exists( 'meta_query', $query_vars ) &&
		       count( $query_vars['meta_query'] ) > 0 &&
		       array_key_exists( 'orderby', $query_vars ) &&
		       $query_vars['orderby'] !== '' &&
		       ! $this->check_not_allowed_orderby( $query_vars['orderby'] );
	}

	protected function check_not_allowed_orderby( $orderby ) {
		// $orderbyが以下配列に含まれている場合デフォルトの$orderbyを返す
		$not_allowed_keys = array(
			'post_name',
			'post_author',
			'post_date',
			'post_title',
			'post_modified',
			'post_parent',
			'post_type',
			'meta_value',
			'name',
			'author',
			'date',
			'title',
			'modified',
			'parent',
			'type',
			'ID',
			'menu_order',
			'comment_count',
			'rand',
			'post__in',
			'post_parent__in',
			'post_name__in',
		);

		if ( is_array( $orderby ) ) {
			//　配列指定の場合、一つでも含まれている場合デフォルトの$orderbyを返す
			foreach ( $orderby as $col => $order ) {
				if ( in_array( rtrim( $col ), $not_allowed_keys ) ) {
					return true;
				}
			}
		} else {
			return in_array( rtrim( $orderby ), $not_allowed_keys );
		}

		return false;
	}
}

add_filter( 'posts_orderby_request', 'beyond_wpdb_wp_query_parse_orderby', 10, 2 );
function beyond_wpdb_wp_query_parse_orderby( $orderby, $query ) {
	$beyond_wpdb_wp_query = new Beyond_Wpdb_Wp_Query();

	return $beyond_wpdb_wp_query->parse_orderby( $orderby, $query );
}
