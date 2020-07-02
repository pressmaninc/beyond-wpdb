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
		$column_class = new Column();
		$column_class->set_columns();
		$this->columns = $column_class->get_columns();
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
				<!-- global settings(suppress filters) -->
				<div>
					<?php settings_fields( 'beyond_wpdb_suppress_filters_group' ); ?>
					<?php do_settings_sections( 'suppress_filters_section' ); ?>
				</div>
				<!-- Group Concat -->
				<div>
					<?php settings_fields( 'beyond_wpdb_croup_concat_group' ); ?>
					<?php do_settings_sections( 'croup_concat_section' ); ?>
				</div>
				<div>
					<?php settings_fields( 'beyond_wpdb_data_init_group' ); ?>
					<?php do_settings_sections( 'data_init_section' ); ?>
				</div>
				<!-- Virtual Column Settings -->
				<div>
					<?php settings_fields( 'beyond_wpdb_virtual_column_group' ); ?>
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
		// suppress filters setting
		register_setting(
			'beyond_wpdb_suppress_filters_group',
			'beyond_wpdb_suppress_filters_name',
			'sanitize_text_field'
		);

		add_settings_section(
			'setting_section_suppress_filters',
			'<h2>Global Settings</h2>',
			array(),
			'suppress_filters_section'
		);

		add_settings_field(
			'suppress_filters',
			"Ignore suppress_filters",
			array( $this, 'print_suppress_filters_section' ),
			'suppress_filters_section',
			'setting_section_suppress_filters'
		);

		// virtual columns setting
		register_setting(
			'beyond_wpdb_virtual_column_group',
			'beyond_wpdb_virtual_column_name',
			array( $this, 'create_virtual_column' )
		);

		add_settings_section(
			'setting_section_id',
			'<h2>Virtual Column Settings</h2>',
			array( $this, 'print_section_info' ),
			'virtual_columns_section'
		);

		add_settings_field(
			'postmeta_json', // id
			"postmeta_json", // title
			array( $this, 'print_postmeta_json_field' ), // callback
			'virtual_columns_section', // section id
			'setting_section_id'
		);

		add_settings_field(
			'usermeta_json',
			"usermeta_json",
			array( $this, 'print_usermeta_json_field' ),
			'virtual_columns_section',
			'setting_section_id'
		);

		add_settings_field(
			'commentmeta_json', // id
			"commentmeta_json", // title
			array( $this, 'print_commentmeta_json_field' ), // callback
			'virtual_columns_section', // section id
			'setting_section_id'
		);

		// group concat
		register_setting(
			'beyond_wpdb_croup_concat_group',
			'',
			'sanitize_text_field'
		);

		add_settings_section(
			'setting_section_croup_concat',
			'<h2>Group Concat</h2>',
			array(),
			'croup_concat_section'
		);

		add_settings_field(
			'',
			"group_concat_max_len",
			array( $this, 'print_croup_concat_field' ),
			'croup_concat_section',
			'setting_section_croup_concat'
		);

		// data init
		register_setting(
			'beyond_wpdb_data_init_group',
			'beyond_wpdb_data_init_name',
			array( $this, 'data_init' )
		);

		add_settings_section(
			'setting_section_data_init',
			'<h2>Data Init</h2>',
			array(),
			'data_init_section'
		);

		add_settings_field(
			'data_init',
			"Data Init",
			array( $this, 'print_data_init_field' ),
			'data_init_section',
			'setting_section_data_init'
		);
	}

	/**
	 * @param $input
	 *
	 * @return array
	 */
	public function create_virtual_column( $input )
	{
		$new_input = array();
		if( isset( $input['postmeta_json'] ) ) {
			$columns = explode( PHP_EOL, $input['postmeta_json'] );
			$columns = $this->sanitize_each_columns_and_create_virtual_column_sql( $columns, 'post' );
			$columns = $this->delete_virtual_column( $columns, 'post' );
			$new_input['postmeta_json'] = implode( ',', $columns );
		}

		if( isset( $input['usermeta_json'] ) ) {
			$columns = explode( PHP_EOL, $input['usermeta_json'] );
			$columns = $this->sanitize_each_columns_and_create_virtual_column_sql( $columns, 'user' );
			$columns = $this->delete_virtual_column( $columns, 'user' );
			$new_input['usermeta_json'] = implode( ',', $columns );
		}

		if( isset( $input['commentmeta_json'] ) ) {
			$columns = explode( PHP_EOL, $input['commentmeta_json'] );
			$columns = $this->sanitize_each_columns_and_create_virtual_column_sql( $columns, 'comment' );
			$columns = $this->delete_virtual_column( $columns, 'comment' );
			$new_input['commentmeta_json'] = implode( ',', $columns );
		}

		return $new_input;
	}

	public function data_init( $input )
	{
		if ( isset( $input['data_init'] ) && $input['data_init'] === '1' ) {
			$beyond_wpdb_sql = new Beyond_Wpdb_Sql();
			$beyond_wpdb_sql->data_init();
		}
		return $input;
	}

	/**
	 * @param $columns
	 * @param $type
	 * sanitize each columns and create virtual column
	 * @return mixed
	 */
	public function sanitize_each_columns_and_create_virtual_column_sql( $columns, $type ) {
		global $wpdb;

		foreach ( $columns as $index => $val ) {
			$columns[$index] = $this->sanitize( $val );
			$column = $columns[$index];

			if ( $column )  {
				$table_name = $this->get_json_table_name( $type );

				// If $column name already exists, continue
				$exist_columns = $this->columns[$table_name];
				foreach ( $exist_columns as $index => $value ) {
					$exist_columns[$index] = str_replace( 'virtual_', '', $value );
				}
				if ( in_array( str_replace( 'virtual_', '', $column ), $exist_columns ) ) {
					continue;
				}

				$virtual_column = 'virtual_' . $column;
				$key = '$.' . $column;

				// create virtual column
				$sql = "ALTER TABLE {$table_name} ADD {$virtual_column} VARCHAR(255) GENERATED ALWAYS AS ( JSON_UNQUOTE( JSON_EXTRACT( json, '$key' ) ) )";
				$wpdb->query( $sql );

				// create index
				$sql = "ALTER TABLE {$table_name} ADD INDEX {$column}_index({$virtual_column})";
				$wpdb->query( $sql );
			}
		}

		return $columns;
	}

	/**
	 * @param $input_columns
	 * @param $type
	 * delete virtual columns
	 * @return mixed
	 */
	public function delete_virtual_column($input_columns, $type) {
		global $wpdb;
		$table_name = $this->get_json_table_name( $type );
		$exist_columns = $this->columns[$table_name];
		// remove virtual_ from the elements of $exist_columns
		foreach ( $exist_columns as $index => $value ) {
			$exist_columns[$index] = str_replace( 'virtual_', '', $value );
		}
		// remove virtual_ from the elements of $input_columns
		foreach ( $input_columns as $index => $value ) {
			$input_columns[$index] = str_replace( 'virtual_', '', $value );
		}
		// delete
		foreach ( $exist_columns as $column ) {
			if ( ! in_array( $column, $input_columns ) ) {
				$sql = "ALTER TABLE {$table_name} DROP COLUMN virtual_{$column}";
				$wpdb->query( $sql );
			}
		}

		return $input_columns;
	}

	/**
	 * @param $input
	 *
	 * @return string
	 */
	public function sanitize( $input )
	{
		return sanitize_text_field( $input );
	}

	public function get_json_table_name( $type ) {
		return esc_sql( constant( beyond_wpdb_get_define_table_name( $type ) ) );
	}

	/**
	 * Print suppress_filters section
	 */
	public function print_suppress_filters_section()
	{
		print '
		<input type="radio" id="suppress_filters_yes" name="beyond_wpdb_suppress_filters_name[suppress_filters]" checked>
		<label for="suppress_filters_yes">YES</label><br>
		<br>
		<input type="radio" id="suppress_filters_no" name="beyond_wpdb_suppress_filters_name[suppress_filters]">
		<label for="suppress_filters_no">NO</label><br>
		<br>
		<p>Whether to ignore suppess_filters setting or not</p>
		';
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info()
	{
		print '<p>Enter a list metakeys for which you want to create a virtual column.Each metakey should be separated by return enter key.</p>';
	}

	/**
	 * print postmeta json field
	 */
	public function print_postmeta_json_field()
	{
		$value = implode( PHP_EOL, $this->columns[$this->get_json_table_name( 'post' )] );
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
		$value = implode( PHP_EOL, $this->columns[$this->get_json_table_name( 'user' )] );
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
		$value = implode( PHP_EOL, $this->columns[$this->get_json_table_name( 'comment' )] );
		printf(
			'<textarea rows="3" cols="40" id="commentmeta_json" name="beyond_wpdb_virtual_column_name[commentmeta_json]">%s</textarea>',
			$value
		);
	}

	/**
	 * print commentmeta croup_concat field
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
	 *
	 */
	public function print_data_init_field()
	{
		print '<input type="checkbox" id="data_init" name="beyond_wpdb_data_init_name[data_init]" value="1">';
	}
}

$my_settings_page = new Beyond_Wpdb_Settings_page();




