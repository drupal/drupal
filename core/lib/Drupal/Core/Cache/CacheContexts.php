<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\CacheContexts.
 */

namespace Drupal\Core\Cache;

use Drupal\Component\Utility\SafeMarkup;
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
    $context_tokens = $this->optimizeTokens($context_tokens);
    sort($context_tokens);
    $keys = [];
    foreach (static::parseTokens($context_tokens) as $context) {
      list($context_id, $parameter) = $context;
      if (!in_array($context_id, $this->contexts)) {
        throw new \InvalidArgumentException(SafeMarkup::format('"@context" is not a valid cache context ID.', ['@context' => $context_id]));
      }
      $keys[] = $this->getService($context_id)->getContext($parameter);
    }
    return $keys;
  }

  /**
   * Optimizes cache context tokens (the minimal representative subset).
   *
   * A minimal representative subset means that any cache context token in the
   * given set of cache context tokens that is a property of another cache
   * context cache context token in the set, is removed.
   *
   * Hence a minimal representative subset is the most compact representation
   * possible of a set of cache context tokens, that still captures the entire
   * universe of variations.
   *
   * E.g. when caching per user ('user'), also caching per role ('user.roles')
   * is meaningless because "per role" is implied by "per user".
   *
   * Examples — remember that the period indicates hierarchy and the colon can
   * be used to get a specific value of a calculated cache context:
   * - ['a', 'a.b'] -> ['a']
   * - ['a', 'a.b.c'] -> ['a']
   * - ['a.b', 'a.b.c'] -> ['a.b']
   * - ['a', 'a.b', 'a.b.c'] -> ['a']
   * - ['x', 'x:foo'] -> ['x']
   * - ['a', 'a.b.c:bar'] -> ['a']
   *
   * @param string[] $context_tokens
   *   A set of cache context tokens.
   *
   * @return string[]
   *   A representative subset of the given set of cache context tokens..
   */
  public function optimizeTokens(array $context_tokens) {
    $optimized_content_tokens = [];
    foreach ($context_tokens as $context_token) {
      // Context tokens without:
      // - a period means they don't have a parent
      // - a colon means they're not a specific value of a cache context
      // hence no optimizations are possible.
      if (strpos($context_token, '.') === FALSE && strpos($context_token, ':') === FALSE) {
        $optimized_content_tokens[] = $context_token;
      }
      // The context token has a period or a colon. Iterate over all ancestor
      // cache contexts. If one exists, omit the context token.
      else {
        $ancestor_found = FALSE;
        // Treat a colon like a period, that allows us to consider 'a' the
        // ancestor of 'a:foo', without any additional code for the colon.
        $ancestor = str_replace(':', '.', $context_token);
        do {
          $ancestor = substr($ancestor, 0, strrpos($ancestor, '.'));
          if (in_array($ancestor, $context_tokens)) {
            // An ancestor cache context is in $context_tokens, hence this cache
            // context is implied.
            $ancestor_found = TRUE;
          }

        } while(!$ancestor_found && strpos($ancestor, '.') !== FALSE);
        if (!$ancestor_found) {
          $optimized_content_tokens[] = $context_token;
        }
      }
    }
    return $optimized_content_tokens;
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

  /**
   * Validates an array of cache context tokens.
   *
   * Can be called before using cache contexts in operations, to check validity.
   *
   * @param string[] $context_tokens
   *   An array of cache context tokens.
   *
   * @throws \LogicException
   *
   * @see \Drupal\Core\Cache\CacheContexts::parseTokens()
   */
  public function validateTokens(array $context_tokens = []) {
    if (empty($context_tokens)) {
      return;
    }

    // Initialize the set of valid context tokens with the container's contexts.
    if (!isset($this->validContextTokens)) {
      $this->validContextTokens = array_flip($this->contexts);
    }

    foreach ($context_tokens as $context_token) {
      if (!is_string($context_token)) {
        throw new \LogicException(sprintf('Cache contexts must be strings, %s given.', gettype($context_token)));
      }

      if (isset($this->validContextTokens[$context_token])) {
        continue;
      }

      // If it's a valid context token, then the ID must be stored in the set
      // of valid context tokens (since we initialized it with the list of cache
      // context IDs using the container). In case of an invalid context token,
      // throw an exception, otherwise cache it, including the parameter, to
      // minimize the amount of work in future ::validateContexts() calls.
      $context_id = $context_token;
      $colon_pos = strpos($context_id, ':');
      if ($colon_pos !== FALSE) {
        $context_id = substr($context_id, 0, $colon_pos);
      }
      if (isset($this->validContextTokens[$context_id])) {
        $this->validContextTokens[$context_token] = TRUE;
      }
      else {
        throw new \LogicException(sprintf('"%s" is not a valid cache context ID.', $context_id));
      }
    }
  }

}
