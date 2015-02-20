<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\CacheContexts.
 */

namespace Drupal\Core\Cache;

use Drupal\Component\Utility\String;
use Drupal\Core\Database\Query\SelectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the CacheContexts service.
 *
 * Converts cache context IDs into their final string values, to be used as
 * cache keys.
 */
class CacheContexts {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Available cache context IDs and corresponding labels.
   *
   * @var string[]
   */
  protected $contexts;

  /**
   * Constructs a CacheContexts object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The current service container.
   * @param string[] $contexts
   *   An array of the available cache context IDs.
   */
  public function __construct(ContainerInterface $container, array $contexts) {
    $this->container = $container;
    $this->contexts = $contexts;
  }

  /**
   * Provides an array of available cache contexts.
   *
   * @return string[]
   *   An array of available cache context IDs.
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
   * @param string[] $contexts
   *   An array of cache context IDs.
   *
   * @return string[]
   *   A copy of the input, with cache context tokens converted.
   *
   * @throws \InvalidArgumentException
   */
  public function convertTokensToKeys(array $contexts) {
    $materialized_contexts = [];
    foreach ($contexts as $context) {
      if (!in_array($context, $this->contexts)) {
        throw new \InvalidArgumentException(String::format('"@context" is not a valid cache context ID.', ['@context' => $context]));
      }
      $materialized_contexts[] = $this->getContext($context);
    }
    return $materialized_contexts;
  }

  /**
   * Provides the string representation of a cache context.
   *
   * @param string $context
   *   A cache context ID of an available cache context service.
   *
   * @return string
   *   The string representation of a cache context.
   */
  protected function getContext($context) {
    return $this->getService($context)->getContext();
  }

  /**
   * Retrieves a cache context service from the container.
   *
   * @param string $context
   *   The context ID, which together with the service ID prefix allows the
   *   corresponding cache context service to be retrieved.
   *
   * @return \Drupal\Core\Cache\CacheContextInterface
   *   The requested cache context service.
   */
  protected function getService($context) {
    return $this->container->get('cache_context.' . $context);
  }

}
