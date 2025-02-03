<?php

namespace Drupal\Core\Session;

use Drupal\Core\Cache\CacheOptionalInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\VariationCacheInterface;

/**
 * Processes access policies into permissions for an account.
 */
class AccessPolicyProcessor implements AccessPolicyProcessorInterface {

  /**
   * The access policies.
   *
   * @var \Drupal\Core\Session\AccessPolicyInterface[]
   */
  protected array $accessPolicies = [];

  public function __construct(
    protected VariationCacheInterface $variationCache,
    protected VariationCacheInterface $variationStatic,
    protected CacheBackendInterface $static,
    protected AccountProxyInterface $currentUser,
    protected AccountSwitcherInterface $accountSwitcher,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function addAccessPolicy(AccessPolicyInterface $access_policy): void {
    $this->accessPolicies[] = $access_policy;
  }

  /**
   * {@inheritdoc}
   */
  public function processAccessPolicies(AccountInterface $account, string $scope = AccessPolicyInterface::SCOPE_DRUPAL): CalculatedPermissionsInterface {
    $persistent_cache_contexts = $this->getPersistentCacheContexts($scope);
    $initial_cacheability = (new CacheableMetadata())->addCacheContexts($persistent_cache_contexts);
    $cache_keys = ['access_policies', $scope];

    // Whether to switch the user account during cache storage and retrieval.
    //
    // This is necessary because permissions may be stored varying by the user
    // cache context or one of its child contexts. Because we may be calculating
    // permissions for an account other than the current user, we need to ensure
    // that the cache ID for said entry is set according to the passed in
    // account's data.
    //
    // Sadly, there is currently no way to reuse the cache context logic outside
    // of the caching layer. If we every get a system that allows us to process
    // cache contexts with a provided environmental value (such as the current
    // user), then we should update the logic below to use that instead.
    //
    // For the time being, we set the current user to the passed in account if
    // they differ, calculate the permissions and then immediately switch back.
    // It's the cleanest solution we could come up with that doesn't involve
    // copying half of the caching layer and that still allows us to use the
    // VariationCache for accounts other than the current user.
    $switch_account = FALSE;
    if ($this->currentUser->id() !== $account->id()) {
      foreach ($persistent_cache_contexts as $cache_context) {
        [$cache_context_root] = explode('.', $cache_context, 2);
        if ($cache_context_root === 'user') {
          $switch_account = TRUE;
          $this->accountSwitcher->switchTo($account);
          break;
        }
      }
    }

    // Wrap the whole cache retrieval or calculation in a try-finally so that we
    // always switch back to the original account after the return statement or
    // if an exception was thrown.
    try {
      // Retrieve the permissions from the static cache if available.
      if ($static_cache = $this->variationStatic->get($cache_keys, $initial_cacheability)) {
        return $static_cache->data;
      }

      // Retrieve the permissions from the persistent cache if available.
      if ($this->needsPersistentCache() && $cache = $this->variationCache->get($cache_keys, $initial_cacheability)) {
        $calculated_permissions = $cache->data;
        $cacheability = CacheableMetadata::createFromObject($calculated_permissions);

        // Convert the calculated permissions into an immutable value object and
        // store it in the static cache so that we don't have to do the same
        // conversion every time we call for the calculated permissions from a
        // warm static cache.
        $calculated_permissions = new CalculatedPermissions($calculated_permissions);
        $this->variationStatic->set($cache_keys, $calculated_permissions, $cacheability, $initial_cacheability);
        return $calculated_permissions;
      }

      // Otherwise build the permissions from scratch.
      // Build mode, allow all access policies to add initial data.
      $calculated_permissions = new RefinableCalculatedPermissions();
      foreach ($this->accessPolicies as $access_policy) {
        if (!$access_policy->applies($scope)) {
          continue;
        }

        $policy_permissions = $access_policy->calculatePermissions($account, $scope);
        if (!$this->validateScope($scope, $policy_permissions)) {
          throw new AccessPolicyScopeException(sprintf('The access policy "%s" returned permissions for scopes other than "%s".', get_class($access_policy), $scope));
        }

        $calculated_permissions = $calculated_permissions->merge($policy_permissions);
      }

      // Alter mode, allow all access policies to alter the complete build.
      foreach ($this->accessPolicies as $access_policy) {
        if (!$access_policy->applies($scope)) {
          continue;
        }

        $access_policy->alterPermissions($account, $scope, $calculated_permissions);
        if (!$this->validateScope($scope, $calculated_permissions)) {
          throw new AccessPolicyScopeException(sprintf('The access policy "%s" altered permissions in a scope other than "%s".', get_class($access_policy), $scope));
        }
      }

      // Apply a cache tag to easily flush the calculated permissions.
      $calculated_permissions->addCacheTags(['access_policies']);

      // First store the actual calculated permissions in the persistent cache,
      // along with the final cache contexts after all calculations have run. We
      // need to store the RefinableCalculatedPermissions in the persistent
      // cache, so we can still get the final cacheability from it for when we
      // run into a persistent cache hit but not a static one. At that point, if
      // we had stored a CalculatedPermissions object, we would no longer be
      // able to ask for its cache contexts.
      $cacheability = CacheableMetadata::createFromObject($calculated_permissions);
      if ($this->needsPersistentCache()) {
        $this->variationCache->set($cache_keys, $calculated_permissions, $cacheability, $initial_cacheability);
      }

      // Then convert the calculated permissions to an immutable value object
      // and store it in the static cache so that we don't have to do the same
      // conversion every time we call for the calculated permissions from a
      // warm static cache.
      $calculated_permissions = new CalculatedPermissions($calculated_permissions);
      $this->variationStatic->set($cache_keys, $calculated_permissions, $cacheability, $initial_cacheability);

      // Return the permissions as an immutable value object.
      return $calculated_permissions;
    }
    finally {
      if ($switch_account) {
        $this->accountSwitcher->switchBack();
      }
    }
  }

  /**
   * Gets the persistent cache contexts of all policies within a given scope.
   *
   * @param string $scope
   *   The scope to get the persistent cache contexts for.
   *
   * @return string[]
   *   The persistent cache contexts of all policies within the scope.
   */
  protected function getPersistentCacheContexts(string $scope): array {
    $cid = 'access_policies:access_policy_processor:contexts:' . $scope;

    // Retrieve the contexts from the regular static cache if available.
    if ($static_cache = $this->static->get($cid)) {
      return $static_cache->data;
    }

    $contexts = [];
    foreach ($this->accessPolicies as $access_policy) {
      if ($access_policy->applies($scope)) {
        $contexts[] = $access_policy->getPersistentCacheContexts();
      }
    }
    $contexts = array_merge(...$contexts);

    // Store the contexts in the regular static cache.
    $this->static->set($cid, $contexts);

    return $contexts;
  }

  /**
   * Validates if calculated permissions all match a single scope.
   *
   * @param string $scope
   *   The scope to match.
   * @param \Drupal\Core\Session\CalculatedPermissionsInterface $calculated_permissions
   *   The calculated permissions that should match the scope.
   *
   * @return bool
   *   Whether the calculated permissions match the scope.
   */
  protected function validateScope(string $scope, CalculatedPermissionsInterface $calculated_permissions): bool {
    $actual_scopes = $calculated_permissions->getScopes();
    return empty($actual_scopes) || $actual_scopes === [$scope];
  }

  /**
   * Returns whether the persistent cache is necessary.
   *
   * @return bool
   *   TRUE if cache should be used (at least one policy requires cache), FALSE
   *   if not.
   */
  protected function needsPersistentCache(): bool {
    foreach ($this->accessPolicies as $access_policy) {
      if (!$access_policy instanceof CacheOptionalInterface) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
