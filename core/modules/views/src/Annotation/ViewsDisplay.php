<?php

/**
 * @file
 * Contains \Drupal\views\Annotation\ViewsDisplay.
 */

namespace Drupal\views\Annotation;

use Drupal\views\Annotation\ViewsPluginAnnotationBase;

/**
 * Defines a Plugin annotation object for views display plugins.
 *
 * @see \Drupal\views\Plugin\views\display\DisplayPluginBase
 *
 * @ingroup views_display_plugins
 *
 * @Annotation
 */
class ViewsDisplay extends ViewsPluginAnnotationBase {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin title used in the views UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title = '';

  /**
   * (optional) The short title used in the views UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $short_title = '';

  /**
   * The administrative name of the display.
   *
   * The name is displayed on the Views overview and also used as default name
   * for new displays.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $admin = '';

  /**
   * A short help string; this is displayed in the views UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $help = '';

  /**
   * Whether or not to use hook_menu() to register a route.
   *
   * @var bool
   */
  public $uses_menu_links;

  /**
   * Does the display plugin registers routes to the route.
   *
   * @var bool
   */
  public $uses_route;

  /**
   * Does the display plugin provide blocks.
   *
   * @var bool
   */
  public $uses_hook_block;

  /**
   * A list of places where contextual links should be added.
   * For example:
   * @code
   * array(
   *   'page',
   *   'block',
   * )
   * @endcode
   *
   * If you don't specify it there will be contextual links rendered for all
   * displays of a view. If this is not set or regions have been specified,
   * views will display an option to 'hide contextual links'. Use an empty
   * array to disable.
   *
   * @var array
   */
  public $contextual_links_locations;

  /**
   * The base tables on which this display plugin can be used.
   *
   * If no base table is specified the plugin can be used with all tables.
   *
   * @var array
   */
  public $base;

  /**
   * The theme function used to render the display's output.
   *
   * @return string
   */
  public $theme;

  /**
   * Whether the plugin should be not selectable in the UI.
   *
   * If it's set to TRUE, you can still use it via the API in config files.
   *
   * @var bool
   */
  public $no_ui;

}
