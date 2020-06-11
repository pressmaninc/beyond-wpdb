<?php
/**
 * Class Beyond_Wpdb_Wp_Query
 * Wordpress version: 5.4.1
 */

class Beyond_Wpdb_Wp_Query {
	/**
	 * Query vars, after parsing
	 */
	public $query_vars = array();

	public function _parse_orderby( $orderby, $query ) {

		//　Check if $orderby can be converted
		if ( ! $this->check( $query ) ) {
			return $orderby;
		}

		//　$orderby contains meta_value
		if ( strpos( $orderby, 'meta_value' ) ) {
			$clauses = $query->meta_query->get_clauses();
			$orderby = explode( ',', $orderby );
			$_orderby = is_array( $query->query['orderby'] ) ? array_keys( $query->query['orderby'] ) : array_keys( array( $query->query['orderby'] ) );
			$clauses_keys = array_keys( $clauses );


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
						$orderby[$key] = "CAST(JSON_EXTRACT(json, '$.{$clause['key']}') AS {$clause['cast']}) $order";
					}

				} elseif ( strpos( $val, 'meta_value' ) ) {
					$clause = array();
					foreach ( $clauses_keys as $ck => $cv ) {
						if ( strpos( ' ' . $val, $clauses[$cv]['alias'] ) ) {
							$clause = $clauses[$cv];
						}
					}

					if ( ! empty( $clause['type'] ) ) {
						$orderby[$key] = "CAST(JSON_EXTRACT(json, '$.{$clause['key']}') AS {$clause['cast']}) $order";
					} else {
						$orderby[$key] = "JSON_EXTRACT(json, '$.{$clause['key']}') $order";
					}
				}

			}
			$orderby = implode( ', ', $orderby );
		}

		return $orderby;
	}

	/**
	 * @param $query
	 * Check for conversion
	 * @return bool
	 */
	protected function check( $query ) {

		$q = $query->query;

		if ( ! is_array( $query->meta_query->queries ) ) {
			return false;
		}

		//　Convert only if it is joined with the json table and an orderby clause is specified.
		return $this->jsonTable_or_not( $query->meta_query->queries ) && ( isset( $q['orderby'] ) && $q['orderby'] !== '' );

	}

	/**
	 * @param $queries
	 * Recursively check if it is joined with the json table
	 * The condition for joining with the json table is that key and value must be specified　
	 * and meta_compare_key is either "=" or "EXISTS".
	 * @return bool
	 */
	protected function jsonTable_or_not( $queries ) {

		foreach ( $queries as $k => $val ) {
			if ( is_array( $val ) ) {
				if ( isset( $val['key'] ) || isset( $val['value'] ) ) {
					if ( !$this->check_isset_key_value( $val ) ) {
						return false;
					}
				} else {
					if ( !$this->jsonTable_or_not( $val ) ) {
						return false;
					} else {
						continue;
					}
				}
			}
		}

		return true;
	}

	/**
	 * @param $val
	 *
	 * @return bool
	 */
	protected function check_isset_key_value( $val ) {
		$correct_meta_compare_key = true;

		if ( isset( $val['compare_key'] ) ) {
			$correct_meta_compare_key = $val['compare_key'] === '=' || $val['compare_key'] === 'EXISTS';
		}

		return isset( $val['key'] ) && isset( $val['value'] ) && $correct_meta_compare_key;
	}
}

add_filter( 'posts_orderby_request', 'beyond_wpdb_wp_query_parse_orderby', 10, 2 );
function beyond_wpdb_wp_query_parse_orderby( $orderby, $query ) {
	$beyond_wpdb_wp_query = new Beyond_Wpdb_Wp_Query();

	return $beyond_wpdb_wp_query->_parse_orderby( $orderby, $query );
}
