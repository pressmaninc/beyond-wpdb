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

	/**
	 * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
	 */
	protected function parse_order( $order ) {
		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}

	/**
	 * Converts the given orderby alias (if allowed) to a properly-prefixed value.
	 */
	protected function parse_orderby( $orderby ) {
		global $wpdb, $beyond_wpdb_meta_query;

		// Used to filter values.
		$allowed_keys = array(
			'post_name',
			'post_author',
			'post_date',
			'post_title',
			'post_modified',
			'post_parent',
			'post_type',
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

		$allowed_keys[] = $orderby;

		$primary_meta_key   = '';
		$primary_meta_query = false;
		$meta_clauses       = $beyond_wpdb_meta_query->get_clauses();

		if ( ! empty( $meta_clauses ) ) {
			$primary_meta_query = reset( $meta_clauses );

			if ( ! empty( $primary_meta_query['key'] ) ) {
				$primary_meta_key = $primary_meta_query['key'];
				$allowed_keys[]   = $primary_meta_key;
			}

			$allowed_keys[] = 'meta_value';
			$allowed_keys[] = 'meta_value_num';
			$allowed_keys   = array_merge( $allowed_keys, array_keys( $meta_clauses ) );
		}

		// If RAND() contains a seed value, sanitize and add to allowed keys.
		$rand_with_seed = false;
		if ( preg_match( '/RAND\(([0-9]+)\)/i', $orderby, $matches ) ) {
			$orderby        = sprintf( 'RAND(%s)', intval( $matches[1] ) );
			$allowed_keys[] = $orderby;
			$rand_with_seed = true;
		}

		if ( ! in_array( $orderby, $allowed_keys, true ) ) {
			return false;
		}

		$orderby_clause = '';

		switch ( $orderby ) {
			case 'post_name':
			case 'post_author':
			case 'post_date':
			case 'post_title':
			case 'post_modified':
			case 'post_parent':
			case 'post_type':
			case 'ID':
			case 'menu_order':
			case 'comment_count':
				$orderby_clause = "{$wpdb->posts}.{$orderby}";
				break;
			case 'rand':
				$orderby_clause = 'RAND()';
				break;
			case $primary_meta_key:
			case 'meta_value':
				if ( ! empty( $primary_meta_query['type'] ) ) {
					$orderby_clause = "CAST(JSON_EXTRACT(json, '$.$orderby') AS {$primary_meta_query['cast']})";
				} else {
					$orderby_clause = "JSON_EXTRACT(json, '$.{$primary_meta_query['key']}')";
				}
				break;
			case 'meta_value_num':
				$orderby_clause = "{$primary_meta_query['alias']}.meta_value+0";
				break;
			case 'post__in':
				if ( ! empty( $this->query_vars['post__in'] ) ) {
					$orderby_clause = "FIELD({$wpdb->posts}.ID," . implode( ',', array_map( 'absint', $this->query_vars['post__in'] ) ) . ')';
				}
				break;
			case 'post_parent__in':
				if ( ! empty( $this->query_vars['post_parent__in'] ) ) {
					$orderby_clause = "FIELD( {$wpdb->posts}.post_parent," . implode( ', ', array_map( 'absint', $this->query_vars['post_parent__in'] ) ) . ' )';
				}
				break;
			case 'post_name__in':
				if ( ! empty( $this->query_vars['post_name__in'] ) ) {
					$post_name__in        = array_map( 'sanitize_title_for_query', $this->query_vars['post_name__in'] );
					$post_name__in_string = "'" . implode( "','", $post_name__in ) . "'";
					$orderby_clause       = "FIELD( {$wpdb->posts}.post_name," . $post_name__in_string . ' )';
				}
				break;
			default:
				if ( array_key_exists( $orderby, $meta_clauses ) ) {
					// $orderby corresponds to a meta_query clause.
					$meta_clause    = $meta_clauses[ $orderby ];
					$orderby_clause = "CAST({$meta_clause['alias']}.meta_value AS {$meta_clause['cast']})";
				} elseif ( $rand_with_seed ) {
					$orderby_clause = $orderby;
				} else {
					// Default: order by post field.
					$orderby = sanitize_key( $orderby );
					$orderby_clause = "JSON_EXTRACT(json, '$.$orderby')";
				}

				break;
		}

		return $orderby_clause;
	}

	public function _parse_orderby( $orderby, $query ) {

		if ( ! $this->check( $query ) ) {
			return $orderby;
		}

		$this->query_vars = $query->query_vars;
		$q = $query->query;
		$orderby_array = array();

		if ( is_array( $q['orderby'] ) ) {
			foreach ( $q['orderby'] as $_orderby => $order ) {
				$orderby = addslashes_gpc( urldecode( $_orderby ) );
				$parsed  = $this->parse_orderby( $orderby );

				if ( ! $parsed ) {
					continue;
				}

				$orderby_array[] = $parsed . ' ' . $this->parse_order( $order );
			}
			$orderby = implode( ', ', $orderby_array );

		} else {
			$q['orderby'] = urldecode( $q['orderby'] );
			$q['orderby'] = addslashes_gpc( $q['orderby'] );

			foreach ( explode( ' ', $q['orderby'] ) as $i => $orderby ) {
				$parsed = $this->parse_orderby( $orderby );
				// Only allow certain values for safety.
				if ( ! $parsed ) {
					continue;
				}

				$orderby_array[] = $parsed;
			}
			$orderby = implode( ' ' . $q['order'] . ', ', $orderby_array );

			if ( empty( $orderby ) ) {
				$orderby = "{$wpdb->posts}.post_date " . $q['order'];
			} elseif ( ! empty( $q['order'] ) ) {
				$orderby .= " {$q['order']}";
			}
		}

		return $orderby;
	}

	/**
	 * Check for conversion
	 */
	protected function check( $query ) {
		global $beyond_wpdb_meta_query;

		$query = $query->query;

		//　Convert only if it is converted to a json table and an orderby clause is specified.
		return count( $beyond_wpdb_meta_query->queries ) > 0 && ( isset( $query['orderby'] ) && $query['orderby'] !== '' );

	}
}

add_filter( 'posts_orderby_request', 'beyond_wpdb_wp_query_parse_orderby', 10, 2 );
function beyond_wpdb_wp_query_parse_orderby( $orderby, $query ) {
	$beyond_wpdb_wp_query = new Beyond_Wpdb_Wp_Query();

	return $beyond_wpdb_wp_query->_parse_orderby( $orderby, $query );
}
