<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Annotation\ViewsDisplayExtender.
 */

namespace Drupal\views\Annotation;

/**
 * Defines a Plugin annotation object for views display extender plugins.
 *
 * @see \Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase
 *
 * @ingroup views_display_extender_plugins
 *
 * @Annotation
 */
class ViewsDisplayExtender extends ViewsPluginAnnotationBase {

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
   * Whether or not the plugin is selectable in the UI.
   *
   * If it's set to TRUE, you can still use it via the API in config files.
   *
   * @var bool
   */
  public $no_ui;

}
