<?php

namespace Drupal\views\Annotation;

use Drupal\Component\Annotation\AnnotationInterface;
use Drupal\Component\Annotation\Plugin;

/**
 * Defines an abstract base class for all views plugin annotations.
 */
abstract class ViewsPluginAnnotationBase extends Plugin implements AnnotationInterface {

  /**
   * Whether or not to register a theme function automatically.
   *
   * @var bool (optional)
   */
  public $register_theme = TRUE;

}
