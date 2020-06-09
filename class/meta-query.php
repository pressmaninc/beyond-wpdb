<?php
/**
 * Class Beyond_Wpdb_Meta_Query
 * Wordpress version: 5.4.1
 */

class Beyond_Wpdb_Meta_Query {

	public $queries = array();

	public $meta_table = '';

	public $table_aliases = '';

	public $meta_id_column = '';

	public $primary_table = '';

	public $primary_id_column = '';

	public $clauses = array();

	// WP_Meta_Query::is_first_order_clauseと同一で大丈夫かも
	protected function is_first_order_clause( $query ) {
		return isset( $query['key'] ) || isset( $query['value'] );
	}

	// get_meta_sql hookで呼ばれる関数
	public function get_meta_sql( $sql, $queries, $type, $primary_table, $primary_id_column, $context ) {
		if ( ! $this->check( $queries ) ) {
			return $sql;
		}

		return $this->get_sql( $type, $queries, $primary_table, $primary_id_column, $context )
			? $this->get_sql( $type, $queries, $primary_table, $primary_id_column, $context )
			: $sql;
	}

	// WP_Meta_Query::get_sql参照
	public function get_sql( $type, $queries, $primary_table, $primary_id_column, $context ) {
		$meta_table = $this->_get_meta_table( $type );
		if ( ! $meta_table ) {
			return false;
		};

		$this->queries = $queries;

		$this->table_aliases = array();

		$this->meta_table     = $meta_table;
		$this->meta_id_column = sanitize_key( $type . '_id' );

		$this->primary_table     = $primary_table;
		$this->primary_id_column = $primary_id_column;

		$sql = $this->get_sql_clauses();

		return $sql;
	}

	// WP_Meta_Query::get_sql_clausesとほぼ同一で大丈夫かも
	protected function get_sql_clauses() {
		$queries = $this->queries;
		$sql     = $this->get_sql_for_query( $queries );

		if ( ! empty( $sql['where'] ) ) {
			$sql['where'] = ' AND ' . $sql['where'];
		}

		return $sql;
	}

	// WP_Meta_Query::get_sql_for_queryとほぼ同一で大丈夫かも
	protected function get_sql_for_query( &$query, $depth = 0 ) {
		$sql_chunks = array(
			'join'  => array(),
			'where' => array(),
		);

		$sql = array(
			'join'  => '',
			'where' => '',
		);

		$indent = '';
		for ( $i = 0; $i < $depth; $i++ ) {
			$indent .= '  ';
		}

		foreach ( $query as $key => &$clause ) {
			if ( 'relation' === $key ) {
				$relation = $query['relation'];
			} elseif ( is_array($clause) ) {
				// 再帰的判定
				if ( $this->is_first_order_clause( $clause ) ) {
					$clause_sql = $this->get_sql_for_clause( $clause, $query, $key );

					$where_count = count( $clause_sql['where'] );
					if ( ! $where_count ) {
						$sql_chunks['where'][] = '';
					} elseif ( 1 === $where_count ) {
						$sql_chunks['where'][] = $clause_sql['where'][0];
					} else {
						$sql_chunks['where'][] = '( ' . implode( ' AND ', $clause_sql['where'] ) . ' )';
					}

					$sql_chunks['join'] = array_merge( $sql_chunks['join'], $clause_sql['join'] );
				} else {
					$clause_sql = $this->get_sql_for_query( $clause, $depth + 1 );

					$sql_chunks['where'][] = $clause_sql['where'];
					$sql_chunks['join'][]  = $clause_sql['join'];
				}
			}
		}

		// Filter to remove empties.
		$sql_chunks['join']  = array_filter( $sql_chunks['join'] );
		$sql_chunks['where'] = array_filter( $sql_chunks['where'] );

		if ( empty( $relation ) ) {
			$relation = 'AND';
		}

		// Filter duplicate JOIN clauses and combine into a single string.
		if ( ! empty( $sql_chunks['join'] ) ) {
			$sql['join'] = implode( ' ', array_unique( $sql_chunks['join'] ) );
		}

		// Generate a single WHERE clause with proper brackets and indentation.
		if ( ! empty( $sql_chunks['where'] ) ) {
			$sql['where'] = '( ' . "\n  " . $indent . implode( ' ' . "\n  " . $indent . $relation . ' ' . "\n  " . $indent, $sql_chunks['where'] ) . "\n" . $indent . ')';
		}

		return $sql;
	}

