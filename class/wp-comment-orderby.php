<?php
/**
 * Class Beyond_Wpdb_Wp_Comment_Orderby
 * Wordpress version: 5.4.1
 */

class Beyond_Wpdb_Wp_Comment_Orderby {
	/**
	 * Query vars, after parsing
	 */
	public $query_vars = array();

	public function _parse_orderby( $orderby_clauses, $query ) {

		//　Check if $orderby can be converted
		if ( ! $this->check( $query ) ) {
			return $orderby_clauses;
		}

		$orderby = $orderby_clauses['orderby'];
		$query_vars = $query->query_vars;
		//　$orderby contains meta_value
		if ( strpos( $orderby, 'meta_value' ) ) {
			$clauses = $query->meta_query->get_clauses();
			$orderby = explode( ',', $orderby );
			$_orderby = is_array( $query_vars['orderby'] ) ? array_keys( $query_vars['orderby'] ) : array_keys( array( $query_vars['orderby'] ) );
			$clauses_keys = array_keys( $clauses );
			$alias = esc_sql( constant( beyond_wpdb_get_define_table_name( 'comment' ) ) );

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
			$orderby_clauses['orderby'] = $orderby;
		}

		return $orderby_clauses;
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

add_filter( 'comments_clauses', 'beyond_wpdb_wp_comment_query_parse_orderby', 10, 2 );
function beyond_wpdb_wp_comment_query_parse_orderby( $clauses, $comment_query ) {
	$beyond_wpdb_wp_comment_orderby = new Beyond_Wpdb_Wp_Comment_Orderby();
	return $beyond_wpdb_wp_comment_orderby->_parse_orderby( $clauses, $comment_query );
}
