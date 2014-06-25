<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Annotation\ViewsQuery.
 */

namespace Drupal\views\Annotation;

use Drupal\views\Annotation\ViewsPluginAnnotationBase;

/**
 * Defines a Plugin annotation object for views query plugins.
 *
 * @see \Drupal\views\Plugin\views\query\QueryPluginBase
 *
 * @ingroup views_query_plugins
 *
 * @Annotation
 */
class ViewsQuery extends ViewsPluginAnnotationBase {

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
   * Whether the plugin should be not selectable in the UI.
   *
   * If it's set to TRUE, you can still use it via the API in config files.
   *
   * @var bool
   */
  public $no_ui;

}