	// WP_Meta_Query::get_sql_for_clauseから大きく変える必要あり
	public function get_sql_for_clause( &$clause, $parent_query, $clause_key = '' ) {
		global $wpdb;

		$sql_chunks = array(
			'where' => array(),
			'join'  => array(),
		);

		if ( isset( $clause['compare'] ) ) {
			$clause['compare'] = strtoupper( $clause['compare'] );
		} else {
			$clause['compare'] = is_array( $clause['value'] ) ? 'IN' : '=';
		}

		$non_numeric_operators = array(
			'=',
			'!=',
			'LIKE',
			'NOT LIKE',
			'IN',
			'NOT IN',
			'EXISTS',
			'NOT EXISTS',
			'RLIKE',
			'REGEXP',
			'NOT REGEXP',
		);

		$numeric_operators = array(
			'>',
			'>=',
			'<',
			'<=',
			'BETWEEN',
			'NOT BETWEEN',
		);

		if ( ! in_array( $clause['compare'], $non_numeric_operators, true ) && ! in_array( $clause['compare'], $numeric_operators, true ) ) {
			$clause['compare'] = '=';
		}

		$clause['compare_key'] = strtoupper( $clause['compare_key'] );

		if ( ! in_array( $clause['compare_key'], $non_numeric_operators, true ) ) {
			$clause['compare_key'] = '=';
		}

		$meta_compare     = $clause['compare'];
		$meta_compare_key = $clause['compare_key'];

		// First build the JOIN clause, if one is required.
		$join = '';

		$alias = false;
		if ( false === $alias ) {
			$alias = $this->meta_table;

			// JOIN clauses for NOT EXISTS have their own syntax.
			if ( 'NOT EXISTS' === $meta_compare ) {
				$join .= " LEFT JOIN $this->meta_table";
				$join .= " AS $alias";

				$join .= $wpdb->prepare( " ON ($this->primary_table.$this->primary_id_column = $alias.$this->meta_id_column AND $alias.meta_key = %s )", $clause['key'] );

				// All other JOIN clauses.
			} else {
				$join .= " INNER JOIN $this->meta_table";
				$join .= " AS $alias";
				$join .= " ON ( $this->primary_table.$this->primary_id_column = $alias.$this->meta_id_column )";

			}

			$this->table_aliases[] = $alias;
			$sql_chunks['join'][]  = $join;
		}

		// Save the alias to this clause, for future siblings to find.
		$clause['alias'] = $alias;

		// Determine the data type.
		$_meta_type     = isset( $clause['type'] ) ? $clause['type'] : '';
		$meta_type      = $this->get_cast_for_type( $_meta_type );
		$clause['cast'] = $meta_type;

		// Fallback for clause keys is the table alias. Key must be a string.
		if ( is_int( $clause_key ) || ! $clause_key ) {
			$clause_key = $clause['alias'];
		}

		// Ensure unique clause keys, so none are overwritten.
		$iterator        = 1;
		$clause_key_base = $clause_key;
		while ( isset( $this->clauses[ $clause_key ] ) ) {
			$clause_key = $clause_key_base . '-' . $iterator;
			$iterator++;
		}

		// Store the clause in our flat array.
		$this->clauses[ $clause_key ] =& $clause;

		// meta_value.
		// チェック済みなのでkeyは存在するけど念の為チェック
		if ( array_key_exists( 'value', $clause ) ) {
			$key = '$.' . trim( $clause['key'] );
			$meta_value = $clause['value'];

			if ( in_array( $meta_compare, array( 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ) ) ) {
				if ( ! is_array( $meta_value ) ) {
					$meta_value = preg_split( '/[,\s]+/', $meta_value );
				}
			} else {
				$meta_value = trim( $meta_value );
			}

			switch ( $meta_compare ) {
				case 'IN':
				case 'NOT IN':
				    $meta_compare = $meta_compare === 'IN'
					    ? '='
					    : '!=';
					$column = $this->getColumn( $meta_type, $key );
					$where = '';
					if ( is_array( $meta_value ) ) {
						foreach ( $meta_value as $k => $value ) {

							if ( $k === 0 ) {
								$where .= ' ( ' . $column . ' ' . $meta_compare . ' ' . $wpdb->prepare( '%s', $value ) . ' OR ';
							} elseif ( $k !== count($meta_value) - 1 ) {
								$where .= $column . ' ' . $meta_compare . ' ' . $wpdb->prepare( '%s', $value ) . ' OR ';
							} else {
								$where .= $column . ' ' . $meta_compare . ' ' . $wpdb->prepare( '%s', $value ) . ' ) ';
							}

						}
					} else {
						$where = $column . ' ' . $meta_compare . ' ' . $wpdb->prepare( '%s', $meta_value );
					}
					$where;
					break;

				case 'BETWEEN':
				case 'NOT BETWEEN':
					$column = $this->getColumn( $meta_type, $key );
					$metaValue1 = $wpdb->prepare( '%s', $meta_value[0] );
					$metaValue2 = $wpdb->prepare( '%s', $meta_value[1] );
					$where = $meta_compare === 'BETWEEN'
						? "( $metaValue1 <= $column and $column <= $metaValue2 )"
						: "( $metaValue1 > $column OR $column > $metaValue2 )";
					break;

				case 'LIKE':
				case 'NOT LIKE':
					$meta_value = '%' . $wpdb->esc_like( $meta_value ) . '%';
					$column = $this->getColumn( $meta_type, $key );
					$where = $column . ' ' . $meta_compare . ' ' .$wpdb->prepare('%s' , $meta_value);
					break;

				// EXISTS with a value is interpreted as '='.
				case 'EXISTS':
					$meta_compare = '=';
					$column = $this->getColumn( $meta_type, $key );
					$where = $column . ' ' . $meta_compare . ' ' .$wpdb->prepare('%s' , $meta_value);
					break;

				// 'value' is ignored for NOT EXISTS.
				case 'NOT EXISTS':
					$where = '';
					break;

				default:
					$column = $this->getColumn( $meta_type, $key );
					$where = $column . ' ' . $meta_compare . ' ' .$wpdb->prepare('%s' , $meta_value);
					break;

			}

			if ( $where ) {
				$sql_chunks['where'][] = "{$where}";
			}
		}

		// ANDで連結
		if ( 1 < count( $sql_chunks['where'] ) ) {
			$sql_chunks['where'] = array( '( ' . implode( ' AND ', $sql_chunks['where'] ) . ' )' );
		}

		return $sql_chunks;
	}


