<?php

/**
 * @file
 * Contains \Drupal\views\Annotation\ViewsExposedForm.
 */

namespace Drupal\views\Annotation;

/**
 * Defines a Plugin annotation object for views exposed form plugins.
 *
 * @see \Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase
 *
 * @ingroup views_exposed_form_plugins
 *
 * @Annotation
 */
class ViewsExposedForm extends ViewsPluginAnnotationBase {

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
   * The types of the display this plugin can be used with.
   *
   * For example the Feed display defines the type 'feed', so only rss style
   * and row plugins can be used in the views UI.
   *
   * @var array
   */
  public $display_types;

  /**
   * The base tables on which this exposed form plugin can be used.
   *
   * If no base table is specified the plugin can be used with all tables.
   *
   * @var array
   */
  public $base;

  /**
   * Whether the plugin should be not selectable in the UI.
   *
   * If it's set to TRUE, you can still use it via the API in config files.
   *
   * @var bool
   */
  public $no_ui;

}
