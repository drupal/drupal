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
   * @var bool
   */
  public $register_theme = TRUE;

}
