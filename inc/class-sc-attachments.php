<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'SC_Attachments' ) ) :

	class SC_Attachments {

		protected $items = array();

		private $items_map = array();

		public function get_attachments() {
			$posts = array();
			return $posts;
		}

		/**
		 * get all post items
		 */
		public function get_items() {
			if ( $this->items ) {
				return $this->items;
			}

			return $this->get_attachments();
		}

		/**
		 * get attachment by posts
		 *
		 * @param array $posts
		 */
		public function add_items( $items = array() ) {
			$this->items = array_merge( $this->items, $items );
			return $this->items;
		}

	}

endif;