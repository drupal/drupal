<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\CacheContexts.
 */

namespace Drupal\Core\Cache;

use Drupal\Component\Utility\String;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Converts cache context tokens into cache keys.
 *
 * Uses cache context services (services tagged with 'cache.context', and whose
 * service ID has the 'cache_context.' prefix) to dynamically generate cache
 * keys based on the request context, thus allowing developers to express the
 * state by which should varied (the current URL, language, and so on).
 *
 * Note that this maps exactly to HTTP's Vary header semantics:
 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.44
 *
 * @see \Drupal\Core\Cache\CacheContextInterface
 * @see \Drupal\Core\Cache\CalculatedCacheContextInterface
 * @see \Drupal\Core\Cache\CacheContextsPass
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
   * @param bool $include_calculated_cache_contexts
   *   Whether to also return calculated cache contexts. Default to FALSE.
   *
   * @return array
   *   An array of available cache contexts and corresponding labels.
   */
  public function getLabels($include_calculated_cache_contexts = FALSE) {
    $with_labels = array();
    foreach ($this->contexts as $context) {
      $service = $this->getService($context);
      if (!$include_calculated_cache_contexts && $service instanceof CalculatedCacheContextInterface) {
        continue;
      }
      $with_labels[$context] = $service->getLabel();
    }
    return $with_labels;
  }

  /**
   * Converts cache context tokens to cache keys.
   *
   * A cache context token is either:
   * - a cache context ID (if the service ID is 'cache_context.foo', then 'foo'
   *   is a cache context ID), e.g. 'foo'
   * - a calculated cache context ID, followed by a double colon, followed by
   *   the parameter for the calculated cache context, e.g. 'bar:some_parameter'
   *
   * @param string[] $context_tokens
   *   An array of cache context tokens.
   *
   * @return string[]
   *   The array of corresponding cache keys.
   *
   * @throws \InvalidArgumentException
   */
  public function convertTokensToKeys(array $context_tokens) {
    $keys = [];
    foreach (static::parseTokens($context_tokens) as $context) {
      list($context_id, $parameter) = $context;
      if (!in_array($context_id, $this->contexts)) {
        throw new \InvalidArgumentException(String::format('"@context" is not a valid cache context ID.', ['@context' => $context_id]));
      }
      $keys[] = $this->getService($context_id)->getContext($parameter);
    }
    return $keys;
  }

  /**
   * Retrieves a cache context service from the container.
   *
   * @param string $context_id
   *   The context ID, which together with the service ID prefix allows the
   *   corresponding cache context service to be retrieved.
   *
   * @return \Drupal\Core\Cache\CacheContextInterface
   *   The requested cache context service.
   */
  protected function getService($context_id) {
    return $this->container->get('cache_context.' . $context_id);
  }

  /**
   * Parses cache context tokens into context IDs and optional parameters.
   *
   * @param string[] $context_tokens
   *   An array of cache context tokens.
   *
   * @return array
   *   An array with the parsed results, with each result being an array
   *   containing:
   *   1. the cache context ID
   *   2. the associated parameter (for a calculated cache context), or NULL if
   *      there is no parameter.
   */
  public static function parseTokens(array $context_tokens) {
    $contexts_with_parameters = [];
    foreach ($context_tokens as $context) {
      $context_id = $context;
      $parameter = NULL;
      if (strpos($context, ':') !== FALSE) {
        list($context_id, $parameter) = explode(':', $context, 2);
      }
      $contexts_with_parameters[] = [$context_id, $parameter];
    }
    return $contexts_with_parameters;
  }

}
