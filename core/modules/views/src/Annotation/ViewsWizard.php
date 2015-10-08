<?php

/**
 * @file
 * Contains \Drupal\views\Annotation\ViewsWizard.
 */

namespace Drupal\views\Annotation;

/**
 * Defines a Plugin annotation object for views wizard plugins.
 *
 * @see \Drupal\views\Plugin\views\wizard\WizardPluginBase
 * @see \Drupal\views\Plugin\views\wizard\WizardInterface
 *
 * @ingroup views_wizard_plugins
 *
 * @Annotation
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
