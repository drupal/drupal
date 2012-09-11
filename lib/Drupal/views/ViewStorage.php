<?php

/**
 * @file
 * Definition of Drupal\views\ViewStorage.
 */

namespace Drupal\views;

use Drupal\config\ConfigEntityBase;

/**
 * Defines a ViewStorage configuration entity class.
 */
class ViewStorage extends ConfigEntityBase implements ViewStorageInterface {

  /**
   * Overrides Drupal\entity\EntityInterface::id().
   */
  public function id() {
    return $this->name;
  }

  /**
   * Implements Drupal\views\ViewStorageInterface::enable().
   */
  public function enable() {
    $this->disabled = FALSE;
    $this->save();
  }

  /**
   * Implements Drupal\views\ViewStorageInterface::disable().
   */
  public function disable() {
    $this->disabled = TRUE;
    $this->save();
  }

  /**
   * Implements Drupal\views\ViewStorageInterface::isEnabled().
   */
  public function isEnabled() {
    return !$this->disabled;
  }

  /**
   * Adds a new display handler to the view, automatically creating an ID.
   *
   * @param string $plugin_id
   *   (optional) The plugin type from the Views plugin annotation. Defaults to
   *   'page'.
   * @param string $title
   *   (optional) The title of the display. Defaults to NULL.
   * @param string $id
   *   (optional) The ID to use, e.g., 'default', 'page_1', 'block_2'. Defaults
   *   to NULL.
   *
   * @return string|false
   *   The key to the display in $view->display, or FALSE if no plugin ID was
   *   provided.
   */
  public function addDisplay($plugin_id = 'page', $title = NULL, $id = NULL) {
    if (empty($plugin_id)) {
      return FALSE;
    }

    $plugin = views_get_plugin_definition('display', $plugin_id);
    if (empty($plugin)) {
      $plugin['title'] = t('Broken');
    }

    if (empty($id)) {
      $id = $this->generateDisplayId($plugin_id);

      // Generate a unique human-readable name by inspecting the counter at the
      // end of the previous display ID, e.g., 'page_1'.
      if ($id !== 'default') {
        preg_match("/[0-9]+/", $id, $count);
        $count = $count[0];
      }
      else {
        $count = '';
      }

      if (empty($title)) {
        // If there is no title provided, use the plugin title, and if there are
        // multiple displays, append the count.
        $title = $plugin['title'];
        if ($count > 1) {
          $title .= ' ' . $count;
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
   * Generates a display ID of a certain plugin type.
   *
   * @param string $plugin_id
   *   Which plugin should be used for the new display ID.
   */
  protected function generateDisplayId($plugin_id) {
    // 'default' is singular and is unique, so just go with 'default'
    // for it. For all others, start counting.
    if ($plugin_id == 'default') {
      return 'default';
    }
    // Initial ID.
    $id = $plugin_id . '_1';
    $count = 1;

    // Loop through IDs based upon our style plugin name until
    // we find one that is unused.
    while (!empty($this->display[$id])) {
      $id = $plugin_id . '_' . ++$count;
    }

    return $id;
  }

  /**
   * Generates a unique ID for an handler instance.
   *
   * These handler instances are typically fields, filters, sort criteria, or
   * arguments.
   *
   * @param string $requested_id
   *   The requested ID for the handler instance.
   * @param array $existing_items
   *   An array of existing handler instancess, keyed by their IDs.
   *
   * @return string
   *   A unique ID. This will be equal to $requested_id if no handler instance
   *   with that ID already exists. Otherwise, it will be appended with an
   *   integer to make it unique, e.g., "{$requested_id}_1",
   *   "{$requested_id}_2", etc.
   */
  public static function generateItemId($requested_id, $existing_items) {
    $count = 0;
    $id = $requested_id;
    while (!empty($existing_items[$id])) {
      $id = $requested_id . '_' . ++$count;
    }
    return $id;
  }

  /**
   * Creates a new display and a display handler for it.
   *
   * @param string $plugin_id
   *   (optional) The plugin type from the Views plugin annotation. Defaults to
   *   'page'.
   * @param string $title
   *   (optional) The title of the display. Defaults to NULL.
   * @param string $id
   *   (optional) The ID to use, e.g., 'default', 'page_1', 'block_2'. Defaults
   *   to NULL.
   *
   * @return Drupal\views\Plugin\views\display\DisplayPluginBase
   *   A reference to the new handler object.
   */
  public function &newDisplay($plugin_id = 'page', $title = NULL, $id = NULL) {
    $id = $this->addDisplay($plugin_id, $title, $id);

    // Create a handler.
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
   * Adds an instance of a handler to the view.
   *
   * Items may be fields, filters, sort criteria, or arguments.
   *
   * @param string $display_id
   *   The machine name of the display.
   * @param string $type
   *   The type of handler being added.
   * @param string $table
   *   The name of the table this handler is from.
   * @param string $field
   *   The name of the field this handler is from.
   * @param array $options
   *   (optional) Extra options for this instance. Defaults to an empty array.
   * @param string $id
   *   (optional) A unique ID for this handler instance. Defaults to NULL, in
   *   which case one will be generated.
   *
   * @return string
   *   The unique ID for this handler instance.
   */
  public function addItem($display_id, $type, $table, $field, $options = array(), $id = NULL) {
    $types = View::viewsHandlerTypes();
    $this->setDisplay($display_id);

    $fields = $this->display[$display_id]->handler->getOption($types[$type]['plural']);

    if (empty($id)) {
      $id = $this->generateItemId($field, $fields);
    }

    // If the desired type is not found, use the original value directly.
    $handler_type = !empty($types[$type]['type']) ? $types[$type]['type'] : $type;

    // @todo This variable is never used.
    $handler = views_get_handler($table, $field, $handler_type);

    $fields[$id] = array(
      'id' => $id,
      'table' => $table,
      'field' => $field,
    ) + $options;

    $this->display[$display_id]->handler->setOption($types[$type]['plural'], $fields);

    return $id;
  }

  /**
   * Gets an array of handler instances for the current display.
   *
   * @param string $type
   *   The type of handlers to retrieve.
   * @param string $display_id
   *   (optional) A specific display machine name to use. If NULL, the current
   *   display will be used.
   *
   * @return array
   *   An array of handler instances of a given type for this display.
   */
  public function getItems($type, $display_id = NULL) {
    $this->setDisplay($display_id);

    if (!isset($display_id)) {
      $display_id = $this->current_display;
    }

    // Get info about the types so we can get the right data.
    $types = View::viewsHandlerTypes();
    return $this->display[$display_id]->handler->getOption($types[$type]['plural']);
  }

  /**
   * Gets the configuration of a handler instance on a given display.
   *
   * @param string $display_id
   *   The machine name of the display.
   * @param string $type
   *   The type of handler to retrieve.
   * @param string $id
   *   The ID of the handler to retrieve.
   *
   * @return array|null
   *   Either the handler instance's configuration, or NULL if the handler is
   *   not used on the display.
   */
  public function getItem($display_id, $type, $id) {
    // Get info about the types so we can get the right data.
    $types = View::viewsHandlerTypes();
    // Initialize the display
    $this->setDisplay($display_id);

    // Get the existing configuration
    $fields = $this->display[$display_id]->handler->getOption($types[$type]['plural']);

    return isset($fields[$id]) ? $fields[$id] : NULL;
  }

  /**
   * Sets the configuration of a handler instance on a given display.
   *
   * @param string $display_id
   *   The machine name of the display.
   * @param string $type
   *   The type of handler being set.
   * @param string $id
   *   The ID of the handler being set.
   * @param array|null $item
   *   An array of configuration for a handler, or NULL to remove this instance.
   *
   * @see set_item_option()
   */
  public function setItem($display_id, $type, $id, $item) {
    // Get info about the types so we can get the right data.
    $types = View::viewsHandlerTypes();
    // Initialize the display.
    $this->setDisplay($display_id);

    // Get the existing configuration.
    $fields = $this->display[$display_id]->handler->getOption($types[$type]['plural']);
    if (isset($item)) {
      $fields[$id] = $item;
    }
    else {
      unset($fields[$id]);
    }

    // Store.
    $this->display[$display_id]->handler->setOption($types[$type]['plural'], $fields);
  }

  /**
   * Sets an option on a handler instance.
   *
   * Use this only if you have just 1 or 2 options to set; if you have many,
   * consider getting the handler instance, adding the options and using
   * set_item() directly.
   *
   * @param string $display_id
   *   The machine name of the display.
   * @param string $type
   *   The type of handler being set.
   * @param string $id
   *   The ID of the handler being set.
   * @param string $option
   *   The configuration key for the value being set.
   * @param mixed $value
   *   The value being set.
   *
   * @see set_item()
   */
  public function setItemOption($display_id, $type, $id, $option, $value) {
    $item = $this->getItem($display_id, $type, $id);
    $item[$option] = $value;
    $this->setItem($display_id, $type, $id, $item);
  }

}
