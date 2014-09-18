<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\CacheContexts.
 */

namespace Drupal\Core\Cache;

use Drupal\Core\Database\Query\SelectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the CacheContexts service.
 *
 * Converts string placeholders into their final string values, to be used as a
 * cache key.
 */
class CacheContexts {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Available cache contexts and corresponding labels.
   *
   * @var array
   */
  protected $contexts;

  /**
   * Constructs a CacheContexts object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The current service container.
   * @param array $contexts
   *   An array of key-value pairs, where the keys are service names (which also
   *   serve as the corresponding cache context token) and the values are the
   *   cache context labels.
   */
  public function __construct(ContainerInterface $container, array $contexts) {
    $this->container = $container;
    $this->contexts = $contexts;
  }

  /**
   * Provides an array of available cache contexts.
   *
   * @return array
   *   An array of available cache contexts.
   */
  public function getAll() {
    return $this->contexts;
  }

  /**
   * Provides an array of available cache context labels.
   *
   * To be used in cache configuration forms.
   *
   * @return array
   *   An array of available cache contexts and corresponding labels.
   */
  public function getLabels() {
    $with_labels = array();
    foreach ($this->contexts as $context) {
      $with_labels[$context] = $this->getService($context)->getLabel();
    }
    return $with_labels;
  }

  /**
   * Converts cache context tokens to string representations of the context.
   *
   * Cache keys may either be static (just strings) or tokens (placeholders
   * that are converted to static keys by the @cache_contexts service, depending
   * depending on the request). This is the default cache contexts service.
   *
   * @param array $keys
   *   An array of cache keys that may or may not contain cache context tokens.
   *
   * @return array
   *   A copy of the input, with cache context tokens converted.
   */
  public function convertTokensToKeys(array $keys) {
    $context_keys = array_intersect($keys, $this->getAll());
    $new_keys = $keys;

    // Iterate over the indices instead of the values so that the order of the
    // cache keys are preserved.
    foreach (array_keys($context_keys) as $index) {
      $new_keys[$index] = $this->getContext($keys[$index]);
    }
    return $new_keys;
  }

  /**
   * Provides the string representation of a cache context.
   *
   * @param string $context
   *   A cache context token of an available cache context service.
   *
   * @return string
   *   The string representation of a cache context.
   */
  protected function getContext($context) {
    return $this->getService($context)->getContext();
  }

  /**
   * Retrieves a service from the container.
   *
   * @param string $service
   *   The ID of the service to retrieve.
   *
   * @return mixed
   *   The specified service.
   */
  protected function getService($service) {
    return $this->container->get($service);
  }

}
