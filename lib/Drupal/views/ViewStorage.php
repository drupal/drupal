<?php

/**
 * @file
 * Definition of Drupal\views\ViewStorage.
 */

namespace Drupal\views;

use Drupal\config\ConfigurableBase;

class ViewStorage extends ConfigurableBase implements ViewStorageInterface {

  /**
   * Overrides Drupal\entity\StorableInterface::id().
   */
  public function id() {
    return $this->name;
  }

  /**
   * Implements Drupal\views\ViewStorageInterface::enable().
   */
  public function enable() {
    $this->disabled = FALSE;
  }

  /**
   * Implements Drupal\views\ViewStorageInterface::disable().
   */
  public function disable() {
    $this->disabled = TRUE;
  }

  /**
   * Implements Drupal\views\ViewStorageInterface::isEnabled().
   */
  public function isEnabled() {
    return !$this->disabled;
  }

  /**
   * Add a new display handler to the view, automatically creating an id.
   *
   * @param $plugin_id
   *   The plugin type from the views plugin data. Defaults to 'page'.
   * @param $title
   *   The title of the display; optional, may be filled in from default.
   * @param $id
   *   The id to use.
   * @return
   *   The key to the display in $view->display, so that the new display
   *   can be easily located.
   */
  function add_display($plugin_id = 'page', $title = NULL, $id = NULL) {
    if (empty($plugin_id)) {
      return FALSE;
    }

    $plugin = views_fetch_plugin_data('display', $plugin_id);
    if (empty($plugin)) {
      $plugin['title'] = t('Broken');
    }


    if (empty($id)) {
      $id = $this->generate_display_id($plugin_id);

      // Generate a unique human readable name.
      // Therefore find out how often the display_plugin already got used,
      // which is stored at the end of the $id, for example page_1.
      if ($id !== 'default') {
        preg_match("/[0-9]+/", $id, $count);
        $count = $count[0];
      }
      else {
        $count = '';
      }

      if (empty($title)) {
        // If we had more then one instance already attach the count,
        // so you end up with "Page" and "Page 2" for example.
        if ($count > 1) {
          $title = $plugin['title'] . ' ' . $count;
        }
        else {
          $title = $plugin['title'];
        }
      }
    }

    $display_options = array(
      'display_plugin' => $plugin_id,
      'id' => $id,
      'display_title' => $title,
    );

    // Create the new display object
    $display = new ViewDisplay($display_options);

    // Add the new display object to the view.
    $this->display[$id] = $display;
    return $id;
  }

  /**
   * Generate a display id of a certain plugin type.
   *
   * @param $type
   *   Which plugin should be used for the new display id.
   */
  function generate_display_id($type) {
    // 'default' is singular and is unique, so just go with 'default'
    // for it. For all others, start counting.
    if ($type == 'default') {
      return 'default';
    }
    // Initial id.
    $id = $type . '_1';
    $count = 1;

    // Loop through IDs based upon our style plugin name until
    // we find one that is unused.
    while (!empty($this->display[$id])) {
      $id = $type . '_' . ++$count;
    }

    return $id;
  }

  /**
   * Generates a unique ID for an item.
   *
   * These items are typically fields, filters, sort criteria, or arguments.
   *
   * @param $requested_id
   *   The requested ID for the item.
   * @param $existing_items
   *   An array of existing items, keyed by their IDs.
   *
   * @return
   *   A unique ID. This will be equal to $requested_id if no item with that ID
   *   already exists. Otherwise, it will be appended with an integer to make
   *   it unique, e.g. "{$requested_id}_1", "{$requested_id}_2", etc.
   */
  public static function generate_item_id($requested_id, $existing_items) {
    $count = 0;
    $id = $requested_id;
    while (!empty($existing_items[$id])) {
      $id = $requested_id . '_' . ++$count;
    }
    return $id;
  }

