<?php

/**
 * @file
 * Contains Drupal\Component\Annotation\AnnotationInterface.
 */

namespace Drupal\Component\Annotation;

/**
 * Defines a common interface for classed annotations.
 */
interface AnnotationInterface {

  /**
   * Returns the value of an annotation.
   */
  public function get();

}
