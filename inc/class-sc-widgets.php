<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'SC_Widgets' ) ) :

	class SC_Widgets {

		protected $items = array();

		private $items_map = array();

		function get_all_widgets_data( $sidebar_id = null ) {
		    $result = [];
		    $sidebars_widgets = get_option( 'sidebars_widgets' );

		    if ( is_array( $sidebars_widgets ) ) {
		        foreach ( $sidebars_widgets as $key => $value ) {
		        	$widget_added = [];
		            if( is_array( $value ) ) {
		                foreach ( $value as $widget_id ) {
		                    $pieces = explode( '-', $widget_id );
		                    $multi_number = array_pop( $pieces );
		                    $id_base = implode('-', $pieces);
		                    $widget_data = get_option( 'widget_' . $id_base );

		                    // Remove inactive widgets
		                    if( $key !== 'wp_inactive_widgets' && ! in_array( $id_base, $widget_added ) ) {
		                    	$widget_added[] = $id_base;
		                    	if ( ! isset( $result[$key] ) ) {
		                    		$result[$key] = array();
		                    	}
		                        unset( $widget_data['_multiwidget'] );
		                        foreach ( $widget_data as $widget ) {
		                        	if ( $widget ) {
		                        		$result[$key][] = array( $id_base, $widget );
		                        	}
		                        }
		                    }
		                }
		            }
		        }
		    }
		    if ( $sidebar_id ) {
		        return isset( $result[$sidebar_id] ) ? $result[ $sidebar_id ] : [];
		    }
		    return $result;
		}

		/**
		 * get all post items
		 */
		public function get_items() {
			if ( $this->items ) {
				return $this->items;
			}

			return $this->get_all_widgets_data();
		}

		public function add_items( $items = array() ) {
			$this->items = array_merge( $this->items, $items );
			return $this->items;
		}

	}

endif;