<?php
/**
 * Class Beyond_Wpdb_Orderby
 * Wordpress version: 5.4.1
 */

class Beyond_Wpdb_Orderby {
	/**
	 * Query vars, after parsing
	 */
	public $query_vars = array();

	public function _parse_orderby( $orderby, $query, $type ) {

		//　Check if $orderby can be converted
		if ( ! $this->check( $query ) ) {
			return false;
		}

		// set $orderby
		if ( $type === 'user' ) {
			$orderby = $query->query_orderby;
		} elseif ( $type === 'comment' ) {
			$orderby = $orderby['orderby'];
		}

		$query_vars = $query->query_vars;
		//　$orderby contains meta_value
		if ( strpos( $orderby, 'meta_value' ) ) {
			$clauses = $query->meta_query->get_clauses();
			$orderby = explode( ',', $orderby );
			$_orderby = is_array( $query_vars['orderby'] ) ? array_keys( $query_vars['orderby'] ) : array_keys( array( $query_vars['orderby'] ) );
			$clauses_keys = array_keys( $clauses );
			$alias = esc_sql( constant( beyond_wpdb_get_define_table_name( $type ) ) );

			foreach ( $orderby as $key => $val ) {
				$order = explode( ' ', $val );
				$order = in_array( 'DESC', $order)
					? $order[array_search( 'DESC', $order )]
					: $order[array_search( 'ASC', $order )];

				if ( strpos( $val, 'mt' ) ) {
					$clause = array();
					$clause_key = '';
					foreach ( $clauses_keys as $ck => $cv ) {
						if ( strpos( $val, $clauses[$cv]['alias'] ) ) {
							$clause = $clauses[$cv];
							$clause_key = $cv;
						}
					}

					if ( in_array( $clause_key, $_orderby ) ) {
						$orderby[$key] = "CAST(JSON_EXTRACT($alias.json, '$.{$clause['key']}') AS {$clause['cast']}) $order";
					}

				} elseif ( strpos( $val, 'meta_value' ) ) {
					$clause = array();
					foreach ( $clauses_keys as $ck => $cv ) {
						if ( strpos( ' ' . $val, $clauses[$cv]['alias'] ) ) {
							$clause = $clauses[$cv];
						}
					}

					if ( ! empty( $clause['type'] ) ) {
						$orderby[$key] = "CAST(JSON_EXTRACT($alias.json, '$.{$clause['key']}') AS {$clause['cast']}) $order";
					} else {
						$orderby[$key] = "JSON_EXTRACT($alias.json, '$.{$clause['key']}') $order";
					}
				}

			}
			$orderby = implode( ', ', $orderby );
			return $orderby;
		}

		return false;
	}

	/**
	 * @param $query
	 * Check for conversion
	 * @return bool
	 */
	protected function check( $query ) {

		$q = $query->query_vars;
		$beyond_wpdb_meta_query = new Beyond_Wpdb_Meta_Query();

		if ( ! is_array( $query->meta_query->queries ) ) {
			return false;
		}

		//　Convert only if it is joined with the json table and an orderby clause is specified.
		return $beyond_wpdb_meta_query->check( $query->meta_query->queries ) && ( isset( $q['orderby'] ) && $q['orderby'] !== '' );

	}
}

add_filter( 'posts_orderby_request', 'beyond_wpdb_wp_query_parse_orderby', 10, 2 );
/**
 * @param $orderby
 * @param $query
 * Convert the order by clause in Wp_Query
 * @return array|bool|mixed
 */
function beyond_wpdb_wp_query_parse_orderby( $orderby, $query ) {
	$beyond_wpdb_orderby = new Beyond_Wpdb_Orderby();
	$result = $beyond_wpdb_orderby->_parse_orderby( $orderby, $query, 'post' );

	if ( ! $result ) {
		return $orderby;
	}

	return $result;
}

add_filter( 'comments_clauses', 'beyond_wpdb_wp_comment_query_parse_orderby', 10, 2 );
/**
 * @param $clauses
 * @param $comment_query
 * Convert the order by clause in Wp_Comment_Query
 * @return mixed
 */
function beyond_wpdb_wp_comment_query_parse_orderby( $clauses, $comment_query ) {
	$beyond_wpdb_orderby = new Beyond_Wpdb_Orderby();
	$result = $beyond_wpdb_orderby->_parse_orderby( $clauses, $comment_query, 'comment' );

	if ( ! $result ) {
		return $clauses;
	}

	$clauses['orderby'] = $result;
	return $clauses;
}

add_action( 'pre_user_query', 'beyond_wpdb_wp_user_query_parse_orderby', 10, 2 );
/**
 * @param $query
 * Convert the order by clause in Wp_User_Query
 */
function beyond_wpdb_wp_user_query_parse_orderby( $query ) {
	$beyond_wpdb_orderby = new Beyond_Wpdb_Orderby();
	$result = $beyond_wpdb_orderby->_parse_orderby( '', $query, 'user' );

	if ( $result !== false ) {
		$query->query_orderby = 'ORDER BY ' . $result;
	}

}
