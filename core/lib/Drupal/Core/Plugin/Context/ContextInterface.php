<?php

namespace Drupal\Core\Plugin\Context;

use Drupal\Component\Plugin\Context\ContextInterface as ComponentContextInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Context data and definitions for plugins supporting caching and return docs.
 *
 * @see \Drupal\Component\Plugin\Context\ContextInterface
 * @see \Drupal\Core\Plugin\Context\ContextDefinitionInterface
 */
interface ContextInterface extends ComponentContextInterface, CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Plugin\Context\ContextDefinitionInterface
   */
  public function getContextDefinition();

  /**
   * Gets the context value as typed data object.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   */
  public function getContextData();

  /**
   * Adds a dependency on an object: merges its cacheability metadata.
   *
   * For example, when a context depends on some configuration, an entity, or an
   * access result, we must make sure their cacheability metadata is present on
   * the response. This method makes doing that simple.
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

  /**
   * Creates a new context with a different value.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface $old_context
   *   The context object used to create a new object. Cacheability metadata
   *   will be copied over.
   * @param mixed $value
   *   The value of the new context object.
   *
   * @return static
   */
  public static function createFromContext(ContextInterface $old_context, $value);

}
