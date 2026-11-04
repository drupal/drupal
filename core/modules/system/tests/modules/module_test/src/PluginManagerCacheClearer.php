<?php

declare(strict_types=1);

namespace Drupal\module_test;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\State\StateInterface;

/**
 * Helps test module uninstall.
 */
class PluginManagerCacheClearer extends DefaultPluginManager {

  /**
   * PluginManagerCacheClearer constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service for recording what happens.
   * @param null $optionalService
   *   An optional service for testing.
   */
  public function __construct(
    protected StateInterface $state,
    protected $optionalService = NULL,
  ) {
  }

  /**
   * Tests call to CachedDiscoveryInterface::clearCachedDefinitions().
   *
   * @see \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface::clearCachedDefinitions()
   */
  public function clearCachedDefinitions(): void {
    $this->state->set(self::class, isset($this->optionalService));
  }

}
