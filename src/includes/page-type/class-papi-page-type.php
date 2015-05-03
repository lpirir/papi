<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Papi Page Type class.
 * All page types in the WordPress theme will extend this class.
 *
 * @package Papi
 * @since 1.0.0
 */

class Papi_Page_Type extends Papi_Page_Type_Meta {

	/**
	 * The array of meta boxes to register.
	 *
	 * @var array
	 * @since 1.0.0
	 */

	private $boxes = array();

	/**
	 * Load all boxes even if we aren't on a post type.
	 *
	 * @var bool
	 * @since 1.0.0
	 */

	private $load_boxes = false;

	/**
	 * Array of post type supports to remove.
	 * By default remove `postcustom` which is the Custom fields metabox.
	 *
	 * @var array
	 * @since 1.0.0
	 */

	private $post_type_supports = array( 'custom-fields' );

	/**
	 * Remove meta boxes.
	 *
	 * @var array
	 * @since 1.2.0
	 */

	private $remove_meta_boxes = array();

	/**
	 * Load a page type by the file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file_path
	 */

	public function __construct( $file_path = '' ) {
		parent::__construct( $file_path );
	}

	/**
	 * Add new meta box with properties.
	 *
	 * @param mixed $file_or_options
	 * @param array $properties
	 *
	 * @since 1.0.0
	 */

	protected function box( $file_or_options = array(), $properties = array() ) {
		if ( is_object( $file_or_options ) ) {
			$file_or_options = (array) $file_or_options;
		}

		if ( ! is_string( $file_or_options ) && ! is_array( $file_or_options ) ) {
			return;
		}

		list( $options, $properties ) = papi_get_options_and_properties( $file_or_options, $properties, true );

		$post_type = papi_get_wp_post_type();

		// Check so we have a post the to add the box to.
		if ( ! $this->load_boxes && ( empty( $post_type ) || ! $this->has_post_type( $post_type ) ) ) {
			return;
		}

		// Add post type to the options array.
		$options['post_type'] = $post_type;

		if ( isset( $options['sort_order'] ) ) {
			$sort_order = intval( $options['sort_order'] );
		} else {
			$sort_order = papi_filter_settings_sort_order();
		}

		if ( is_callable( $properties ) ) {
			$properties = call_user_func( $properties );
		}

		// Check and convert all non properties objects to properties objects.
		$properties = $this->convert_properties( $properties );

		$options['title'] = papi_esc_html( isset( $options['title'] ) ? $options['title'] : '' );

		array_push( $this->boxes, array( $options, $properties, 'sort_order' => $sort_order, 'title' => $options['title'] ) );
	}

	/**
	 * Convert properties to properties objects.
	 *
	 * @param array $properties
	 * @since 1.3.0
	 *
	 * @return array
	 */

	private function convert_properties( $properties ) {
		if ( empty( $properties ) ) {
			return array();
		}

		$properties = array_map( function ( $property ) {
			return papi_property( $property );
		}, $properties );

		return $this->convert_child_properties( $properties );
	}

	/**
	 * Fix child properties so you can skip `papi_property`
	 * it in for example `settings->items`.
	 *
	 * @param array $properties
	 * @since 1.3.0
	 *
	 * @return array
	 */

	private function convert_child_properties( $properties ) {
		for ( $i = 0, $l = count( $properties ); $i < $l; $i++ ) {
			if ( ! isset( $properties[$i]->settings ) ) {
				continue;
			}

			$arr = (array) $properties[$i]->settings;

			foreach ( $arr as $key => $value ) {
				if ( ! is_array( $value ) ) {
					continue;
				}

				$arr[$key] = $this->convert_items_array( $value );
			}

			$properties[$i]->settings = (object) $arr;
		}

		return $properties;
	}

	/**
	 * Convert all arrays that has a valid property type.
	 *
	 * @param array $items
	 * @since 1.3.0
	 *
	 * @return array
	 */

