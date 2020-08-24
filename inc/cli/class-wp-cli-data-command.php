<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'SC_Cli_Data_Command' ) ):

	class SC_Cli_Data_Command {

		public $command_name = 'starter-content';

		private $posts;
		private $nav_menus;
		private $options;
		private $widgets;

		public function __construct() {
			$this->theme = wp_get_theme();
			$this->template_dir = get_template_directory();
			$this->template_url = get_template_directory_uri();
			$this->version = $this->theme->get( 'Version' );
			$this->name = $this->theme->get( 'Name' );
			$this->starter_dir = $this->template_dir . '/inc/starter-content';
			$this->image_dir = $this->template_dir . '/assets/images/starter-content';
		}

		/**
	     * Export WP sample for starter content
	     *
	     * ## EXAMPLES
	     *
	     *     wp starter-content export --theme-mod-prefix=higutengeek
	     *
	     * @when after_wp_load
	     */
	    function export( $args, $assoc_args ) {
	    	$prefix = isset( $assoc_args['theme-mod-prefix'] ) ? $assoc_args['theme-mod-prefix'] : '';
	    	$starter_keys = array( 'posts', 'attachments', 'nav_menus', 'options', 'widgets', 'theme_mods' );
	    	$posts = array();
	    	$attachments = array();
	    	$options = array();
	    	$widgets = array();

	    	// theme mods
	    	$theme_mods = array();
	    	if ( $prefix ) {
		    	foreach ( get_theme_mods() as $name => $value ) {
		    		if ( strpos( $name, $prefix ) !== false ) {
		    			$theme_mods[$name] = $value;
		    		}
		    	}
	    	} else {
	    		$theme_mods = get_theme_mods();
	    	}

	    	if ( $theme_mods ) {
				$this->write_file( 'theme_mods', $theme_mods );
	    	}

	    	// nav_menus
	    	if ( $this->nav_menus ) {
	    		$this->write_file( 'nav_menus', $this->nav_menus->get_items() );
	    		$posts = array_merge( $posts, $this->nav_menus->get_posts() );
	    	}

	    	// widgets
	    	if ( $this->widgets ) {
	    		$this->write_file( 'widgets', $this->widgets->get_items() );
	    	}

	    	// posts
	    	if ( $this->posts ) {
	    		$blog_page_id = get_option( 'page_for_posts' );
	    		$home_page_id = get_option( 'page_on_front' );

	    		$found_home = false;
	    		$found_blog = false;
    			foreach ( $posts as $key => $post ) {
    				if ( get_option( 'show_on_front' ) === 'page' ) {
	    				if ( isset( $post['ID'] ) ) {
	    					if ( absint( $post['ID'] ) == absint( $home_page_id ) ) {
	    						$found_home = $home_page_id;
	    					} else if ( $post['ID'] == $blog_page_id ) {
	    						$found_blog = $blog_page_id;
	    					}
	    				}
		    		}
    				// unset( $post['ID'] );
    				$posts[$key] = $post;
    			}

    			if ( get_option( 'show_on_front' ) === 'page' ) {
    				$found_home = $found_home ? get_post( $found_home ) : get_post( $home_page_id );
    				$posts['homepage'] = array(
    					'ID' => $home_page_id,
    					'post_title' => $found_home->post_title,
    					'post_type' => $found_home->post_type,
    					'post_content' => $found_home->post_content,
    					'template' => get_post_meta( $home_page_id, '_wp_page_template', true )
    				);
    				$options['show_on_front'] = 'page';
    				$options['page_on_front'] = '{{homepage}}';
    			}

    			if ( $blog_page_id && $found_blog ) {
    				$found_blog = get_post( $blog_page_id );
    				$posts['blog'] = array(
    					'ID' => $blog_page_id,
    					'post_title' => $found_blog->post_title,
    					'post_type' => $found_blog->post_type,
    					'post_content' => $found_blog->post_content
    				);
    				$options['page_for_posts'] = '{{blog}}';
    			}

	    		$this->posts->add_items( $posts );
	    		$posts = $this->posts->get_items();
		    	// get attachments
		    	$attachments = array_merge( $attachments, $this->posts->get_attachments( $posts ) );

	    		foreach ( $posts as $key => $post ) {
	    			unset( $post['ID'] );
	    			$posts[$key] = $post;
	    		}
	    		$this->write_file( 'posts', $posts );

	    		// page options
	    		$options = array_merge( $options, $this->posts->get_page_options() );
	    	}

	    	// attachments
	    	if ( $this->attachments ) {
	    		$this->attachments->add_items( $attachments );
	    		$attachment_items = $this->attachments->get_items();
	    		foreach ( $attachment_items as $key => $item ) {
	    			$url = $this->download_image_from_url( $item['file'] );
	    			$item['file'] = str_replace( $this->template_url . '/', '', $url );
	    			$attachment_items[$key] = $item;
	    		}

	    		$this->write_file( 'attachments', $attachment_items );
	    	}

	    	if ( $options ) {
	    		$this->write_file( 'options', $options );
	    	}
	    }

	    /**
	     * var export format
	     *
	     * @param $expression
	     * @param $return
	     * @return string
	     */
	    function var_export( $expression, $return = false ) {
		    $export = var_export( $expression, true );
		    $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
		    $array = preg_split("/\r\n|\n|\r/", $export);
		    $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [NULL, ']$1', ' => ['], $array);
		    $export = join(PHP_EOL, array_filter(["["] + $array));
		    if ((bool)$return) return $export; else echo $export;
		}

	    /**
	     * write file content
	     *
	     * @param string $type
	     * @oaram array $items
	     */
	    public function write_file( $type = '', $items = array() ) {
	    	// make dir if its not exists
			if ( ! is_dir( $this->starter_dir ) ) {
				wp_mkdir_p( $this->starter_dir );
			}

	    	$header = "/**\r * Starter Content for {{KEY}}\r *\r * @package $this->name\r * @since $this->version\r */\r";
	    	$file_header = str_replace( '{{KEY}}', $type, $header );

	    	global $wp_filesystem;
	    	ob_start();
			$this->var_export( $items );
			$content = sprintf( "<?php\r%s\rreturn %s;", $file_header, ob_get_clean() );
	    	return $wp_filesystem->put_contents( $this->starter_dir . '/' . $type . '.php', $content, FS_CHMOD_FILE );
	    }

	    /**
	     * before invoke init thirdparty
	     *
	     * @since 0.0.1
	     */
	    public function before_invoke() {
	    	global $wp_filesystem;
	    	require_once ( ABSPATH . '/wp-admin/includes/file.php' );
    		WP_Filesystem();

	        $this->nav_menus = new SC_Nav_Menus();
	        $this->posts = new SC_Posts();
	        $this->attachments = new SC_Attachments();
	        $this->widgets = new SC_Widgets();
	    }

	    /**
	     * download image from url
	     */
	    public function download_image_from_url( $image_url = '' ) {
	    	// make dir if its not exists
			if ( ! is_dir( $this->image_dir ) ) {
				wp_mkdir_p( $this->image_dir );
			}

			$parsed_url = wp_parse_url( $image_url );

			// Check parsed URL.
			if ( ! $parsed_url || ! is_array( $parsed_url ) ) {
				/* translators: %s: image URL */
				return new WP_Error( 'sc_develop_rest_invalid_image_url', sprintf( __( 'Invalid URL %s.' ), $image_url ), array( 'status' => 400 ) );
			}

			// Ensure url is valid.
			$image_url = esc_url_raw( $image_url );

			// download_url function is part of wp-admin.
			if ( ! function_exists( 'download_url' ) ) {
				include_once ABSPATH . 'wp-admin/includes/file.php';
			}

			$file_array         = array();
			$file_array['name'] = basename( current( explode( '?', $image_url ) ) );

			// Download file to temp location.
			$file_array['tmp_name'] = download_url( $image_url );

			// If error storing temporarily, return the error.
			if ( is_wp_error( $file_array['tmp_name'] ) ) {
				return new WP_Error(
					'sc_develop_rest_invalid_remote_image_url',
					/* translators: %s: image URL */
					sprintf( __( 'Error getting remote image %s.' ), $image_url ) . ' '
					/* translators: %s: error message */
					. sprintf( __( 'Error: %s' ), $file_array['tmp_name']->get_error_message() ),
					array( 'status' => 400 )
				);
			}

			copy( $file_array['tmp_name'], $this->template_dir . '/assets/images/starter-content/' . $file_array['name'] );
			@unlink( $file_array['tmp_name'] );
			return $this->template_url . '/assets/images/starter-content/' . $file_array['name'];
		}

	}

endif;

return new SC_Cli_Data_Command();