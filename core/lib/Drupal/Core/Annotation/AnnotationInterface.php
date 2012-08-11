<?php

/**
 * @file
 * Definition of Drupal\Core\Annotation\AnnotationInterface.
 */

namespace Drupal\Core\Annotation;

/**
 * Defines a common interface for classed annotations.
 */
interface AnnotationInterface {

  /**
   * Returns the value of an annotation.
   */
  public function get();

}
