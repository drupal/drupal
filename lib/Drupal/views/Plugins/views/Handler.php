<?php
/**
 * @file
 * Definition of Drupal\views\Plugins\views\Handler
 */

namespace Drupal\views\Plugins\views;

use Drupal\views\Plugins\views\Plugin;
use Drupal\views\View;

class Handler extends Plugin {
  /**
   * init the handler with necessary data.
   * @param $view
   *   The $view object this handler is attached to.
   * @param $options
   *   The item from the database; the actual contents of this will vary
   *   based upon the type of handler.
   */
  function init(&$view, &$options) {
    $this->view = &$view;
    $display_id = $this->view->current_display;
    // Check to see if this handler type is defaulted. Note that
    // we have to do a lookup because the type is singular but the
    // option is stored as the plural.

    // If the 'moved to' keyword moved our handler, let's fix that now.
    if (isset($this->actual_table)) {
      $options['table'] = $this->actual_table;
    }

    if (isset($this->actual_field)) {
      $options['field'] = $this->actual_field;
    }

    $types = View::views_object_types();
    $plural = $this->handler_type;
    if (isset($types[$this->handler_type]['plural'])) {
      $plural = $types[$this->handler_type]['plural'];
    }
    if ($this->view->display_handler->is_defaulted($plural)) {
      $display_id = 'default';
    }

    $this->localization_keys = array(
      $display_id,
      $this->handler_type,
      $options['table'],
      $options['id']
    );

    $this->unpack_options($this->options, $options);

    // This exist on most handlers, but not all. So they are still optional.
    if (isset($options['table'])) {
      $this->table = $options['table'];
    }

    if (isset($this->definition['real field'])) {
      $this->real_field = $this->definition['real field'];
    }

    if (isset($this->definition['field'])) {
      $this->real_field = $this->definition['field'];
    }

    if (isset($options['field'])) {
      $this->field = $options['field'];
      if (!isset($this->real_field)) {
        $this->real_field = $options['field'];
      }
    }

    $this->query = &$view->query;
  }
}
