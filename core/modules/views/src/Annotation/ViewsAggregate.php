<?php

/**
 * @file
 * Contains \Drupal\views\Annotation\ViewsAggregate.
 */

namespace Drupal\views\Annotation;

/**
 * Defines a Plugin annotation object for views aggregate plugins.
 *
 * @see \Drupal\views\Plugin\views\aggregate\AggregatePluginBase
 *
 * @ingroup views_aggregate_plugins
 *
 * @Annotation
 */
class ViewsAggregate extends ViewsPluginAnnotationBase {

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
   * An external function to use for this plugin instead of the plugin name.
   *
   * @var string
   */
  public $function = '';

  /**
   * An function external to the class to use for this plugin.  This is the default in D7.
   *
   * @var string
   */
  public $method = '';

  /**
   * The Views handler use for this plugin.
   *
   * @var list
   */
  public $handler = array();

  /**
   * Whether the plugin should be not selectable in the UI.
   *
   * If it's set to TRUE, you can still use it via the API in config files.
   *
   * @var bool
   */
  public $no_ui;

}
