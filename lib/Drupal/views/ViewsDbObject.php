<?php

/**
 * @file
 * Definition of Drupal\views\ViewsDbObject.
 */

namespace Drupal\views;

/**
 * Base class for views' database objects.
 */
class ViewsDbObject {

  public $db_table;

  /**
   * Initialize this object, setting values from schema defaults.
   *
   * @param $init
   *   If an array, this is a set of values from db_fetch_object to
   *   load. Otherwse, if TRUE values will be filled in from schema
   *   defaults.
   */
  function init($init = TRUE) {
    if (is_array($init)) {
      return $this->load_row($init);
    }

    if (!$init) {
      return;
    }

    $schema = drupal_get_schema($this->db_table);

    if (!$schema) {
      return;
    }

    // Go through our schema and build correlations.
    foreach ($schema['fields'] as $field => $info) {
      if ($info['type'] == 'serial') {
        $this->$field = NULL;
      }
      if (!isset($this->$field)) {
        if (!empty($info['serialize']) && isset($info['serialized default'])) {
          $this->$field = unserialize($info['serialized default']);
        }
        elseif (isset($info['default'])) {
          $this->$field = $info['default'];
        }
        else {
          $this->$field = '';
        }
      }
    }
  }

  /**
   * Write the row to the database.
   *
   * @param $update
   *   If true this will be an UPDATE query. Otherwise it will be an INSERT.
   */
  function save_row($update = NULL) {
    $fields = $defs = $values = $serials = array();
    $schema = drupal_get_schema($this->db_table);

    // Go through our schema and build correlations.
    foreach ($schema['fields'] as $field => $info) {
      // special case -- skip serial types if we are updating.
      if ($info['type'] == 'serial') {
        $serials[] = $field;
        continue;
      }
      elseif ($info['type'] == 'int') {
        $this->$field = (int) $this->$field;
      }
      $fields[$field] = empty($info['serialize']) ? $this->$field : serialize($this->$field);
    }
    if (!$update) {
      $query = db_insert($this->db_table);
    }
    else {
      $query = db_update($this->db_table)
        ->condition($update, $this->$update);
    }
    $return = $query
      ->fields($fields)
      ->execute();

    if ($serials && !$update) {
      // get last insert ids and fill them in.
      // Well, one ID.
      foreach ($serials as $field) {
        $this->$field = $return;
      }
    }
  }

  /**
   * Load the object with a row from the database.
   *
   * This method is separate from the constructor in order to give us
   * more flexibility in terms of how the view object is built in different
   * contexts.
   *
   * @param $data
   *   An object from db_fetch_object. It should contain all of the fields
   *   that are in the schema.
   */
  function load_row($data) {
    $schema = drupal_get_schema($this->db_table);

    // Go through our schema and build correlations.
    foreach ($schema['fields'] as $field => $info) {
      $this->$field = empty($info['serialize']) ? $data->$field : unserialize($data->$field);
    }
  }

  /**
   * Export a loaded row, such as an argument, field or the view itself to PHP code.
   *
   * @param $identifier
   *   The variable to assign the PHP code for this object to.
   * @param $indent
   *   An optional indentation for prettifying nested code.
   */
  function export_row($identifier = NULL, $indent = '') {
    ctools_include('export');

    if (!$identifier) {
      $identifier = $this->db_table;
    }
    $schema = drupal_get_schema($this->db_table);

    $output = $indent . '$' . $identifier . ' = new ' . get_class($this) . "();\n";
    // Go through our schema and build correlations.
    foreach ($schema['fields'] as $field => $info) {
      if (!empty($info['no export'])) {
        continue;
      }
      if (!isset($this->$field)) {
        if (isset($info['default'])) {
          $this->$field = $info['default'];
        }
        else {
          $this->$field = '';
        }

        // serialized defaults must be set as serialized.
        if (isset($info['serialize'])) {
          $this->$field = unserialize($this->$field);
        }
      }
      $value = $this->$field;
      if ($info['type'] == 'int') {
        if (isset($info['size']) && $info['size'] == 'tiny') {
          $value = (bool) $value;
        }
        else {
          $value = (int) $value;
        }
      }

      $output .= $indent . '$' . $identifier . '->' . $field . ' = ' . ctools_var_export($value, $indent) . ";\n";
    }
    return $output;
  }

  /**
   * Add a new display handler to the view, automatically creating an id.
   *
   * @param $type
   *   The plugin type from the views plugin data. Defaults to 'page'.
   * @param $title
   *   The title of the display; optional, may be filled in from default.
   * @param $id
   *   The id to use.
   * @return
   *   The key to the display in $view->display, so that the new display
   *   can be easily located.
   */
  function add_display($type = 'page', $title = NULL, $id = NULL) {
    if (empty($type)) {
      return FALSE;
    }

    $plugin = views_fetch_plugin_data('display', $type);
    if (empty($plugin)) {
      $plugin['title'] = t('Broken');
    }


    if (empty($id)) {
      $id = $this->generate_display_id($type);
      if ($id !== 'default') {
        preg_match("/[0-9]+/", $id, $count);
        $count = $count[0];
      }
      else {
        $count = '';
      }

      if (empty($title)) {
        if ($count > 1) {
          $title = $plugin['title'] . ' ' . $count;
        }
        else {
          $title = $plugin['title'];
        }
      }
    }

    // Create the new display object
    $display = new ViewsDisplay();
    $display->options($type, $id, $title);

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
