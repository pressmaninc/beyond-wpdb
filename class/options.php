<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Beyond_Wpdb_Settings_page
 * Create Beyond Wpdb Settings Page
 */
class Beyond_Wpdb_Settings_page {
	private $privilege_error = False;

	/**
	 * construct
	 */
	public function __construct()
	{
		load_theme_textdomain( 'beyond-wpdb', plugin_dir_path( __FILE__ ) . '../lang' );
		add_action( 'admin_notices', array( $this, 'notice__warnig_or_error' ) );
		add_action( 'admin_menu', array( $this, 'add_beyond_wpdb_settings_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );

	}

	/**
	 * Display warnig or error
	 */
	function notice__warnig_or_error() {
		global $wpdb, $pagenow;

		$current_db = $wpdb->get_var( "SELECT DATABASE()" );
		$current_user = explode('@', $wpdb->get_var( "SELECT USER()" ));
		$grants = $wpdb->get_results( "SHOW GRANTS FOR '{$current_user[0]}'@'{$current_user[1]}'", ARRAY_A );
		$grant_for_mysql_db = False;
		$current_privileges = array();

		foreach ( $grants as $value ) {
			$value = array_values( $value )[0];
			if ( strpos( $value, "ON `mysql`." ) || strpos( $value, "root" ) ) {
				$grant_for_mysql_db = True;
			}
		}

		if ( $grant_for_mysql_db ) {
			$result_privileges = $wpdb->get_results( "SELECT * FROM mysql.db WHERE Db = '{$current_db}' AND User = '{$current_user[0]}'", ARRAY_A );
			foreach ( $result_privileges as $val ) {
				$keys = array_keys( $val );
				$values = array_values( $val );

				// If $v is Y, $keys[$idx] is assumed to be authorized.
				foreach ( $values as $idx => $v ) {
					if ( $v === 'Y' ) {
						array_push( $current_privileges, $keys[$idx] );
					}
				}
			}
		}

		$privileges_error = '';
		$expected_privileges = array(
			'Trigger_priv',
			'Select_priv',
			'Insert_priv',
			'Update_priv',
			'Delete_priv',
			'Create_priv',
			'Drop_priv'
		);
		if ( count( $current_privileges ) > 0 ) {
			foreach ( $expected_privileges as $privilege ) {
				if ( ! in_array( $privilege, $current_privileges ) ) {
					switch ( $privilege ) {
						case 'Trigger_priv':
							$privileges_error .= "<p>" . __("You do not have permission to create triggers.", "beyond-wpdb") . "</p>";
							break;
						case 'Select_priv':
							$privileges_error .= "<p>" . __("You don't have SELECT permission.", "beyond-wpdb") . "</p>";
							break;
						case 'Insert_priv':
							$privileges_error .= "<p>" . __("You don't have INSERT permission.", "beyond-wpdb") . "</p>";
							break;
						case 'Update_priv':
							$privileges_error .= "<p>" . __("You don't have UPDATE permission.", "beyond-wpdb") . "</p>";
							break;
						case 'Delete_priv':
							$privileges_error .= "<p>" . __("You don't have DELETE permission.", "beyond-wpdb") . "</p>";
							break;
						case 'Create_priv':
							$privileges_error .= "<p>" . __("You don't have CREATE permission.", "beyond-wpdb") . "</p>";
							break;
						default :
							$privileges_error .= "<p>" . __("You don't have DROP permission.", "beyond-wpdb") . "</p>";
							break;
					}
					$this->privilege_error = True;
				}
			}
		}

		if ( isset( $_GET['page'] ) && ! $_GET['page'] && $pagenow === 'options-general.php' ) {
			if ( ! $grant_for_mysql_db ) {
				$grants_message = __("You do not have permission to the mysql database.<br>We access the db table in the mysql database to find out what permissions we have to use each feature.<br>There is a possibility that each function will not be available.", "beyond-wpdb" );
				print "
				<div class='notice notice-warning is-dismissible'>
					<p>
					{$grants_message}
					</p>
				</div>
				";
			}

			if ( $privileges_error ) {
				$priviles_message = __( "You do not have the following permissions.Please use each function after granting permission.", "beyond-wpdb" );
				print "
				<div class='notice notice-error is-dismissible'>
					<p>{$priviles_message}</p>
					{$privileges_error}
				</div>";
			}
		}
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
		$disabled = $this->checkDisabled() ? '' : 'disabled';
		$features = __( "Speed up database loading by creating your own tables that aggregate meta information.<br>This is especially useful when you have a large number of records and for complex meta-query data calls.", "beyond-wpdb" );
?>
		<style>
			.d-block {
				display: block;
			}
			.d-inline-block {
				display: inline-block;
			}
			.d-none {
				display: none;
			}
			.table_not_exists {
				opacity:0.3;
				pointer-envet:none;
			}
		</style>
		<div class='beyond-wpdb-settings-wrap'>
			<!-- title -->
			<div style="margin-bottom: 30px;">
				<h1 style="margin-bottom: 30px;">Beyond WPDB Settings</h1>
				<p>
					<?php echo $features; ?>
				</p>
				<h2><?php echo __( "Settings", "beyond-wpdb" ) ?></h2>
			</div>
			<!-- data init -->
			<div class="data_init_section">
				<?php settings_fields( 'beyond_wpdb_group' ); ?>
				<?php do_settings_sections( 'data_init_section' ); ?>

				<!-- Submit Button -->
				<p class="submit">
					<button id="beyond-wpdb-init-btn" class="button button-primary" <?php echo $disabled; ?>>Update</button>
				</p>
			</>
			<!-- Virtual Column Settings -->
			<div>
				<?php settings_fields( 'beyond_wpdb_group' ); ?>
				<?php do_settings_sections( 'virtual_columns_section' ); ?>

				<!-- Submit Button -->
				<p class="submit">
					<button id="beyond-wpdb-virtual-columns-btn" class="button button-primary" <?php echo $disabled; ?>>Update</button>
				</p>
			</div>
			<!-- notice -->
			<div>
				<h2><?php echo __( "NOTICE", "beyond-wpdb" ) ?></h2>
				<p><?php echo __( "This plugin sets 4294967295 to group_concat_max_len when it createa an original table with JSON type column.<br>You can change the number by using filter 'beyond_group_concat_max_len'.", "beyond-wpdb" ) ?></p>
			</div>
		</div>
<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init()
	{
		global $wpdb;
		// data init
		add_settings_section(
			'setting_section_data_init',
			'<h2>' . __( "Creating and deleting custom metatables", "beyond-wpdb" ) . '</h2>',
			array( $this, 'print_data_init_section_info' ),
			'data_init_section'
		);

		add_settings_field(
			'data_init_postmeta_json',
			__( "Table for postmeta", "beyond-wpdb" ) . "<br><span style='font-size: 0.7rem; color: #808080;'>Table name: postmeta_beyond</span>",
			array( $this, 'print_data_init_field' ),
			'data_init_section',
			'setting_section_data_init',
			array(
				'id' => "data_init_{$wpdb->prefix}postmeta_beyond",
				'class' => "{$wpdb->prefix}postmeta_beyond"
			)
		);

		add_settings_field(
			'data_init_usermeta_json',
			__( "Table for usermeta", "beyond-wpdb" ) . "<br><span style='font-size: 0.7rem; color: #808080;'>Table name: usermeta_beyond</span>",
			array( $this, 'print_data_init_field' ),
			'data_init_section',
			'setting_section_data_init',
			array(
				'id' => "data_init_{$wpdb->prefix}usermeta_beyond",
				'class' => "{$wpdb->prefix}usermeta_beyond"
			)
		);

		add_settings_field(
			'data_init_commentmeta_json',
			__( "Table for commentmeta", "beyond-wpdb" ) . "<br><span style='font-size: 0.7rem; color: #808080;'>Table name: commentmeta_beyond</span>",
			array( $this, 'print_data_init_field' ),
			'data_init_section',
			'setting_section_data_init',
			array(
				'id' => "data_init_{$wpdb->prefix}commentmeta_beyond",
				'class' => "{$wpdb->prefix}commentmeta_beyond"
			)
		);

		register_setting(
			'beyond_wpdb_group',
			'beyond_wpdb_data_init_name',
			array()
		);

		// virtual columns setting
		add_settings_section(
			'virtual_column_section_id',
			'<h2>' . __( "Virtual Column Settings", "beyond-wpdb" ) . '</h2>',
			array( $this, 'print_virtual_column_section_info' ),
			'virtual_columns_section'
		);

		add_settings_field(
			'postmeta_json', // id
			__( "Table for postmeta", "beyond-wpdb" ) . "<br><span style='font-size: 0.7rem; color: #808080;'>Table name: postmeta_beyond</span>", // title
			array( $this, 'print_postmeta_json_field' ), // callback
			'virtual_columns_section', // section page
			'virtual_column_section_id', // section id,
			array(
				'id' => "virtual_column_{$wpdb->prefix}postmeta_beyond",
				'class' => "virtual_column_{$wpdb->prefix}postmeta_beyond"
			)
		);

		add_settings_field(
			'usermeta_json',
			__( "Table for usermeta", "beyond-wpdb" ) . "<br><span style='font-size: 0.7rem; color: #808080;'>Table name: usermeta_beyond</span>",
			array( $this, 'print_usermeta_json_field' ),
			'virtual_columns_section', // section page
			'virtual_column_section_id', // section id
			array(
				'id' => "virtual_column_{$wpdb->prefix}usermeta_beyond",
				'class' => "virtual_column_{$wpdb->prefix}usermeta_beyond"
			)
		);

		add_settings_field(
			'commentmeta_json', // id
			__( "Table for commentmeta", "beyond-wpdb" ) . "<br><span style='font-size: 0.7rem; color: #808080;'>Table name: commentmeta_beyond</span>", // title
			array( $this, 'print_commentmeta_json_field' ), // callback
			'virtual_columns_section', // section id
			'virtual_column_section_id',
			array(
				'id' => "virtual_column_{$wpdb->prefix}commentmeta_beyond",
				'class' => "virtual_column_{$wpdb->prefix}commentmeta_beyond"
			)
		);

		register_setting(
			'beyond_wpdb_group',
			'beyond_wpdb_virtual_column_name',
			array()
		);
	}

	/**
	 * Print the Section text
	 */
	public function print_virtual_column_section_info()
	{
		$info = __( "When you enter a key of meta information separated by a new line,<br>the specified meta key from the JSON type column is set as a virtual column and the index is pasted.<br>You can further speed up data calls by specifying the most commonly used meta keys for meta queries.", "beyond-wpdb" );
		print "<p>
			     {$info}
		       </p>";
	}

	/**
	 * print postmeta json field
	 */
	public function print_postmeta_json_field( $args )
	{
		$id = $args['id'];
		$disabled = $this->checkDisabled() ? '' : 'disabled';
		print "
		<textarea class='create_virtualColumns_text-area d-none' rows='3' cols='40' id='{$id}_textarea' name='{$id}' {$disabled}></textarea>
		<span class='create_{$id} d-none' style='margin-left: 20px;'>Processing...</span>
		<span class='data-init-input-loading' style='margin-left: 20px;'>Loading...</span>
		";

	}

	/**
	 * print usermeta json field
	 */
	public function print_usermeta_json_field( $args )
	{
		$id = $args['id'];
		$disabled = $this->checkDisabled() ? '' : 'disabled';
		print "
		<textarea class='create_virtualColumns_text-area d-none' rows='3' cols='40' id='{$id}_textarea' name='{$id}' {$disabled}></textarea>
		<span class='create_{$id} d-none' style='margin-left: 20px;'>Processing...</span>
		<span class='data-init-input-loading' style='margin-left: 20px;'>Loading...</span>
		";
	}

	/**
	 * print commentmeta json field
	 */
	public function print_commentmeta_json_field( $args )
	{
		$id = $args['id'];
		$disabled = $this->checkDisabled() ? '' : 'disabled';
		print "
		<textarea class='create_virtualColumns_text-area d-none' rows='3' cols='40' id='{$id}_textarea' name='{$id}' {$disabled}></textarea>
		<span class='create_{$id} d-none' style='margin-left: 20px;'>Processing...</span>
		<span class='data-init-input-loading' style='margin-left: 20px;'>Loading...</span>
		";
	}

	/**
	 * print data init section info
	 */
	public function print_data_init_section_info()
	{
		$info = __( "Create and delete your own tables that aggregate the metadata. (Enabled to create, disabled to delete)<br>As long as it is enabled, all meta information will continue to be automatically registered, updated, and deleted in the JSON type columns of the relevant table.<br>When enabled, a new table will be created or initialized, which will take some time. When disabled, the table will be deleted.", "beyond-wpdb" );
		print $info;
	}

	/**
	 * Check to see if each feature is available
	 * @return bool
	 */
	public function checkDisabled() {
		return ! $this->privilege_error;
	}

	/**
	 * @param $args
	 * print data_init field
	 */
	public function print_data_init_field( $args )
	{
		$id = $args['id'];
		$disabled = $this->checkDisabled() ? '' : 'disabled';
		print "
		<div class='data-init-input-radio d-none'><input type='radio' id='{$id}_active' name='name_{$id}' value='1' {$disabled}>" . "<label for='{$id}_active'>" . __( "activation", "beyond-wpdb" ) . "</label>" .
		"<input type='radio' id='{$id}_deactive' name='name_{$id}' value='0' {$disabled} style='margin-left: 25px;'>" . "<label for='{$id}_deactive'>" . __( "deactivation", "beyond-wpdb" ) . "</label>" .
		"<span class='activate_{$id} d-none' style='margin-left: 20px;'>Processing...</span>
		 <span class='success_{$id}' style='margin-left: 20px; display: none;'>Success.</span></div><span class='data-init-input-loading' style='margin-left: 20px;'>Loading...</span>
		";
	}
}

$my_settings_page = new Beyond_Wpdb_Settings_page();








