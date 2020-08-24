<?php

defined( 'ABSPATH' ) || exit();

class SC_Cli {

	protected $commands = [];

	/**
	 * Load required files and hooks to make the CLI work.
	 */
	public function __construct() {
		$this->includes();
		$this->hooks();
	}

	/**
	 * Load command files.
	 */
	private function includes() {
		$this->commands[] = require_once dirname( __FILE__ ) . '/cli/class-wp-cli-data-command.php';

		require_once dirname( __FILE__ ) . '/class-sc-nav-menus.php';
		require_once dirname( __FILE__ ) . '/class-sc-posts.php';
		require_once dirname( __FILE__ ) . '/class-sc-attachments.php';
		require_once dirname( __FILE__ ) . '/class-sc-widgets.php';
	}

	/**
	 * Sets up and hooks WP CLI to our CLI code.
	 */
	private function hooks() {
		foreach ( $this->commands as $command ) {
			$before_invoke = method_exists( $command, 'before_invoke' );
			$args = array(
				'before_invoke' => call_user_func_array( array( $command, 'before_invoke' ), array() )
			);
			WP_CLI::add_command( $command->command_name, $command, $args );
		}
	}
}

new SC_Cli();