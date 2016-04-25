<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
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
 * @see \Drupal\Core\Cache\Context\CacheContextInterface
 * @see \Drupal\Core\Cache\Context\CalculatedCacheContextInterface
 * @see \Drupal\Core\Cache\Context\CacheContextsPass
 */
class CacheContextsManager {

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
   * Constructs a CacheContextsManager object.
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
   *   is a cache context ID); for example, 'foo'.
   * - a calculated cache context ID, followed by a colon, followed by
   *   the parameter for the calculated cache context; for example,
   *   'bar:some_parameter'.
   *
   * @param string[] $context_tokens
   *   An array of cache context tokens.
   *
   * @return \Drupal\Core\Cache\Context\ContextCacheKeys
   *   The ContextCacheKeys object containing the converted cache keys and
   *   cacheability metadata.
   *
   */
  public function convertTokensToKeys(array $context_tokens) {
    assert('$this->assertValidTokens($context_tokens)');
    $cacheable_metadata = new CacheableMetadata();
    $optimized_tokens = $this->optimizeTokens($context_tokens);
    // Iterate over cache contexts that have been optimized away and get their
    // cacheability metadata.
    foreach (static::parseTokens(array_diff($context_tokens, $optimized_tokens)) as $context_token) {
      list($context_id, $parameter) = $context_token;
      $context = $this->getService($context_id);
      $cacheable_metadata = $cacheable_metadata->merge($context->getCacheableMetadata($parameter));
    }

    sort($optimized_tokens);
    $keys = [];
    foreach (array_combine($optimized_tokens, static::parseTokens($optimized_tokens)) as $context_token => $context) {
      list($context_id, $parameter) = $context;
      $keys[] = '[' . $context_token . ']=' . $this->getService($context_id)->getContext($parameter);
    }

    // Create the returned object and merge in the cacheability metadata.
    $context_cache_keys = new ContextCacheKeys($keys);
    return $context_cache_keys->merge($cacheable_metadata);
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
   * If a cache context is being optimized away, it is able to set cacheable
   * metadata for itself which will be bubbled up.
   *
   * For example, when caching per user ('user'), also caching per role
   * ('user.roles') is meaningless because "per role" is implied by "per user".
   *
   * In the following examples, remember that the period indicates hierarchy and
   * the colon can be used to get a specific value of a calculated cache
   * context:
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

      // Extract the parameter if available.
      $parameter = NULL;
      $context_id = $context_token;
      if (strpos($context_token, ':') !== FALSE) {
        list($context_id, $parameter) = explode(':', $context_token);
      }

      // Context tokens without:
      // - a period means they don't have a parent
      // - a colon means they're not a specific value of a cache context
      // hence no optimizations are possible.
      if (strpos($context_token, '.') === FALSE && strpos($context_token, ':') === FALSE) {
        $optimized_content_tokens[] = $context_token;
      }
      // Check cacheability. If the context defines a max-age of 0, then it
      // can not be optimized away. Pass the parameter along if we have one.
      elseif ($this->getService($context_id)->getCacheableMetadata($parameter)->getCacheMaxAge() === 0) {
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
   * @return \Drupal\Core\Cache\Context\CacheContextInterface
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
   *   - The cache context ID.
   *   - The associated parameter (for a calculated cache context), or NULL if
   *     there is no parameter.
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
   * @see \Drupal\Core\Cache\Context\CacheContextsManager::parseTokens()
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

  /**
   * Asserts the context tokens are valid
   *
   * Similar to ::validateTokens, this method returns boolean TRUE when the
   * context tokens are valid, and FALSE when they are not instead of returning
   * NULL when they are valid and throwing a \LogicException when they are not.
   * This function should be used with the assert() statement.
   *
   * @param mixed $context_tokens
   *   Variable to be examined - should be array of context_tokens.
   *
   * @return bool
   *   TRUE if context_tokens is an array of valid tokens.
   */
  public function assertValidTokens($context_tokens) {
    if (!is_array($context_tokens)) {
      return FALSE;
    }

    try {
      $this->validateTokens($context_tokens);
    }
    catch (\LogicException $e) {
      return FALSE;
    }

    return TRUE;
  }

}
