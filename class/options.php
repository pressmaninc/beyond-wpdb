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
		$this->get_columns();
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

		$title = $this->get_specified_table_columns( 'post' );
		add_settings_field(
			'postmeta_json', // id
			"$title", // title
			array( $this, 'print_postmeta_json_field' ), // callback
			'virtual_columns_section', // section id
			'setting_section_id'
		);

		$title = $this->get_specified_table_columns( 'user' );
		add_settings_field(
			'usermeta_json',
			"$title",
			array( $this, 'print_usermeta_json_field' ),
			'virtual_columns_section',
			'setting_section_id'
		);

		$title = $this->get_specified_table_columns( 'comment' );
		add_settings_field(
			'commentmeta_json',
			"$title",
			array( $this, 'print_commentmeta_json_field' ),
			'virtual_columns_section',
			'setting_section_id'
		);
	}

	/**
	 * get all meta_json table columns
	 */
	public function get_columns()
	{
		global $wpdb;
		foreach( BEYOND_WPDB_PRIMARYS as $primary => $values ) {
			$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $primary ) ) );
			$columns = $wpdb->get_results( "show columns from {$table_name}" );
			$this->columns[$table_name] = array();

			foreach ( $columns as $val ) {
				array_push( $this->columns[$table_name], $val->Field );
			}
		}
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
			$new_input['postmeta_json'] = implode( ',', $columns );
		}

		if( isset( $input['usermeta_json'] ) ) {
			$columns = explode( PHP_EOL, $input['usermeta_json'] );
			$columns = $this->sanitize_each_columns_and_create_virtual_column_sql( $columns, 'user' );
			$new_input['usermeta_json'] = implode( ',', $columns );
		}

		if( isset( $input['commentmeta_json'] ) ) {
			$columns = explode( PHP_EOL, $input['commentmeta_json'] );
			$columns = $this->sanitize_each_columns_and_create_virtual_column_sql( $columns, 'comment' );
			$new_input['commentmeta_json'] = implode( ',', $columns );
		}

		return $new_input;
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
				$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $type ) ) );
				$virtual_column = 'virtual_' . $column;
				$key = '$.' . $column;

				// create virtual column
				$sql = "ALTER TABLE {$table_name} ADD {$virtual_column} VARCHAR(255) GENERATED ALWAYS AS ( JSON_UNQUOTE( JSON_EXTRACT( json, '$key' ) ) )";
				$wpdb->query( $sql );

				// create index
				$sql = "ALTER TABLE {$table_name} ADD INDEX ({$virtual_column})";
				$wpdb->query( $sql );
			}
		}

		return $columns;
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

	/**
	 * get specified table columns
	 *
	 * @param $type
	 * @return string
	 */
	public function get_specified_table_columns( $type )
	{
		$table_name = esc_sql( constant( beyond_wpdb_get_define_table_name( $type ) ) );
		if ( array_key_exists( $table_name, $this->columns ) ) {
			if ( count( $this->columns[$table_name] ) > 0 ) {
				$column = implode( ',', $this->columns[$table_name] );
				$table_name .= "<br />" . "<p>$column</p>";
			}
		}

		return $table_name;
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
		print '<textarea rows="3" cols="40" id="postmeta_json" name="beyond_wpdb_virtual_column_name[postmeta_json]"></textarea>';
	}

	/**
	 * print usermeta json field
	 */
	public function print_usermeta_json_field()
	{
		print '<textarea rows="3" cols="40" id="usermeta_json" name="beyond_wpdb_virtual_column_name[usermeta_json]"></textarea>';
	}

	/**
	 * print commentmeta json field
	 */
	public function print_commentmeta_json_field()
	{
		print '<textarea rows="3" cols="40" id="commentmeta_json" name="beyond_wpdb_virtual_column_name[commentmeta_json]"></textarea>';
	}
}

$my_settings_page = new Beyond_Wpdb_Settings_page();




