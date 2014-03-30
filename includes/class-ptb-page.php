<?php

/**
 * Page Type Builder Page class
 */

class PTB_Page {

  /**
   * All Page Type Builder meta that exists on the page.
   *
   * @var array
   * @since 1.0
   */

  private $meta;

  /**
   * The WordPress post.
   *
   * @var object
   * @since 1.0
   */

  private $post;

  /**
   * The Page type data.
   *
   * @var object.
   * @since 1.0
   */

  private $page_type;

  /**
   * Create a new instance of the class.
   *
   * @param int $post_id
   */

  public function __construct ($post_id = 0) {
    $this->id = $post_id;
    $this->setup_post();
    $this->setup_meta();
    $this->setup_page();
  }

  /**
   * Setup page variables. This will not setup any variables from the WordPress post.
   *
   * @since 1.0
   */

  private function setup_page () {
    // Can't proceed if we haven't a post object.
    if (!$this->has_post() || !is_array($this->meta)) {
      return;
    }

    // The path to the page type file.
    $path = _ptb_get_page_type_file($this->meta['ptb_page_type']);

    // The page type object.
    $this->page_type = new PTB_Page_Type($path);

    // Can't proceed without a page type.
    if (!$this->page_type->has_name()) {
      return;
    }

    // The page type name. Example: "Standard Page".
    $this->page_type_name = $this->page_type->name;

    // The page type. Example: "PTB_Standard_Page".
    $this->page_type = $this->page_type->page_type;
  }

  /**
   * Setup post variables for the WordPress post.
   *
   * @since 1.0
   */

  private function setup_post () {
    $this->post = get_post($this->id);

    if (!isset($this->post)) {
      return;
    }
    // variables

    // create ptb_get_page_type_name function in ptb-functions.php

    foreach ($this->post as $key => $value) {
      // maybe we should remove "post_" and/or remove the variables.
      // some idéas
      // page name,
      // page content
      // page data
      // created
      // created by
      $this->$key = $value;
    }

    // utility methods
    // is logged in
    // current page url
    // access
  }

  /**
   * Setup meta variables.
   *
   * @since 1.0
   */

  private function setup_meta () {
    $this->meta = get_post_meta($this->id, PTB_META_KEY, true);

    // Meta should be an array.
    if (!is_array($this->meta)) {
      return;
    }

    // Get all property collection.
    $this->meta = array_merge($this->meta, $this->get_collection_meta());

    // Don't need this anymore.
    if (isset($this->meta[PTB_COLLECTION_KEY])) {
      unset($this->meta[PTB_COLLECTION_KEY]);
    }

    foreach ($this->meta as $key => $value) {
      if (is_array($value)) {
       $value = $this->convert($value);
      }

      $key = _ptb_remove_ptb($key);

      if (!isset($this->$key)) {
        $this->$key = $value;
      }
    }
  }

  /**
   * Get meta data from collection.
   *
   * @since 1.0
   *
   * @todo rewrite this
   *
   * @return array
   */

  private function get_collection_meta () {
    $properties = $this->meta;

    if (isset($properties[PTB_COLLECTION_KEY])) {
      $values = $properties[PTB_COLLECTION_KEY];
      $res = array();

      foreach ($values as $key => $value) {
        $key = _ptb_remove_ptb($key);
        foreach ($value as $x => $v) {
          foreach ($v as $k => $y) {
            if (_ptb_is_property_key($k)) {
              continue;
            }
            $pk = _ptb_property_type_key($k);
            $v[$k] = $this->convert(array(
              'value' => $y,
              'type' => $v[$pk]
            ));
          }
          foreach ($v as $k => $y) {
            if (_ptb_is_property_key($k)) {
              unset($v[$k]);
            }
          }
        }
        $res[$key] = $value;
      }

      return $res;
    }

    return array();
  }

  /**
   * Convert property value with the property type converter.
   *
   * @param array $property
   * @since 1.0
   *
   * @return mixed|null
   */

  private function convert ($property = array()) {
    if (is_array($property)) {
      if (isset($property['value']) && isset($property['type'])) {
        $type = $property['type'];
        $property_type = PTB_Property::factory($type);
        return $property_type->convert($property['value']);
      }
      if (isset($property['value'])) {
        return $property['value'];
      }
      return $property;
    }
    return $property;
  }

  /**
   * Check if the page has the post object and that it's not null
   *
   * @since 1.0
   *
   * @return bool
   */

  public function has_post () {
    return $this->post != null;
  }

  /**
   * Get the WordPress post object.
   *
   * @since 1.0
   *
   * @return object
   */

  public function get_post () {
    return $this->post;
  }

  /**
   * Get the Page Type Builder meta array as object.
   *
   * @since 1.0
   *
   * @return object
   */

  public function get_meta ($array = true) {
    return $array ? $this->meta : (object)$this->meta;
  }

  /**
   * Get the permalink for the page.
   *
   * @since 1.0
   *
   * @return string
   */

  public function get_permalink () {
    return get_permalink($this->id);
  }

  /**
   * Get the post status of a page.
   *
   * @since 1.0
   *
   * @return string
   */

  public function get_status () {
    return get_post_status($this->id);
  }

}