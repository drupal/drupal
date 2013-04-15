<?php

/**
 * @file
 * Contains Drupal\Component\Annotation\PluginID.
 */

namespace Drupal\Component\Annotation;

/**
 * Defines a Plugin annotation object that just contains an ID.
 *
 * @Annotation
 */
class PluginID implements AnnotationInterface {

  /**
   * The plugin ID.
   *
   * When an annotation is given no key, 'value' is assumed by Doctrine.
   *
   * @var string
   */
  public $value;

  /**
   * Implements \Drupal\Core\Annotation\AnnotationInterface::get().
   */
  public function get() {
    return array(
      'id' => $this->value,
    );
  }

}
