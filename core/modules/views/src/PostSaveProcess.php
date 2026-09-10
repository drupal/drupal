<?php

namespace Drupal\views;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;

/**
 * Defines the post save process value object.
 */
class PostSaveProcess {

  /**
   * TRUE once one plugin flags that the router needs rebuilding.
   */
  protected bool $routerRebuild = FALSE;

  /**
   * Plugin managers queued to have their cached definitions cleared.
   *
   * @var \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface[]
   */
  protected array $discoveriesToClear = [];

  /**
   * Sets routerRebuild to true.
   */
  public function setRouterRebuild(): void {
    $this->routerRebuild = TRUE;
  }

  /**
   * Does the router need rebuilding?
   */
  public function needsRouterRebuild(): bool {
    return $this->routerRebuild;
  }

  /**
   * Queues a plugin manager to have its cached definitions cleared.
   *
   * @param \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface $discovery
   *   The plugin manager (or other discovery) to clear.
   */
  public function addDiscoveryToClear(CachedDiscoveryInterface $discovery): void {
    $this->discoveriesToClear[get_class($discovery)] = $discovery;
  }

  /**
   * Gets all plugin managers queued to have their caches cleared.
   *
   * @return \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface[]
   *   The queued discoveries, keyed by class name.
   */
  public function getDiscoveriesToClear(): array {
    return $this->discoveriesToClear;
  }

}
