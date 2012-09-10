<?php

/**
 * @file
 * Definition of Drupal\views\ViewDisplay.
 */

namespace Drupal\views;

/**
 * Defines a display type in a view.
 *
 * This is just the database storage mechanism, and isn't terribly important
 * to the behavior of the display at all.
 */
class ViewDisplay {

  /**
   * The display plugin ID.
   *
   * @var string
   */
  public $display_plugin;

  /**
   * The display handler itself, which has all the methods.
   *
   * @var Drupal\views\Plugin\views\display\DisplayPluginBase
   */
  public $handler;

  /**
   * The machine name of this display.
   *
   * @var string
   */
  public $id;

  /**
   * Stores all options of the display, like fields, filters etc.
   *
   * @var array
   */
  public $display_options;

  /**
   * The human-readable name of this display.
   *
   * @var string
   */
  public $display_title;

  /**
   * The position (weight) of the display.
   *
   * @var int
   */
  public $position;

  /**
   * Constructs a ViewDisplay object.
   *
   * @param array $values
   *   An array of display options to set, with the following keys:
   *   - display_options: (optional) An array of display configuration values.
   *     Defaults to an empty array.
   *   - display_plugin: (optional) The display plugin ID, if any. Defaults to
   *     NULL.
   *   - id: (optional) The ID of this ViewDisplay object. Defaults to NULL.
   *   - display_title: (optional) The human-readable label for the display.
   *     Defaults to an empty string.
   *   - position: (optional) The weight of the display. Defaults to NULL.
   *
   * @todo Determine behavior when values are empty and if these are actually
   *   optional. Does it make sense to construct a display without an ID or
   *   plugin?
   * @todo Rename position to weight.
   * @todo Rename display_plugin to plugin_id.
   * @todo Do we actually want to pass these in as an array, or do we want
   *   explicit parameters or protected properties? (ID, type, array()) is the
   *   pattern core uses.
   */
  public function __construct(array $values = array()) {
    $values += array(
      'display_options' => array(),
      'display_plugin' => NULL,
      'id' => NULL,
      'display_title' => '',
      'position' => NULL,
    );

    $this->display_options = $values['display_options'];
    $this->display_plugin = $values['display_plugin'];
    $this->id = $values['id'];
    $this->display_title = $values['display_title'];
    $this->position = $values['position'];
  }

}
