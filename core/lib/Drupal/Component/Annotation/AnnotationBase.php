<?php

/**
 * @file
 * Contains \Drupal\Component\Annotation\AnnotationBase.
 */

namespace Drupal\Component\Annotation;

/**
 * Provides a base class for classed annotations.
 */
abstract class AnnotationBase implements AnnotationInterface {

  /**
   * The annotated class ID.
   *
   * @var string
   */
  public $id;

  /**
   * The class used for this annotated class.
   *
   * @var string
   */
  protected $class;

  /**
   * The provider of the annotated class.
   *
   * @var string
   */
  protected $provider;

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * {@inheritdoc}
   */
  public function setProvider($provider) {
    $this->provider = $provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    return $this->class;
  }

  /**
   * {@inheritdoc}
   */
  public function setClass($class) {
    $this->class = $class;
  }

}