	private function convert_items_array( $items ) {
		for ( $j = 0, $k = count( $items ); $j < $k; $j++ ) {
			if ( is_object( $items[$j] ) ) {
				continue;
			}

			if ( ! isset( $items[$j]['type'] ) ) {
				foreach ( $items[$j] as $key => $value ) {
					if ( is_array( $items[$j][$key] ) ) {
						$items[$j][$key] = $this->convert_items_array( $items[$j][$key] );
					}
				}

				continue;
			}

			$type = papi_get_property_class_name( $items[$j]['type'] );

			if ( ! class_exists( $type ) ) {
				continue;
			}

			$items[$j] = papi_property( $items[$j] );
		}

		return $items;
	}

	/**
	 * Get boxes from the page type.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */

	public function get_boxes() {
		if ( empty( $this->boxes ) ) {
			if ( ! method_exists( $this, 'register' ) ) {
				return;
			}

			$this->load_boxes = true;

			$this->register();
		}

		return $this->boxes;
	}

	/**
	 * This function will setup all meta boxes.
	 *
	 * @since 1.0.0
	 */

	public function setup() {
		if ( ! method_exists( $this, 'register' ) ) {
			return;
		}

		// 1. Run the register method.
		$this->register();

		// 2. Remove post type support
		$this->remove_post_type_support();

		// 3. Load all boxes.
		$this->boxes = papi_sort_order( array_reverse( $this->boxes ) );

		foreach ( $this->boxes as $box ) {
			new Papi_Admin_Meta_Box( $box[0], $box[1] );
		}
	}

	/**
	 * Add new property to the page using array or rendering property template file.
	 *
	 * @param string|array $file_or_options
	 * @param array $values
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */

	protected function property( $file_or_options = array(), $values = array() ) {
		return papi_property( $file_or_options, $values );
	}

	/**
	 * Remove post type support. Runs once, on page load.
	 *
	 * @param array $post_type_supports
	 *
	 * @since 1.0.0
	 */

	protected function remove( $post_type_supports = array() ) {
		$this->post_type_supports = array_merge( $this->post_type_supports, papi_to_array( $post_type_supports ) );
	}

	/**
	 * Remove post type support action.
	 *
	 * @since 1.0.0
	 */

	public function remove_post_type_support() {
		global $_wp_post_type_features;

		$post_type = papi_get_wp_post_type();

		if ( empty( $post_type ) ) {
			return;
		}

		foreach ( $this->post_type_supports as $key => $value ) {
			if ( is_numeric( $key ) ) {
				$key = $value;
				$value = '';
			}

			if ( isset( $_wp_post_type_features[$post_type] ) && isset( $_wp_post_type_features[$post_type][$key] ) ) {
				unset( $_wp_post_type_features[$post_type][$key] );
				continue;
			}

			// Add non post type support to remove meta boxes array.
			if ( empty( $value ) ) {
				$value = 'normal';
			}

			$this->remove_meta_boxes[] = array( $key, $value );
		}

		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 999 );
	}

	/**
	 * Remove meta boxes.
	 *
	 * @since 1.2.0
	 */

	public function remove_meta_boxes() {
		$post_type = papi_get_wp_post_type();

		if ( empty( $post_type ) ) {
			return;
		}

		foreach ( $this->remove_meta_boxes as $item ) {
			remove_meta_box( $item[0], $post_type, $item[1] );
		}
	}

	/**
	 * Add a new tab.
	 *
	 * @param mixed $file_or_options
	 * @param array $properties
	 *
	 * @since 1.0
	 *
	 * @return array
	 */

	protected function tab( $file_or_options = array(), $properties = array() ) {
		if ( ! is_string( $file_or_options ) && ! is_array( $file_or_options ) ) {
			return;
		}

		$tab = papi_tab( $file_or_options, $properties );

		// Tabs sometimes will be in $tab->options['options'] when you use a tab template in this method
		// and using the return value of papi_tab function is used.
		//
		// This should be fixed later, not a priority for now since this works.
		if ( is_object( $tab ) && isset( $tab->options ) && isset( $tab->options['options'] ) ) {
			$tab = (object) $tab->options;
		}

		if ( isset( $tab->options ) ) {
			$tab->options = papi_esc_html( $tab->options );
		}

		return $tab;
	}

	/**
	 * Load template file.
	 *
	 * @param string $file
	 * @param array $values
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */

	protected function template( $file, $values = array() ) {
		return papi_template( $file, $values );
	}
}
