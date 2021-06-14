<?php

namespace Drupal\Component\Annotation;

/**
 * Defines a Plugin annotation object that just contains an ID.
 *
 * @Annotation
 */
class PluginID extends AnnotationBase {

  /**
   * The plugin ID.
   *
   * When an annotation is given no key, 'value' is assumed by Doctrine.
   *
   * @var string
   */
  public $value;

  /**
   * {@inheritdoc}
   */
  public function get() {
    return [
      'id' => $this->value,
      'class' => $this->class,
      'provider' => $this->provider,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->value;
  }

}
