<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'SC_Posts' ) ) :

	class SC_Posts {

		protected $post_types = array( 'product' );

		protected $attachments = array();

		protected $items = array();
		protected $tmpItems = array();

		private $items_map = array();

		public function get_posts() {
			$posts = array();

			$post_types = apply_filters( 'scec_export_post_types', $this->post_types );

			$page_index = array_search( 'post', $post_types );
			if ( $page_index !== false ) {
				unset( $post_types[$page_index] );
			}

			$page_index = array_search( 'page', $post_types );
			if ( $page_index !== false ) {
				unset( $post_types[$page_index] );
			}
			if ( $post_types ) {
				$query_posts = get_posts( array(
					'post_type' => $post_types,
					'posts_per_page' => -1,
					'post_status' => 'publish'
				) );
				foreach ( $query_posts as $post ) {
					$posts[$post->post_name] = apply_filters( 'scec_export_starter_post_data', array(
						'ID' => $post->ID,
						'post_type' => $post->post_type,
						'post_title' => $post->post_title,
						'post_content' => $post->post_content,
						'post_excerpt' => $post->post_excerpt,
						'comment_status' => $post->comment_status,
						'template' => get_post_meta( $post->ID, '_wp_page_template', true ),
					), $post );
				}
			}

			return $posts;
		}

		/**
		 * get all post items
		 */
		public function get_items() {
			if ( ! $this->tmpItems && $this->items ) {
				return $this->items;
			}

			$this->items = array_merge( $this->get_posts(), $this->tmpItems );
			$igore_keys = [ '_customize_changeset_uuid', '_edit_lock' ];
			foreach ( $this->items as $key => $item ) {
				$thumb = get_post_thumbnail_id( $item['ID'] );
				if ( $thumb ) {
					$post_attachment = get_post( $thumb );

					$attach_key = 'attachment_' . get_post_field( 'post_name', $post_attachment->ID );
					$item['thumbnail'] = '{{' . $attach_key . '}}';
				}
				if ( isset( $item['post_type'] ) && $item['post_type'] !== 'product' && ! isset( $item['meta_input'] ) ) {
					$meta_input = [];
					$meta_data = get_post_meta( $item['ID'] );
					foreach ( $meta_data as $meta_key => $value ) {
						if ( ! in_array( $meta_key, $igore_keys ) ) {
							$meta_input[$meta_key] = is_array( $value ) && count( $value ) > 1 ? $value : $value[0];
						}
					}
					if ( $meta_input ) {
						$item['meta_input'] = $meta_input;
					}
				}
				$this->attachments[$attach_key] = array(
					'post_title' => $post_attachment->post_title,
					'file' => $post_attachment->guid
				);
				$this->items[$key] = $item;
			}

			return $this->items;
		}

		/**
		 * add items
		 */
		public function add_items( $items = array() ) {
			$this->tmpItems = array_merge( $this->tmpItems, $items );
			return array_merge( $this->items, $this->tmpItems );
		}

		/**
		 * get attachment by posts
		 *
		 * @param array $posts
		 */
		public function get_attachments( $posts = array() ) {
			$attachments = array();

			if ( $posts ) {
				$post_ids = array_values( array_map( function( $post ) {
					return $post['ID'];
				}, $posts ) );

				$post_attachments = new WP_Query(array(
					'posts_per_page' => -1,
					'post_type' => 'attachment',
					'post_parent__in' => $post_ids,
					'post_type' => 'attachment',
					'post_status' => 'inherit'
				));
				if ( $post_attachments->have_posts() ) {
					while ( $post_attachments->have_posts() ) {
						$post_attachments->the_post();

						$attachments[ 'attachment_' . get_post_field( 'post_name', get_the_ID() ) ] = array(
							'post_title' => get_the_title(),
							'file' => get_post_field( 'guid', get_the_ID() )
						);
					}
					wp_reset_postdata();
				}
			}

			return array_merge( $this->attachments, $attachments );
		}

		/**
		 * get options related posts
		 */
		public function get_page_options() {
			$default_page_options = apply_filters( 'scec_export_starer_content_page_options', array(
				'woocommerce_cart_page_id',
				'woocommerce_checkout_page_id',
				'woocommerce_myaccount_page_id',
				'woocommerce_shop_page_id',
				'woocommerce_terms_page_id',
			) );

			$options = array();
			foreach ( $default_page_options as $option ) {
				$page_id = get_option( $option );
				if ( $page_id ) {
					$page = get_post( $page_id );
					if ( $page && $page->post_name ) {
						$options[ $option ] = '{{' . 'post_' . $page->post_name . '}}';
					}
				}
			}
			return apply_filters( 'scec_export_starter_options_data', $options, $this );
		}

	}

endif;