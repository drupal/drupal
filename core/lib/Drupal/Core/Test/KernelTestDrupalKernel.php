<?php

declare(strict_types=1);

namespace Drupal\Core\Test;

use Drupal\Core\DrupalKernel;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Subclass of DrupalKernel for kernel testing.
 *
 * This class extends the DrupalKernel to allow re-use of the dependency
 * injection container between kernel test methods.
 *
 * @internal only use in tests.
 */
class KernelTestDrupalKernel extends DrupalKernel {

  /**
   * Whether the container should be retrieved from cache.
   *
   * Only overrides when it's TRUE, FALSE does nothing.
   */
  protected bool $overrideUseCache = FALSE;

  /**
   * A cache suffix to increment on rebuilds.
   */
  protected int $cacheSuffix = 0;

  /**
   * Sets whether the container should be rebuilt.
   */
  public function setContainerNeedsRebuild(bool $rebuild): void {
    $this->containerNeedsRebuild = $rebuild;
  }

  /**
   * Resets the cache suffix to 0.
   */
  public function resetCacheSuffix(): void {
    $this->cacheSuffix = 0;
  }

  /**
   * Sets whether the container should be retrieved from cache.
   */
  public function setOverrideUseCache(bool $use): void {
    $this->overrideUseCache = $use;
  }

  /**
   * {@inheritdoc}
   */
  protected function getContainerCacheKey() {
    $key = parent::getContainerCacheKey();
    // Vary by the suffix, so that a container rebuilt during a test method is
    // cached under its own key and does not overwrite the initial container
    // that the other test methods of the class load.
    $key .= ':' . $this->cacheSuffix;
    return $key;
  }

  /**
   * {@inheritdoc}
   */
  protected function getContainerFromCacheIfCacheable(): ?array {
    if ($this->overrideUseCache) {
      // Only override once.
      $this->overrideUseCache = FALSE;
      return $this->getCachedContainerDefinition();
    }
    return parent::getContainerFromCacheIfCacheable();
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildContainer() {
    $this->cacheSuffix++;
    return parent::rebuildContainer();
  }

  /**
   * {@inheritdoc}
   */
  public function resetContainer(): ContainerInterface {
    $this->cacheSuffix++;
    return parent::resetContainer();
  }

}
