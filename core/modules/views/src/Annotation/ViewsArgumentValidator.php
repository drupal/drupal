<?php

/**
 * @file
 * Contains \Drupal\views\Annotation\ViewsArgumentValidator.
 */

namespace Drupal\views\Annotation;

/**
 * Defines a Plugin annotation object for views argument validator plugins.
 *
 * @see \Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase
 *
 * @ingroup views_argument_validate_plugins
 *
 * @Annotation
 */
class ViewsArgumentValidator extends ViewsPluginAnnotationBase {

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
   * Whether the plugin should be not selectable in the UI.
   *
   * If it's set to TRUE, you can still use it via the API in config files.
   *
   * @var bool
   */
  public $no_ui;

}
