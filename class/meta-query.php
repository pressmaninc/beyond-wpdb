<?php
class Beyond_Wpdb_Meta_Query {
	// WP_Meta_Query::is_first_order_clauseと同一で大丈夫かも
	protected function is_first_order_clause( $query ) {
		return isset( $query['key'] ) || isset( $query['value'] );
	}

	// get_meta_sql hookで呼ばれる関数
	public function get_meta_sql( $sql, $queries, $type, $primary_table, $primary_id_column, $context ) {
		if ( ! $this->check( $queries ) ) {
			return $sql;
		}

		return $this->get_sql( $type, $queries, $primary_table, $primary_id_column, $context );
	}

	// WP_Meta_Query::get_sql_clausesとほぼ同一で大丈夫かも
	protected function get_sql_clauses() {
		$this->get_sql_for_query();
	}

	// WP_Meta_Query::get_sql_for_queryとほぼ同一で大丈夫かも
	protected function get_sql_for_query() {
	}

	// WP_Meta_Query::get_sql_for_clauseから大きく変える必要あり
	public function get_sql_for_clause() {
	}

	// WP_Meta_Query::get_sql参照
	public function get_sql( $type, $primary_table, $primary_id_column, $context ) {
		$meta_table = $this->_get_meta_table( $type );
		$sql = $this->get_sql_clauses();
		return $sql;
	}

	// 変換して大丈夫かどうか判断
	protected function check( $query ) {
		// is_first_order_clauseを活用して再帰的に判断する必要あり
		// 再帰的に確認する方法としてはWP_Meta_Query::get_sql_for_queryが参考になる
		if (!is_array($query)) {
			return false;
		}

		foreach ($query as $key => $clause) {
			if (is_array($clause)) {
				if ($this->is_first_order_clause( $clause )) {
					if (!$this->checkColumns($clause)) {
						return false;
					}
				} else {
					if ($this->check($clause)) {
						continue;
					} else {
						return false;
					}
				}
			}
		}

		return true;
	}

	//　変換条件チェック
	protected function checkColumns($columns) {
		return isset( $columns['key'] ) &&
		       isset( $columns['value'] ) &&
		       (isset( $columns['compare_key'] ) && ($columns['compare_key'] === '=' || $columns['compare_key'] === 'EXISTS'));
	}

	// jsonの独自テーブルのテーブル名を返す
	protected function _get_meta_table( $type ) {
	}
}


add_filter( 'get_meta_sql', 'beyond_wpdb_meta_query_get_sql', 10, 5 );
function beyond_wpdb_meta_query_get_sql( $sql, $queries, $type, $primary_table, $primary_id_column, $context = null ) {
	$beyond_wpdb_meta_query = new Beyond_Wpdb_Meta_Query();
	return $beyond_wpdb_meta_query->get_meta_sql( $sql, $queries, $type, $primary_table, $primary_id_column, $context );
}
