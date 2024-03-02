<?php

namespace Drupal\language;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\language\Attribute\LanguageNegotiation;

/**
 * Manages language negotiation methods.
 */
class LanguageNegotiationMethodManager extends DefaultPluginManager {

  /**
   * Constructs a new LanguageNegotiationMethodManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   An object that implements CacheBackendInterface
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   An object that implements ModuleHandlerInterface
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/LanguageNegotiation', $namespaces, $module_handler, 'Drupal\language\LanguageNegotiationMethodInterface', LanguageNegotiation::class, 'Drupal\language\Annotation\LanguageNegotiation');
    $this->cacheBackend = $cache_backend;
    $this->setCacheBackend($cache_backend, 'language_negotiation_plugins');
    $this->alterInfo('language_negotiation_info');
  }

}
