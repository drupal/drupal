<?php

namespace Drupal\Core\PreWarm;

use Drupal\Core\DependencyInjection\ClassResolverInterface;

// cspell:ignore ABCDEF FCDABE BEDAFC

/**
 * Prewarms caches for services that implement PreWarmableInterface.
 *
 * Takes a list of prewarmable services and prewarms them at random.
 * Randomization is used because whenever two or more requests are building
 * caches, the most benefit is gained by minimizing duplication. For example
 * two requests rely on the same six services but these services are requested
 * at different times, one request builds caches for the other and vice versa.
 *
 * No randomization:
 *
 * ABCDEF
 * ABCDEF
 *
 * Randomization:
 *
 * ABCDEF
 * FCDABE
 *
 * Randomization and three requests:
 *
 * ABCDEF
 * FCDABE
 * BEDAFC
 *
 * @internal
 *
 * @see Drupal\Core\PreWarm\PreWarmableInterface
 * @see Drupal\Core\DrupalKernel::handle()
 * @see Drupal\Core\LockBackendAbstract::wait()
 * @see Drupal\Core\Routing\RouteProvider::preLoadRoutes()
 */
class CachePreWarmer implements CachePreWarmerInterface {

  /**
   * Whether to prewarm caches at the end of the request.
   */
  protected bool $needsPreWarming = FALSE;

  public function __construct(
    protected readonly ClassResolverInterface $classResolver,
    protected array $serviceIds,
  ) {
    // Ensure the serviceId order is random to reduce chances of conflicts.
    shuffle($this->serviceIds);
  }

  /**
   * {@inheritdoc}
   */
  public function preWarmOneCache(): bool {
    $candidate = array_pop($this->serviceIds);
    if ($candidate === NULL) {
      return FALSE;
    }
    $service = $this->classResolver->getInstanceFromDefinition($candidate);
    $service->preWarm();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function preWarmAllCaches(): bool {
    $prewarmed = FALSE;
    while ($this->preWarmOneCache()) {
      $prewarmed = TRUE;
    }
    return $prewarmed;
  }

}
