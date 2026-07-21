<?php

declare(strict_types=1);

namespace Drupal\Tests\Traits\Core\Cache;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\PageCache\DefaultRequestPolicy as PageCacheDefaultRequestPolicy;
use Drupal\Core\PageCache\RequestPolicy\NoSessionOpen;
use Drupal\Core\Session\SessionConfigurationInterface;
use Drupal\dynamic_page_cache\PageCache\RequestPolicy\DefaultRequestPolicy as DynamicPageCacheDefaultRequestPolicy;

/**
 * Trait for using page caching modules in Kernel tests.
 *
 * To use Drupal's page caching with HTTP requests in a Kernel test, the
 * following are necessary:
 * - Either or both of the 'page_cache' and 'dynamic_page_cache' modules must be
 *   installed, as desired.
 * - The test class must implement
 *   \Drupal\Core\DependencyInjection\ServiceModifierInterface. This trait
 *   provides an implementation of the alter() method which replaces the cache
 *   policies of the page cache modules.
 * - The 'user' module must be installed if 'dynamic_page_cache' is installed.
 *
 * @see \Drupal\Tests\HttpKernelUiHelperTrait
 */
trait PageCachePolicyTrait {

  /**
   * Sets up mock cache policies.
   *
   * To use this, the test class must implement
   * \Drupal\Core\DependencyInjection\ServiceModifierInterface.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The DI container.
   */
  public function alter(ContainerBuilder $container): void {
    if ($container->hasDefinition('page_cache_request_policy')) {
      $service_definition = $container->getDefinition('page_cache_request_policy');
      $service_definition->setClass(KernelTestPageCacheRequestPolicy::class);
    }

    if ($container->hasDefinition('dynamic_page_cache_request_policy')) {
      $service_definition = $container->getDefinition('dynamic_page_cache_request_policy');
      $service_definition->setClass(KernelTestDynamicPageCacheRequestPolicy::class);
    }
  }

}

/**
 * Replaces the page_cache module's default request policy.
 */
class KernelTestPageCacheRequestPolicy extends PageCacheDefaultRequestPolicy {

  public function __construct(SessionConfigurationInterface $session_configuration) {
    $this->addPolicy(new NoSessionOpen($session_configuration));
  }

}

/**
 * Replaces the dynamic_page_cache module's default request policy.
 */
class KernelTestDynamicPageCacheRequestPolicy extends DynamicPageCacheDefaultRequestPolicy {

  public function __construct() {
  }

}
