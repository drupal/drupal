<?php

/**
 * @file
 * Definition of Drupal\views\ViewUI.
 */

namespace Drupal\views;

/**
 * Stores UI related temporary settings.
 */
class ViewUI extends ViewExecutable {

  /**
   * Indicates if a view is currently being edited.
   *
   * @var bool
   */
  public $editing = FALSE;

  /**
   * Stores an array of errors for any displays.
   *
   * @var array
   */
  public $display_errors;

  /**
   * Stores an array of displays that have been changed.
   *
   * @var array
   */
  public $changed_display;

  /**
   * How long the view takes to build.
   *
   * @var int
   */
  public $build_time;

  /**
   * How long the view takes to render.
   *
   * @var int
   */
  public $render_time;

  /**
   * How long the view takes to execute.
   *
   * @var int
   */
  public $execute_time;

  /**
   * If this view is locked for editing.
   *
   * @var bool
   */
  public $locked;

  /**
   * If this view has been changed.
   *
   * @var bool
   */
  public $changed;

  /**
   * Stores options temporarily while editing.
   *
   * @var array
   */
  public $temporary_options;

  /**
   * Stores a stack of UI forms to display.
   *
   * @var array
   */
  public $stack;

  /**
   * Is the view runned in a context of the preview in the admin interface.
   *
   * @var bool
   */
  public $live_preview;
}