	// 変換して大丈夫かどうか判断
	protected function check( $query ) {
		// is_first_order_clauseを活用して再帰的に判断する必要あり
		// 再帰的に確認する方法としてはWP_Meta_Query::get_sql_for_queryが参考になる
		if ( !is_array( $query ) ) {
			return false;
		}

		foreach ( $query as $key => $clause ) {
			if ( is_array( $clause ) ) {
				if ( $this->is_first_order_clause( $clause ) ) {
					if ( !$this->checkColumns( $clause ) ) {
						return false;
					}
				} else {
					if ( $this->check( $clause ) ) {
						continue;
					} else {
						return false;
					}
				}
			}
		}

		return true;
	}

	// jsonの独自テーブルのテーブル名を返す
	protected function _get_meta_table( $type ) {
		global $wpdb;

		if (!in_array( $type, ['post', 'user', 'comment'] ) ) {
			return false;
		}

		$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $type ) ) );

		$json_table_name = $wpdb->get_var( "show tables like '" . $table_name . "'" );

		if ( empty( $json_table_name ) ) {
			return false;
		}

		return $json_table_name;
	}

	// キャスト
	public function get_cast_for_type( $type = '' ) {
		if ( empty( $type ) ) {
			return 'CHAR';
		}

		$meta_type = strtoupper( $type );

		if ( ! preg_match( '/^(?:BINARY|CHAR|DATE|DATETIME|SIGNED|UNSIGNED|TIME|NUMERIC(?:\(\d+(?:,\s?\d+)?\))?|DECIMAL(?:\(\d+(?:,\s?\d+)?\))?)$/', $meta_type ) ) {
			return 'CHAR';
		}

		if ( 'NUMERIC' == $meta_type ) {
			$meta_type = 'SIGNED';
		}

		return $meta_type;
	}

	//　変換条件チェック
	protected function checkColumns( $columns ) {
		return isset( $columns['key'] ) &&
		       isset( $columns['value'] ) &&
		       ( isset( $columns['compare_key'] ) && ( $columns['compare_key'] === '=' || $columns['compare_key'] === 'EXISTS' ) );
	}

	protected function getColumn( $meta_type, $key ) {
		global $wpdb;
		return 'CHAR' === $meta_type
			? $wpdb->prepare( "JSON_EXTRACT(json, %s)", $key )
			: $wpdb->prepare( "CAST(JSON_EXTRACT(json, %s) as $meta_type)", $key );
	}
}

add_filter( 'get_meta_sql', 'beyond_wpdb_meta_query_get_sql', 10, 5 );
function beyond_wpdb_meta_query_get_sql( $sql, $queries, $type, $primary_table, $primary_id_column, $context = null ) {
	$beyond_wpdb_meta_query = new Beyond_Wpdb_Meta_Query();
	return $beyond_wpdb_meta_query->get_meta_sql( $sql, $queries, $type, $primary_table, $primary_id_column, $context );
}
