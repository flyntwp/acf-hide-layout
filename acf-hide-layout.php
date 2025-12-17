<?php
/*
 * Plugin Name: ACF Hide Layout
 * Plugin URI: https://flyntwp.com/acf-hide-layout/
 * Description: Easily hide the layout of the flexible content on the frontend but still keep it in the backend.
 * Tags: acf, advanced custom fields, flexible content, hide layout
 * Version: 1.3.0
 * Author: Bleech
 * Author URI: https://bleech.de/
 * Text Domain: acf-hide-layout
 * Domain Path: /languages
 * License GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class ACF_Hide_Layout {

	/**
	 * The single instance of the class.
	 *
	 * @since  1.0
	 * @access protected
	 */
	protected static $instance = null;

	/**
	 * Field key.
	 *
	 * @since  1.0
	 * @access protected
	 */
	protected $field_key = 'acf_hide_layout';

	/**
	 * Layouts that will be hidden.
	 *
	 * @since  1.0
	 * @access protected
	 */
	protected $hidden_layouts = [];

	/**
	 * Layouts that will be disabled.
	 *
	 * @since  1.0
	 * @access protected
	 */
	protected $disabled_layouts = [];

	/**
	 * File.
	 *
	 * @access protected
	 */
	protected $file = '';

	/**
	 * Basename.
	 *
	 * @access protected
	 */
	protected $basename = '';

	/**
	 * A dummy magic method to prevent class from being cloned.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0.0' );
	}

	/**
	 * A dummy magic method to prevent class from being unserialized.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0.0' );
	}

	/**
	 * Main instance.
	 *
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function __construct() {

		$this->file     =  __FILE__;
		$this->basename = plugin_basename( $this->file );

		$this->init_hooks();
	}

	/**
	 * Get the plugin url.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_plugin_url() {
		return plugin_dir_url( $this->file );
	}

	/**
	 * Get the plugin path.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_plugin_path() {
		return plugin_dir_path( $this->file );
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_version() {
		$plugin_data = get_file_data( $this->file, [ 'Version' => 'Version' ], 'plugin' );
		return $plugin_data['Version'];
	}

	/**
	 * Get field key.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return string Field key.
	 */
	public function get_field_key() {
		return $this->field_key;
	}

	/**
	 * Get hidden layouts.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array Hidden layouts.
	 */
	public function get_hidden_layouts() {
		return $this->hidden_layouts;
	}

	/**
	 * Set hidden layout.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  string $field_key
	 * @param  int $row
	 */
	public function set_hidden_layout( $field_key, $row ) {
		$this->hidden_layouts[ $field_key ][] = 'row-' . $row;
	}

	/**
	 * What type of request is this?
	 *
	 * Thanks WooCommerce
	 * @see https://github.com/woocommerce/woocommerce/blob/master/includes/class-woocommerce.php#L304
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 *
	 * @return bool
	 */
	public function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return wp_doing_ajax();
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || wp_doing_ajax() ) && ! defined( 'DOING_CRON' ) && ! wp_is_json_request();
		}
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since  1.0
	 * @access private
	 */
	private function init_hooks() {
		add_action( 'init', [ $this, 'init' ], 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_footer', [ $this, 'admin_footer'] );
		add_action( 'admin_notices', [ $this, 'maybe_show_admin_notice' ] );
		add_action( 'wp_ajax_acf_hide_layout_dismiss_notice', [ $this, 'ajax_dismiss_notice' ] );
		add_action( 'wp_ajax_acf_hide_layout_migrate_hidden_layouts', [ $this, 'ajax_migrate_hidden_layouts' ] );
		add_filter( 'acf/load_value/type=flexible_content', [ $this, 'load_value'], 10, 3 );
		add_filter( 'acf/update_value/type=flexible_content', [ $this, 'update_value'], 10, 4 );
		add_filter( 'acf/pre_load_metadata', [ $this, 'pre_load_metadata'], 10, 4 );
		add_filter( 'plugin_action_links_' . $this->basename, [ $this, 'plugin_action_links' ] );
	}

	/**
	 * Init when WordPress Initialises.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function init() {
		// Set up localisation.
		$this->load_plugin_textdomain();
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function enqueue_scripts() {
		$assets_url     = $this->get_plugin_url() . 'assets/';
		$plugin_version = $this->get_version();

		wp_enqueue_style( 'acf-hide-layout', $assets_url . 'css/style.css', [], $plugin_version );
		wp_enqueue_script( 'acf-hide-layout', $assets_url . 'js/script.js', ['jquery'], $plugin_version, true );
	}

	/**
	 * Add script options.
	 *
	 * admin_enqueue_scripts is to early for hidden layouts.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function admin_footer() {

		$args = [
			'supports_disabled_layouts' => $this->supports_disabled_layouts() ? 'true' : 'false',
			'hidden_layouts' => $this->get_hidden_layouts(),
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'acf-hide-layout-nonce' ),
			'i18n' => [
				'hide_layout' => esc_html__( 'Hide / Show Layout', 'acf-hide-layout' ),
				'migration_success' => _n_noop( 'Success! We migrated %s layout.', 'Success! We migrated %s layouts.', 'acf-hide-layout' ),
				'delete_plugin' => esc_html__( 'You can now delete the ACF Hide Layout plugin.', 'acf-hide-layout' ),
				'migrated' => _n_noop( 'We migrated %s layout.', 'We migrated %s layouts.', 'acf-hide-layout' ),
				'not_migrated' => _n_noop( '%s layout could not be migrated.', '%s layouts could not be migrated.', 'acf-hide-layout' ),
				'try_again' => esc_html__( 'Try Again', 'acf-hide-layout' ),
			],
		];

		wp_localize_script( 'acf-hide-layout', 'acf_hide_layout_options', $args );

		// Add modal HTML
		?>
		<div id="acf-hide-layout-modal" class="acf-hide-layout-modal" role="dialog" aria-labelledby="acf-hide-layout-modal-title" aria-modal="true" aria-hidden="true">
			<div class="acf-hide-layout-modal__backdrop" data-acf-hide-layout-modal-close></div>
			<div class="acf-hide-layout-modal__container">
				<div class="acf-hide-layout-modal__content">
					<button type="button" class="acf-hide-layout-modal__close" data-acf-hide-layout-modal-close aria-label="<?php esc_attr_e( 'Close modal', 'acf-hide-layout' ); ?>">
						<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
					</button>
					<div class="acf-hide-layout-modal__body">
						<h2 id="acf-hide-layout-modal-title" class="acf-hide-layout-modal__title"><?php esc_html_e( 'Migrate to ACF Disabled Layouts', 'acf-hide-layout' ); ?></h2>
						<div class="acf-hide-layout-modal__notice">
							<p><?php esc_html_e( 'It is advised to backup your database before proceeding.', 'acf-hide-layout' ); ?></p>
						</div>
						<div class="acf-hide-layout-modal__progress-wrapper">
							<div class="acf-hide-layout-modal__progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" aria-label="<?php esc_attr_e( 'Migration progress', 'acf-hide-layout' ); ?>">
								<div class="acf-hide-layout-modal__progress-fill" style="width: 0%"></div>
								<div class="acf-hide-layout-modal__progress-percentage" style="display: none;">0%</div>
							</div>
						</div>
						<div class="acf-hide-layout-modal__message" style="display: none;"></div>
						<div class="acf-hide-layout-modal__actions">
							<button type="button" class="button button-primary acf-hide-layout-modal__migrate-button" data-acf-hide-layout-migrate>
								<?php esc_html_e( 'Start Migration', 'acf-hide-layout' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Load Localisation files.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'acf-hide-layout', false, plugin_basename( dirname( $this->file ) ) . '/languages' );
	}

	/**
	 * Remove layouts that are hidden from frontend.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  mixed $layouts The value to preview.
	 * @param  string $post_id The post ID for this value.
	 * @param  array $field The field array.
	 *
	 * @return array $layouts
	 */
	public function load_value( $layouts, $post_id, $field ) {

		// bail early if no layouts
		if ( empty( $layouts ) ) {
			return $layouts;
		}

		// value must be an array
		$layouts = acf_get_array( $layouts );
		$field_key = $this->get_field_key();

		foreach ( $layouts as $row => $layout ) {

			$hide_layout_field = [
				'name' => "{$field['name']}_{$row}_{$field_key}",
				'key' => "field_{$field_key}",
			];

			$is_hidden = acf_get_value( $post_id, $hide_layout_field );

			if ( $is_hidden ) {
				if ( $this->supports_disabled_layouts() ) {
					$this->disabled_layouts[ $field['name'] ][] = $row;
				} else {
					// used only on admin for javascript
					$this->set_hidden_layout( $field['key'], $row );

					// hide layout on frontend
					if ( $this->is_request( 'frontend' ) ) {
						unset( $layouts[ $row ] );
					}
				}
			}
		}

		return $layouts;
	}

	/**
	 * Update the field acf_hide_layout value.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  mixed $rows The value to update.
	 * @param  string $post_id The post ID for this value.
	 * @param  array $field The field array.
	 * @param  mixed $original The original value before modification.
	 *
	 * @return mixed $rows
	 */
	public function update_value( $rows, $post_id, $field, $original ) {

		// bail early if no layouts or empty values
		if ( empty( $field['layouts'] ) || empty( $rows ) ) {
			return $rows;
		}

		unset( $rows['acfcloneindex'] );

		$rows = array_values( $rows);
		$field_key = $this->get_field_key();

		foreach ( $rows as $key => $row ) {

			// bail early if no layout reference
			if ( !is_array( $row ) || !isset( $row['acf_fc_layout'] ) ) {
				continue;
			}

			$hide_layout_field = [
				'name' => "{$field['name']}_{$key}_{$field_key}",
				'key' => "field_{$field_key}",
			];

			if ( isset( $row[ $field_key ] ) ) {
				$new_value = $row[ $field_key ];
				acf_update_value( $new_value, $post_id, $hide_layout_field );
			} else {
				acf_delete_value( $post_id, $hide_layout_field );
			}
		}

		return $rows;
	}

	/**
	 * Pre load metadata.
	 *
	 * @since  1.3
	 * @access public
	 *
	 * @param  mixed $null
	 * @param  integer|string $post_id The post id.
	 * @param  array $field The field array.
	 * @param  boolean $hidden True if we should return the reference key.
	 *
	 * @return mixed $null
	 */
	public function pre_load_metadata( $null, $post_id, $field_name, $hidden ) {
		if ( substr( $field_name, -12 ) === '_layout_meta' ) {
			$key = ltrim( substr( $field_name, 0, -12 ), '_' );

			if ( isset( $this->disabled_layouts[ $key ] ) ) {
				// Decode the $post_id for $type and $id.
				$decoded = acf_decode_post_id( $post_id );
				$id      = $decoded['id'];
				$type    = $decoded['type'];

				// Bail early if no $id (possible during new acf_form).
				if ( ! $id ) {
					return null;
				}

				$meta_instance = acf_get_meta_instance( $type );

				if ( ! $meta_instance ) {
					return null;
				}

				$meta = $meta_instance->get_value( $id, [ 'name' => $field_name ] );

				$disabled_layouts = array_unique( array_merge(
					$meta['disabled'] ?? [],
					$this->disabled_layouts[ $key ]
				) );

				$meta['disabled'] = $disabled_layouts;

				return $meta;
			}
		}

		return $null;
	}

	/**
	 * Check if a native ACF disabled layouts feature is supported.
	 *
	 * @since  1.3
	 * @access public
	 *
	 * @return boolean
	 */
	public function supports_disabled_layouts() {
		$version = defined( 'ACF_VERSION' ) ? constant( 'ACF_VERSION' ) : '0';
		return version_compare( $version, '6.5', '>=' );
	}

	/**
	 * Migrate hidden layouts.
	 *
	 * @since  1.3
	 * @access public
	 */
	public function ajax_migrate_hidden_layouts() {
		global $wpdb;

		if ( ! $this->supports_disabled_layouts() ) {
			wp_send_json_error( [
				'message' => esc_html__( 'ACF 6.5+ is required to migrate hidden layouts', 'acf-hide-layout' ),
			] );
		}

		if ( ! check_ajax_referer( 'acf-hide-layout-nonce', 'nonce', false ) ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Please refresh the page and try again.', 'acf-hide-layout' ),
			] );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => esc_html__( 'You are not allowed to migrate hidden layouts', 'acf-hide-layout' ),
			] );
		}

		$type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';
		$last_id = isset( $_POST['last_id'] ) ? intval( $_POST['last_id'] ) : 0;
		$limit = 2000;

		if ( empty( $type ) ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Type is required', 'acf-hide-layout' ),
			] );
		}

		if ( ! in_array( $type, [ 'postmeta', 'termmeta', 'usermeta', 'options' ], true ) ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Invalid type', 'acf-hide-layout' ),
			] );
		}

		$columns = [
			'postmeta' => [
				'id' => 'meta_id',
				'foreign_key' => 'post_id',
				'name' => 'meta_key',
				'value' => 'meta_value',
				'acf_id' => 'post_%d',
			],
			'termmeta' => [
				'id' => 'meta_id',
				'foreign_key' => 'term_id',
				'name' => 'meta_key',
				'value' => 'meta_value',
				'acf_id' => 'term_%d',
			],
			'usermeta' => [
				'id' => 'umeta_id',
				'foreign_key' => 'user_id',
				'name' => 'meta_key',
				'value' => 'meta_value',
				'acf_id' => 'user_%d',
			],
			'options' => [
				'id' => 'option_id',
				'foreign_key' => 'option_name',
				'name' => 'option_name',
				'value' => 'option_value',
				'acf_id' => 'options',
			],
		];

		$column = $columns[ $type ];

		$sql = $wpdb->prepare(
			"SELECT * FROM {$wpdb->$type}
			WHERE {$column['id']} > %d
			AND {$column['name']} LIKE %s
			ORDER BY {$column['id']} ASC
			LIMIT %d",
			$last_id,
			'%_' . $wpdb->esc_like( $this->get_field_key() ),
			$limit
		);

		$fields = $wpdb->get_results( $sql );

		if ( empty( $fields ) ) {
			wp_send_json_success( [
				'type' => $type,
				'last_id' => 0,
				'hidden_layouts' => [],
				'has_results' => false,
			] );
		}

		$last_field = $fields[ count( $fields ) - 1 ];
		$last_id = $last_field->{$column['id']};
		$hidden_layouts = [];

		foreach ( $fields as $field ) {
			// Get the key and value based on type
			$id = $field->{$column['id']};
			$key = $field->{$column['name']};
			$value = $field->{$column['value']};

			// Only process if value is '1' (hidden)
			if ( '1' !== $value ) {
				continue;
			}

			// Extract prefix (everything before _acf_hide_layout)
			$prefix = substr( $key, 0, strpos( $key, '_' . $this->get_field_key() ) );

			// Extract layout index (last part after _ in prefix)
			$last_underscore_pos = strrpos( $prefix, '_' );
			if ( false === $last_underscore_pos ) {
				continue;
			}

			$layout_index = substr( $prefix, $last_underscore_pos + 1 );
			$layout_name = substr( $prefix, 0, $last_underscore_pos );
			$layout_name = 'options' === $type ? preg_replace( '/^options_/', '', $layout_name ) : $layout_name;
			$acf_id = sprintf( $column['acf_id'], $field->{$column['foreign_key']} );

			$hidden_layouts[ $acf_id ][ $layout_name ][ $id ] = (int) $layout_index;
		}

		$total_migrated = 0;
		$not_migrated = [];

		foreach ( $hidden_layouts as $post_id => $layouts ) {
			foreach ( $layouts as $field_name => $indexes ) {
				$original_layout_meta = acf_get_metadata_by_field(
					$post_id,
					array(
						'name' => '_' . $field_name . '_layout_meta',
					)
				);

				if ( empty( $original_layout_meta ) || ! is_array( $original_layout_meta ) ) {
					$layout_meta = [
						'disabled' => [],
						'renamed' => [],
					];
				} else {
					$layout_meta = $original_layout_meta;
				}

				$layout_meta['disabled'] = array_unique( array_merge(
					$layout_meta['disabled'] ?? [],
					$indexes
				) );

				// If value is unchanged, consider it already migrated (success).
				if ( $layout_meta === $original_layout_meta ) {
					$total_migrated += count( $indexes );
					continue;
				}

				$is_updated = acf_update_metadata_by_field(
					$post_id,
					array(
						'name' => '_' . $field_name . '_layout_meta',
					),
					$layout_meta
				);

				if ( $is_updated ) {
					$total_migrated += count( $indexes );
				} else {
					$not_migrated = array_unique( array_merge( array_keys( $indexes ), $not_migrated ) );
					error_log( 'not_migrated: ' . $is_updated . ' - ' . $post_id . ' - ' . $field_name . ' - ' . print_r( $indexes, true ) );
				}
			}
		}

		$all_ids = array_values( array_unique( array_column( $fields, $column['id'] ) ) );
		$all_ids = array_diff( $all_ids, $not_migrated );

		$safe_ids = implode( ',', array_map( 'absint', $all_ids ) );
		$wpdb->query( "DELETE FROM {$wpdb->$type} WHERE {$column['id']} IN($safe_ids)" );

		wp_send_json_success( [
			'type' => $type,
			'last_id' => $last_id,
			'hidden_layouts' => $hidden_layouts,
			'all_ids' => $all_ids,
			'has_results' => count( $fields ) > 0,
			'rows_deleted' => $wpdb->rows_affected ?? 0,
			'total_migrated' => $total_migrated,
			'not_migrated' => count( $not_migrated ),
		] );
	}

	/**
	 * Handle AJAX request to dismiss the migration notice.
	 *
	 * @since  1.3
	 * @access public
	 */
	public function ajax_dismiss_notice() {
		check_ajax_referer( 'acf-hide-layout-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		update_option( 'acf_hide_layout_migration_notice_dismissed', true );

		wp_die();
	}

	/**
	 * Display admin notice for converting old hidden fields to new ACF disabled layouts.
	 *
	 * @since  1.3
	 * @access public
	 */
	public function maybe_show_admin_notice() {
		if ( ! $this->supports_disabled_layouts() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( get_option( 'acf_hide_layout_migration_notice_dismissed', false ) ) {
			return;
		}

		?>
		<div class="notice notice-info is-dismissible acf-hide-layout-migration-notice">
			<p>
				<strong><?php esc_html_e( 'ACF Hide Layout', 'acf-hide-layout' ); ?>:</strong>
				<?php esc_html_e( 'ACF 6.5+ now supports disabled layouts! Would you like to migrate your existing hidden layouts to the ACF disabled layouts?', 'acf-hide-layout' ); ?>
			</p>
			<p>
				<button type="button" class="button button-primary" data-acf-hide-layout-open-modal>
					<?php esc_html_e( 'Migrate to ACF Disabled Layouts', 'acf-hide-layout' ); ?>
				</button>
			</p>
		</div>
		<?php
	}

	/**
	 * Add plugin action links.
	 *
	 * @since  1.3
	 * @access public
	 *
	 * @param  array $links Plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function plugin_action_links( $links ) {
		if ( $this->supports_disabled_layouts() && current_user_can( 'manage_options' ) ) {
			$migrate_link = sprintf(
				'<button type="button" class="button-link" data-acf-hide-layout-open-modal>%s</button>',
				esc_html__( 'Migrate', 'acf-hide-layout' )
			);
			array_unshift( $links, $migrate_link );
		}

		return $links;
	}

}

ACF_Hide_Layout::instance();
