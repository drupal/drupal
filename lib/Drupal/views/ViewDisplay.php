<?php

/**
 * @file
 * Definition of Drupal\views\ViewDisplay.
 */

namespace Drupal\views;

/**
 * A display type in a view.
 *
 * This is just the database storage mechanism, and isn't terribly important
 * to the behavior of the display at all.
 */
class ViewDisplay {

  /**
   * The display handler itself, which has all the methods.
   *
   * @var views_plugin_display
   */
  public $handler;

  /**
   * Stores all options of the display, like fields, filters etc.
   *
   * @var array
   */
  public $display_options;

  function __construct(array $display_options = array()) {
    if (!empty($display_options)) {
      $this->display_options = $display_options['display_options'];
      $this->display_plugin = $display_options['display_plugin'];
      $this->id = $display_options['id'];
      $this->display_title = $display_options['display_title'];
    }
  }

}
