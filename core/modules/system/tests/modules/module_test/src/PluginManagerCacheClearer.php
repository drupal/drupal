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
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * An optional service dependency.
   *
   * @var object|null
   */
  protected $optionalService;

  /**
   * PluginManagerCacheClearer constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service for recording what happens.
   * @param null $optional_service
   *   An optional service for testing.
   */
  public function __construct(StateInterface $state, $optional_service = NULL) {
    $this->state = $state;
    $this->optionalService = $optional_service;
  }

  /**
   * Tests call to CachedDiscoveryInterface::clearCachedDefinitions().
   *
   * @see \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface::clearCachedDefinitions()
   */
  public function clearCachedDefinitions() {
    $this->state->set(self::class, isset($this->optionalService));
  }

}