  /**
   * Create a new display and a display handler for it.
   * @param $type
   *   The plugin type from the views plugin data. Defaults to 'page'.
   * @param $title
   *   The title of the display; optional, may be filled in from default.
   * @param $id
   *   The id to use.
   * @return views_plugin_display
   *   A reference to the new handler object.
   */
  function &new_display($type = 'page', $title = NULL, $id = NULL) {
    $id = $this->add_display($type, $title, $id);

    // Create a handler
    $this->display[$id]->handler = views_get_plugin('display', $this->display[$id]->display_plugin);
    if (empty($this->display[$id]->handler)) {
      // provide a 'default' handler as an emergency. This won't work well but
      // it will keep things from crashing.
      $this->display[$id]->handler = views_get_plugin('display', 'default');
    }

    if (!empty($this->display[$id]->handler)) {
      // Initialize the new display handler with data.
      $this->display[$id]->handler->init($this, $this->display[$id]);
      // If this is NOT the default display handler, let it know which is
      if ($id != 'default') {
        $this->display[$id]->handler->default_display = &$this->display['default']->handler;
      }
    }

    return $this->display[$id]->handler;
  }

  /**
   * Add an item with a handler to the view.
   *
   * These items may be fields, filters, sort criteria, or arguments.
   */
  function add_item($display_id, $type, $table, $field, $options = array(), $id = NULL) {
    $types = View::views_object_types();
    $this->set_display($display_id);

    $fields = $this->display[$display_id]->handler->get_option($types[$type]['plural']);

    if (empty($id)) {
      $id = $this->generate_item_id($field, $fields);
    }

    $new_item = array(
      'id' => $id,
      'table' => $table,
      'field' => $field,
    ) + $options;

    if (!empty($types[$type]['type'])) {
      $handler_type = $types[$type]['type'];
    }
    else {
      $handler_type = $type;
    }

    $handler = views_get_handler($table, $field, $handler_type);

    $fields[$id] = $new_item;
    $this->display[$display_id]->handler->set_option($types[$type]['plural'], $fields);

    return $id;
  }

  /**
   * Get an array of items for the current display.
   */
  function get_items($type, $display_id = NULL) {
    $this->set_display($display_id);

    if (!isset($display_id)) {
      $display_id = $this->current_display;
    }

    // Get info about the types so we can get the right data.
    $types = View::views_object_types();
    return $this->display[$display_id]->handler->get_option($types[$type]['plural']);
  }

  /**
   * Get the configuration of an item (field/sort/filter/etc) on a given
   * display.
   */
  function get_item($display_id, $type, $id) {
    // Get info about the types so we can get the right data.
    $types = View::views_object_types();
    // Initialize the display
    $this->set_display($display_id);

    // Get the existing configuration
    $fields = $this->display[$display_id]->handler->get_option($types[$type]['plural']);

    return isset($fields[$id]) ? $fields[$id] : NULL;
  }

  /**
   * Set the configuration of an item (field/sort/filter/etc) on a given
   * display.
   *
   * Pass in NULL for the $item to remove an item.
   */
  function set_item($display_id, $type, $id, $item) {
    // Get info about the types so we can get the right data.
    $types = View::views_object_types();
    // Initialize the display
    $this->set_display($display_id);

    // Get the existing configuration
    $fields = $this->display[$display_id]->handler->get_option($types[$type]['plural']);
    if (isset($item)) {
      $fields[$id] = $item;
    }
    else {
      unset($fields[$id]);
    }

    // Store.
    $this->display[$display_id]->handler->set_option($types[$type]['plural'], $fields);
  }

  /**
   * Set an option on an item.
   *
   * Use this only if you have just 1 or 2 options to set; if you have
   * many, consider getting the item, adding the options and doing
   * set_item yourself.
   */
  function set_item_option($display_id, $type, $id, $option, $value) {
    $item = $this->get_item($display_id, $type, $id);
    $item[$option] = $value;
    $this->set_item($display_id, $type, $id, $item);
  }

}
