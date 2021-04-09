<?php

namespace Drupal\views\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an abstract base class for all views plugin annotations.
 */
abstract class ViewsPluginAnnotationBase extends Plugin {

  /**
   * Whether or not to register a theme function automatically.
   *
   * This property is optional in the annotation and can be left out.
   *
   * @var bool
   */
  public $register_theme = TRUE;

}
