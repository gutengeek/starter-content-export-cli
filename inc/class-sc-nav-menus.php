<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'SC_Nav_Menus' ) ) :

	class SC_Nav_Menus {

		protected $posts;

		protected $menus;

		private $items_map = array();

		public function get_menus() {
			$menus = array();
			$menu_locations = get_nav_menu_locations();
			$nav_menus = wp_get_nav_menus();

			foreach ( $nav_menus as $menu ) {
				$item = array(
					'term_id' => $menu->term_id,
					'name' => $menu->name,
					'items' => array()
				);
				$menu_items = wp_get_nav_menu_items( $menu );

				$temp_menus_mapping_items = array();
				foreach ( $menu_items as $index => $menu_item ) {
					$post = get_post( $menu_item->object_id );
					$post_key = 'nav_menu_' . $menu->slug . '_item_' . $post->post_name;

					// save items;
					$args = array(
						'type' => $menu_item->type,
						'object' => $menu_item->object,
						'menu_item_parent' => absint( $menu_item->menu_item_parent ),
						'object_id' => '{{' . $post_key . '}}',
						'title' => $menu_item->title
					);

					if ( $menu_item->type === 'custom' ) {
						$args = array_merge( $args, array(
							'url' => $menu_item->url
						) );
						unset( $args['object_id'] );
						unset( $args['object'] );
					}

					$nav_menu_index = $post_key;
					$item['items'][$nav_menu_index] = $args;

					// save mapping tmp
					$temp_menus_mapping_items[ $menu_item->ID ] = $nav_menu_index;

					if ( in_array( $menu_item->type, array( 'custom', 'post_type' ) ) && $menu_item->object_id ) {
						$post = get_post( $menu_item->object_id );

						// is blog
						$post_key = 'post_' . $post->post_name;
						if ( $menu_item->object_id == get_option( 'page_for_posts' ) ) {
							$post_key = 'blog';
						} else if ( $menu_item->object_id == get_option( 'page_on_front' ) && get_option( 'show_on_front' ) === 'page' ) {
							$post_key = 'homepage';
						}
						$post_args = array(
							'ID' => $post->ID,
							'post_type' => $post->post_type,
							'post_excerpt' => $post->post_excerpt,
							'post_title' => $post->post_title,
							'post_content' => $post->post_content
						);
						// update object_id
						if ( $menu_item->type !== 'custom' ) {
							$item['items'][$nav_menu_index]['object_id'] = '{{' . $post_key . '}}';
						} else {
							// menu item type 'custom' could not have 'object_id' key
							// $item['items'][$nav_menu_index]['object_id'] = '{{' . $post_key . '}}';
							$post_args['meta_input'] = [
								'_hi_starter_content_nav_menu_symbol' => $nav_menu_index,
								// '_menu_item_object_id' => '{{'.$post_key.'}}',
								'_menu_item_classes' => get_post_meta( $post->ID, '_menu_item_classes', true ),
								'_menu_item_url' => get_post_meta( $post->ID, '_menu_item_classes', true ),
								'_menu_item_xfn' => get_post_meta( $post->ID, '_menu_item_xfn', true ),
							];
						}
						// storage posts
						$this->posts[ $post_key ] = $post_args;
					}
				}

				// mapping menu item parent
				foreach ( $item['items'] as $index_key => $newItem ) {
					$old_parent_id = $newItem['menu_item_parent'];
					if ( isset( $temp_menus_mapping_items[$old_parent_id] ) ) {
						$newItem['menu_item_parent'] = '{{'. $temp_menus_mapping_items[$old_parent_id] . '}}';
					}

					$newItem['key'] = $index_key;
					$item['items'][$index_key] = $newItem;
				}

				$menu_key = $menu->slug;
				$menus[$menu_key] = $item;
			}

			// loop menus created and pass its to location
			foreach ( $menus as $menu_key => $menu_data ) {
				$find = false;
				foreach ( $menu_locations as $name => $nav_menu_id ) {
					if ( isset( $menu_data['term_id'] ) && $nav_menu_id == $menu_data['term_id'] ) {
						unset( $menus[$menu_key], $menu_data['term_id'] );
						// assign menu to location
						$menus[$name] = $menu_data;
						$find = true;
						break;
					}
				}
				if ( ! $find ) {
					unset( $menu_data['term_id'] );
					$menus[$menu_key] = $menu_data;
				}
			}

			return $menus;
		}

		public function get_items() {
			if ( $this->menus ) {
				return $this->menus;
			}

			return $this->menus = $this->get_menus();
		}

		public function get_posts() {
			if ( $this->posts ) {
				$menu_items = $this->get_items();
			}
			return $this->posts;
		}

	}

endif;