<?php

/**
 * @file
 * Definition of Drupal\views\ViewsDisplay.
 */

namespace Drupal\views;

/**
 * A display type in a view.
 *
 * This is just the database storage mechanism, and isn't terribly important
 * to the behavior of the display at all.
 */
class ViewsDisplay extends ViewsDbObject {
  /**
   * The display handler itself, which has all the methods.
   *
   * @var views_plugin_display
   */
  var $handler;

  /**
   * Stores all options of the display, like fields, filters etc.
   *
   * @var array
   */
  var $display_options;

  var $db_table = 'views_display';

  function __construct($init = TRUE) {
    parent::init($init);
  }

  function options($type, $id, $title) {
    $this->display_plugin = $type;
    $this->id = $id;
    $this->display_title = $title;
  }
}
