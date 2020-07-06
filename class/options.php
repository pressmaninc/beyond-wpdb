<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Beyond_Wpdb_Settings_page {
	private $columns = array();

	/**
	 * construct
	 */
	public function __construct()
	{
		add_action( 'admin_menu', array( $this, 'add_beyond_wpdb_settings_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
		$beyond_wpdb_column = new Beyond_Wpdb_Column();
		$beyond_wpdb_column->set_columns();
		$this->columns = $beyond_wpdb_column->get_columns();
		$options = get_option('beyond_wpdb_data_init_name');
		print_r($options);

	}

	/**
	 * Add options page
	 */
	public function add_beyond_wpdb_settings_page()
	{
		add_options_page(
			'Beyond WPDB',
			'Beyond WPDB',
			'manage_options',
			'',
			array( $this, 'create_beyond_wpdb_settings_page' )
		);
	}

	/**
	 * create beyond wpdb settings page
	 */
	public function create_beyond_wpdb_settings_page()
	{
?>
		<div class='beyond-wpdb-settings-wrap'>
			<!-- title -->
			<h1 style="margin-bottom: 30px;">Beyond WPDB Settings</h1>
			<!-- form -->
			<form action='options.php' method='post'>
				<!-- Group Concat -->
				<div>
					<?php settings_fields( 'beyond_wpdb_group' ); ?>
					<?php do_settings_sections( 'croup_concat_section' ); ?>
				</div>
				<div>
					<?php settings_fields( 'beyond_wpdb_group' ); ?>
					<?php do_settings_sections( 'data_init_section' ); ?>
				</div>
				<!-- Virtual Column Settings -->
				<div>
					<?php settings_fields( 'beyond_wpdb_group' ); ?>
					<?php do_settings_sections( 'virtual_columns_section' ); ?>
				</div>

				<!-- Submit Button -->
				<?php submit_button(); ?>
			</form>
		</div>
<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init()
	{
		// group concat
		add_settings_section(
			'setting_section_croup_concat',
			'<h2>Group Concat</h2>',
			array( $this, 'print_group_concat_section_info' ),
			'croup_concat_section'
		);

		add_settings_field(
			'',
			"group_concat_max_len",
			array( $this, 'print_croup_concat_field' ),
			'croup_concat_section',
			'setting_section_croup_concat'
		);

		register_setting(
			'beyond_wpdb_group',
			'',
			'sanitize_text_field'
		);

		// data init
		add_settings_section(
			'setting_section_data_init',
			'<h2>Data Init</h2>',
			array( $this, 'print_data_init_section_info' ),
			'data_init_section'
		);

		add_settings_field(
			'data_init',
			"Data Init",
			array( $this, 'print_data_init_field' ),
			'data_init_section',
			'setting_section_data_init'
		);

		register_setting(
			'beyond_wpdb_group',
			'beyond_wpdb_data_init_name',
			array( $this, 'sanitize_input_columns' )
		);

		// virtual columns setting
		add_settings_section(
			'virtual_column_section_id',
			'<h2>Virtual Column Settings</h2>',
			array( $this, 'print_virtual_column_section_info' ),
			'virtual_columns_section'
		);

		add_settings_field(
			'postmeta_json', // id
			"postmeta_json", // title
			array( $this, 'print_postmeta_json_field' ), // callback
			'virtual_columns_section', // section page
			'virtual_column_section_id' // section id
		);

		add_settings_field(
			'usermeta_json',
			"usermeta_json",
			array( $this, 'print_usermeta_json_field' ),
			'virtual_columns_section', // section page
			'virtual_column_section_id' // section id
		);

		add_settings_field(
			'commentmeta_json', // id
			"commentmeta_json", // title
			array( $this, 'print_commentmeta_json_field' ), // callback
			'virtual_columns_section', // section id
			'virtual_column_section_id'
		);

		register_setting(
			'beyond_wpdb_group',
			'beyond_wpdb_virtual_column_name',
			array( $this, 'sanitize_input_columns' )
		);
	}

	/**
	 * @param $input
	 *
	 * @return array
	 */
	public function sanitize_input_columns( $input )
	{
		$new_input = array();
		if( isset( $input['postmeta_json'] ) ) {
			$input_columns = explode( PHP_EOL, $input['postmeta_json'] );
			$input_columns = $this->sanitize( $input_columns );
			$new_input['postmeta_json'] = implode( PHP_EOL, $input_columns );
		}

		if( isset( $input['usermeta_json'] ) ) {
			$input_columns = explode( PHP_EOL, $input['usermeta_json'] );
			$input_columns = $this->sanitize( $input_columns );
			$new_input['usermeta_json'] = implode( PHP_EOL, $input_columns );
		}

		if( isset( $input['commentmeta_json'] ) ) {
			$input_columns = explode( PHP_EOL, $input['commentmeta_json'] );
			$input_columns = $this->sanitize( $input_columns );
			$new_input['commentmeta_json'] = implode( PHP_EOL, $input_columns );
		}

		if( isset( $input['data_init'] ) ) {
			$input_columns = explode( PHP_EOL, $input['data_init'] );
			$input_columns = $this->sanitize( $input_columns );
			$new_input['data_init'] = implode( PHP_EOL, $input_columns );
		}

		return $new_input;
	}

	/**
	 * @param $option
	 * data init
	 */
	public function data_init( $option )
	{
		if ( isset( $option['data_init'] ) && $option['data_init'] === '1' ) {
			$beyond_wpdb_sql = new Beyond_Wpdb_Sql();
			$beyond_wpdb_sql->data_init();
		}
	}

	/**
	 * @param $input_columns
	 *
	 * @return string
	 */
	public function sanitize( $input_columns )
	{
		foreach ( $input_columns as $key => $value ) {
			$input_columns[$key] = sanitize_text_field( $value );
		}
		return $input_columns;
	}

	/**
	 * @param $options
	 * create virtual column
	 */
	public function create_virtual_column_and_index( $options ) {
		global $wpdb;

		if ( is_array( $options ) ) {
			foreach ( $options as $key => $option ) {

				if ( 'postmeta_json' === $key ) {
					$type = 'post';
				} elseif ( 'usermeta_json' === $key ) {
					$type = 'user';
				} else {
					$type = 'comment';
				}

				$table_name = $this->get_json_table_name( $type );
				$exist_columns = $this->columns[$type];
				$option = explode( PHP_EOL, $option );

				foreach ( $option as $value ) {

					// If $value already exists, continue
					if ( in_array( $value, $exist_columns ) ) {
						continue;
					}

					$json_key = '$.' . $value;

					// create virtual column
					$sql = "ALTER TABLE {$table_name} ADD {$value} VARCHAR(255) GENERATED ALWAYS AS ( JSON_UNQUOTE( JSON_EXTRACT( json, '$json_key' ) ) )";
					$wpdb->query( $sql );

					// create index
					$sql = "ALTER TABLE {$table_name} ADD INDEX ({$value})";
					$wpdb->query( $sql );
				}
			}
		}
	}

	/**
	 * @param $options
	 * delete virtual columns
	 * @return mixed
	 */
	public function delete_virtual_column( $options ) {
		global $wpdb;

		if ( is_array( $options ) ) {
			foreach ( $options as $key => $option ) {
				if ( 'postmeta_json' === $key ) {
					$type = 'post';
				} elseif ( 'usermeta_json' === $key ) {
					$type = 'user';
				} else {
					$type = 'comment';
				}

				$table_name = $this->get_json_table_name( $type );
				$exist_columns = $this->columns[$type];
				$option = explode( PHP_EOL, $option );

				foreach ( $exist_columns as $column ) {
					if ( ! in_array( $column, $option ) ) {
						$sql = "ALTER TABLE {$table_name} DROP COLUMN {$column}";
						$wpdb->query( $sql );
					}
				}

			}
		}
	}


	public function get_json_table_name( $type ) {
		return esc_sql( constant( beyond_wpdb_get_define_table_name( $type ) ) );
	}

	/**
	 * Print the Section text
	 */
	public function print_virtual_column_section_info()
	{
		print '<p>
			     Enter a list metakeys for which you want to create a virtual column.Each metakey should be separated by return enter key.<br>
			     The virtual column is indexed and the search uses the virtual column, which makes it faster.
		       </p>';
	}

	/**
	 * print postmeta json field
	 */
	public function print_postmeta_json_field()
	{
		$value = implode( PHP_EOL, $this->columns['post'] );
		printf(
			'<textarea rows="3" cols="40" id="postmeta_json" name="beyond_wpdb_virtual_column_name[postmeta_json]">%s</textarea>',
			$value
		);
	}

	/**
	 * print usermeta json field
	 */
	public function print_usermeta_json_field()
	{
		$value = implode( PHP_EOL, $this->columns['user'] );
		printf(
			'<textarea rows="3" cols="40" id="usermeta_json" name="beyond_wpdb_virtual_column_name[usermeta_json]">%s</textarea>',
			$value
		);
	}

	/**
	 * print commentmeta json field
	 */
	public function print_commentmeta_json_field()
	{
		$value = implode( PHP_EOL, $this->columns['comment'] );
		printf(
			'<textarea rows="3" cols="40" id="commentmeta_json" name="beyond_wpdb_virtual_column_name[commentmeta_json]">%s</textarea>',
			$value
		);
	}

	/**
	 * print group concat section info
	 */
	public function print_group_concat_section_info()
	{
		print '<p>Use GROUP_CONCAT in data init.If the json string consisting of all metas is longer than this value, an error occurs.</p>';
	}

	/**
	 * print croup_concat field
	 */
	public function print_croup_concat_field()
	{
		global $wpdb;
		$group_concat_max_len = $wpdb->get_results( "show variables like 'group_concat_max_len'" );
		printf(
			'<p>%s</p>',
			$group_concat_max_len[0]->Value
		);
	}

	/**
	 * print data init section info
	 */
	public function print_data_init_section_info()
	{
		print '<p>Collect all meta information and re-register it in the json table.</p>';
	}

	/**
	 *  print data_init field
	 */
	public function print_data_init_field()
	{
		print '<input type="checkbox" id="data_init" name="beyond_wpdb_data_init_name[data_init]" value="1">';
	}
}

$my_settings_page = new Beyond_Wpdb_Settings_page();

/**
 * @param $option
 * create virtual column and delete virtual column and delete option
 */
function do_virtual_column_processing( $option ) {
	$beyond_wpdb_settings_page = new Beyond_Wpdb_Settings_page();
	$beyond_wpdb_settings_page->create_virtual_column_and_index( get_option($option) );
	$beyond_wpdb_settings_page->delete_virtual_column( get_option($option) );

	delete_option( $option );
}

/**
 * @param $option
 * data init
 */
function do_data_init_processing( $option ) {
	$beyond_wpdb_settings_page = new Beyond_Wpdb_Settings_page();
	$beyond_wpdb_settings_page->data_init( get_option($option) );

	delete_option( $option );
}

// add option
/**
 * add option beyond_wpdb_virtual_column_name
 */
add_action( 'add_option_beyond_wpdb_virtual_column_name', 'do_after_add_virtual_column' ,10, 2 );
function do_after_add_virtual_column( $option, $value ) {
	do_virtual_column_processing( $option );
}
/**
 * add option beyond_wpdb_data_init_name
 */
add_action( 'add_option_beyond_wpdb_data_init_name', 'do_after_add_data_init' ,10, 2 );
function do_after_add_data_init( $option, $value ) {
	do_data_init_processing( $option );
}

// update option
/**
 * update option beyond_wpdb_virtual_column_name
 */
add_action( 'update_option_beyond_wpdb_virtual_column_name', 'do_after_update_virtual_column' ,10, 3 );
function do_after_update_virtual_column( $old, $value, $option ) {
	do_virtual_column_processing( $option );
}

/**
 * update option beyond_wpdb_data_init_name
 */
add_action( 'update_option_beyond_wpdb_data_init_name', 'do_after_update_data_init' ,10, 3 );
function do_after_update_data_init( $old, $value, $option ) {
	do_data_init_processing( $option );
}






