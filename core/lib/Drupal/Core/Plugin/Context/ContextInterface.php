<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Context\ContextInterface.
 */

namespace Drupal\Core\Plugin\Context;

use Drupal\Component\Plugin\Context\ContextInterface as ComponentContextInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Interface for context.
 */
interface ContextInterface extends ComponentContextInterface, CacheableDependencyInterface {

  /**
   * Gets the context value as typed data object.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   */
  public function getContextData();

  /**
   * Sets the context value as typed data object.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $data
   *   The context value as a typed data object.
   *
   * @return $this
   */
  public function setContextData(TypedDataInterface $data);

  /**
   * Adds a dependency on an object: merges its cacheability metadata.
   *
   * E.g. when a context depends on some configuration, an entity, or an access
   * result, we must make sure their cacheability metadata is present on the
   * response. This method makes doing that simple.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface|mixed $dependency
   *   The dependency. If the object implements CacheableDependencyInterface,
   *   then its cacheability metadata will be used. Otherwise, the passed in
   *   object must be assumed to be uncacheable, so max-age 0 is set.
   *
   * @return $this
   *
   * @see \Drupal\Core\Cache\CacheableMetadata::createFromObject()
   */
  public function addCacheableDependency($dependency);

}
