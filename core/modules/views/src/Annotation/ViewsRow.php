<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Annotation\ViewsRow.
 */

namespace Drupal\views\Annotation;

use Drupal\views\Annotation\ViewsPluginAnnotationBase;

/**
 * Defines a Plugin annotation object for views row plugins.
 *
 * @Annotation
 *
 * @see \Drupal\views\Plugin\views\row\RowPluginBase
 */
class ViewsRow extends ViewsPluginAnnotationBase {

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
   * A short help string; this is displayed in the views UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $help = '';

  /**
   * The theme function used to render the row output.
   *
   * @return string
   */
  public $theme;

  /**
   * The base tables on which this row plugin can be used.
   *
   * @var array
   */
  public $base;

  /**
   * The types of the display this plugin can be used with.
   *
   * For example the Feed display defines the type 'feed', so only rss style
   * and row plugins can be used in the views UI.
   *
   * @var array
   */
  public $display_types;

  /**
   * Whether the plugin should be not selectable in the UI.
   *
   * If it's set to TRUE, you can still use it via the API in config files.
   *
   * @var bool
   */
  public $no_ui;

}
