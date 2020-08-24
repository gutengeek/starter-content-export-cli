<?php

defined( 'ABSPATH' ) || exit();

final class SC_Develop {

	/**
	 * plugin version
	 *
	 * @since 0.0.1
	 */
	public $version = null;

	/**
	 * instance
	 */
	public static $instance = null;

	/**
	 * instance
	 *
	 * @since 0.0.1
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * constructor
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		$this->set_define();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * define constants
	 *
	 * @since 0.0.1
	 */
	public function set_define() {

	}

	/**
	 * include all needed files
	 */
	public function includes() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			include_once SCEC_DEV_PATH . 'inc/class-sc-cli.php';
		}

		$this->version = SCEC_DEV_VERSION;
	}

	/**
	 * register all hooks in this method
	 *
	 * @since 0.0.1
	 */
	public function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'plugin_loaded' ) );
	}

	/**
	 * plugin loaded action callback
	 *
	 * @since 0.0.1
	 */
	public function plugin_loaded() {
		// load plugin text domain
	}

}
