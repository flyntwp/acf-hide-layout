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
	 * Hook into actions and filters.
	 *
	 * @since  	1.0
	 * @access 	private
	 */
	private function init_hooks() {
		add_action( 'init', [ $this, 'init' ], 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
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
	 * Load Localisation files.
	 *
	 * @since  	1.0
	 * @access 	public
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'acf-hide-layout', false, plugin_basename( dirname( $this->file ) ) . '/languages' );
	}
}

ACF_Hide_Layout::instance();
