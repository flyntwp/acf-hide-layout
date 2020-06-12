<?php
/*
 * Plugin Name: ACF Hide Layout
 * Plugin URI: http://wordpress.org/plugins/acf-hide-layout-image-optimizer/
 * Description: This plugin allows you to hide flexible content layout on the frontend.
 * Tags: acf, advanced custom fields, flexible content, hide layout
 * Version: 1.0
 * Author: Bleech
 * Author URI: https://bleech.de
 * Text Domain: acf-hide-layout
 * Domain Path: /languages
 * License GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class ACF_Hide_Layout {

	/**
	 * The single instance of the class.
	 *
	 * @since  	1.0
	 * @access 	protected
	 */
	protected static $instance = null;

	/**
	 * Field key.
	 *
	 * @since  	1.0
	 * @access 	protected
	 */
	protected $field_key = 'acf_hide_layout';

	/**
	 * A dummy magic method to prevent class from being cloned.
	 *
	 * @since  	1.0
	 * @access 	public
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0.0' );
	}

	/**
	 * A dummy magic method to prevent class from being unserialized.
	 *
	 * @since  	1.0
	 * @access 	public
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0.0' );
	}

	/**
	 * Main instance.
	 *
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @since  	1.0
	 * @access 	public
	 *
	 * @return 	Main instance.
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
	 * @since  	1.0
	 * @access 	public
	 */
	public function __construct() {

		$this->file     =  __FILE__;
		$this->basename = plugin_basename( $this->file );

		$this->init_hooks();
	}

	/**
	 * Get the plugin url.
	 *
	 * @since  	1.0
	 * @access 	public
	 *
	 * @return 	string
	 */
	public function get_plugin_url() {
		return plugin_dir_url( $this->file );
	}

	/**
	 * Get the plugin path.
	 *
	 * @since  	1.0
	 * @access 	public
	 *
	 * @return 	string
	 */
	public function get_plugin_path() {
		return plugin_dir_path( $this->file );
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  	1.0
	 * @access 	public
	 *
	 * @return 	string
	 */
	public function get_version() {
		$plugin_data = get_file_data( $this->file, [ 'Version' => 'Version' ], 'plugin' );
		return $plugin_data['Version'];
	}

	/**
     * Get field key.
     *
     * @since   1.0
     * @access  public
	 *
     * @return  string Field key.
     */
    public function get_field_key() {
        return $this->field_key;
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since  	1.0
	 * @access 	private
	 */
	private function init_hooks() {
		add_action( 'init', [ $this, 'init' ], 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_footer', [ $this, 'admin_footer'] );
		add_filter( 'acf/update_value/type=flexible_content', [ $this, 'update_value'], 10, 4 );
	}

	/**
	 * Init when WordPress Initialises.
	 *
	 * @since  	1.0
	 * @access 	public
	 */
	public function init() {
		// Set up localisation.
		$this->load_plugin_textdomain();
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since  	1.0
	 * @access 	public
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
	 * @since  	1.0
	 * @access 	public
	 */
	public function admin_footer() {

		$args = [
			'i18n' => [
				'hide_layout' => esc_html__( 'Hide / Show Layout', 'acf-hide-layout' ),
			],
		];

		wp_localize_script( 'acf-hide-layout', 'acf_hide_layout_options', $args );
	}

	/**
	 * Load Localisation files.
	 *
	 * @since  	1.0
	 * @access 	public
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'acf-hide-layout', false, plugin_basename( dirname( $this->file ) ) . '/languages' );
	}

	/**
	 * Update the field acf_hide_layout value.
	 *
	 * @since  	1.0
	 * @access 	public
	 *
	 * @param 	mixed $rows The value to update.
	 * @param	string $post_id The post ID for this value.
	 * @param	array $field The field array.
	 * @param	mixed $original The original value before modification.
	 *
	 * @return 	mixed $rows
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

			$new_value = $row[ $field_key ];

			acf_update_value( $new_value, $post_id, $hide_layout_field );
		}

		return $rows;
	}
}

ACF_Hide_Layout::instance();
