<?php
/*
 * Plugin Name: ACF Hide Layout
 * Plugin URI: https://flyntwp.com/acf-hide-layout/
 * Description: Easily hide the layout of the flexible content on the frontend but still keep it in the backend.
 * Tags: acf, advanced custom fields, flexible content, hide layout
 * Version: 1.2.1
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
		add_filter( 'acf/load_value/type=flexible_content', [ $this, 'load_value'], 10, 3 );
		add_filter( 'acf/update_value/type=flexible_content', [ $this, 'update_value'], 10, 4 );
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
			'hidden_layouts' => $this->get_hidden_layouts(),
			'i18n' => [
				'hide_layout' => esc_html__( 'Hide / Show Layout', 'acf-hide-layout' ),
			],
		];

		wp_localize_script( 'acf-hide-layout', 'acf_hide_layout_options', $args );
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
				// used only on admin for javascript
				$this->set_hidden_layout( $field['key'], $row );

				// hide layout on frontend
				if ( $this->is_request( 'frontend' ) ) {
					unset( $layouts[ $row ] );
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
			if ( !is_array( $row ) || !isset( $row['acf_fc_layout'] ) || !isset( $row[ $field_key ] ) ) {
				continue;
			}

			$hide_layout_field = [
				'name' => "{$field['name']}_{$key}_{$field_key}",
				'key' => "field_{$field_key}",
			];

			$new_value = $row[ $field_key ];

			acf_update_value( $new_value, $post_id, $hide_layout_field );
		}

		return $rows;
	}
}

ACF_Hide_Layout::instance();
