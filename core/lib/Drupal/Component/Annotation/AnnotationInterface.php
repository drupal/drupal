<?php

namespace Drupal\Component\Annotation;

/**
 * Defines a common interface for classed annotations.
 */
interface AnnotationInterface {

  /**
   * Gets the value of an annotation.
   */
  public function get();

  /**
   * Gets the name of the provider of the annotated class.
   *
   * @return string
   */
  public function getProvider();

  /**
   * Sets the name of the provider of the annotated class.
   *
   * @param string $provider
   */
  public function setProvider($provider);

  /**
   * Gets the unique ID for this annotated class.
   *
   * @return string
   */
  public function getId();

  /**
   * Gets the class of the annotated class.
   *
   * @return string
   */
  public function getClass();

  /**
   * Sets the class of the annotated class.
   *
   * @param string $class
   */
  public function setClass($class);

}
