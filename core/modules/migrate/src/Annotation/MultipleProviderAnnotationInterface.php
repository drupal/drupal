<?php

namespace Drupal\migrate\Annotation;

use Drupal\Component\Annotation\AnnotationInterface;

/**
 * Defines a common interface for classed annotations with multiple providers.
 *
 * @todo This provides backwards compatibility for migration source plugins
 *   using annotations and having more than one provider. This functionality
 *   will be deprecated with plugin discovery by annotations in
 *   https://www.drupal.org/project/drupal/issues/3522409.
 */
interface MultipleProviderAnnotationInterface extends AnnotationInterface {

  /**
   * Gets the name of the provider of the annotated class.
   *
   * @return string
   *   The provider of the annotation. If there are multiple providers the first
   *   is returned.
   */
  public function getProvider();

  /**
   * Gets the provider names of the annotated class.
   *
   * @return string[]
   *   The providers of the annotation.
   */
  public function getProviders();

  /**
   * Sets the provider names of the annotated class.
   *
   * @param string[] $providers
   *   The providers of the annotation.
   */
  public function setProviders(array $providers);

}
