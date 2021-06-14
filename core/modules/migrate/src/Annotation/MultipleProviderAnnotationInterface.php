<?php

namespace Drupal\migrate\Annotation;

use Drupal\Component\Annotation\AnnotationInterface;

/**
 * Defines a common interface for classed annotations with multiple providers.
 *
 * @todo This is a temporary solution to the fact that migration source plugins
 *   have more than one provider. This functionality will be moved to core in
 *   https://www.drupal.org/node/2786355.
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
