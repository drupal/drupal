<?php

namespace Drupal\help;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages help page section plugins.
 *
 * @see \Drupal\help\HelpSectionPluginInterface
 * @see \Drupal\help\Plugin\HelpSection\HelpSectionPluginBase
 * @see \Drupal\help\Annotation\HelpSection
 * @see hook_help_section_info_alter()
 */
class HelpSectionManager extends DefaultPluginManager {

  /**
   * Constructs a new HelpSectionManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler for the alter hook.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/HelpSection', $namespaces, $module_handler, 'Drupal\help\HelpSectionPluginInterface', 'Drupal\help\Annotation\HelpSection');

    $this->alterInfo('help_section_info');
    $this->setCacheBackend($cache_backend, 'help_section_plugins');
  }

}
