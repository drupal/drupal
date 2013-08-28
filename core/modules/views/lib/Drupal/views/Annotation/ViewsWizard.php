<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Annotation\ViewsWizard.
 */

namespace Drupal\views\Annotation;

use Drupal\views\Annotation\ViewsPluginAnnotationBase;

/**
 * Defines a Plugin annotation object for views wizard plugins.
 *
 * @Annotation
 *
 * @see \Drupal\views\Plugin\views\wizard\WizardPluginBase
 */
class ViewsWizard extends ViewsPluginAnnotationBase {

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
   * The base tables on which this wizard is used.
   *
   * @var array
   */
  public $base_table;

}
