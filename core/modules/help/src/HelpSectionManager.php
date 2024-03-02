<?php

namespace Drupal\help;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\help\Attribute\HelpSection;
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
   * The search manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected ?PluginManagerInterface $searchManager = NULL;

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
    parent::__construct('Plugin/HelpSection', $namespaces, $module_handler, 'Drupal\help\HelpSectionPluginInterface', HelpSection::class, 'Drupal\help\Annotation\HelpSection');

    $this->alterInfo('help_section_info');
    $this->setCacheBackend($cache_backend, 'help_section_plugins');
  }

  /**
   * Sets the search manager.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface|null $search_manager
   *   The search manager if the Search module is installed.
   */
  public function setSearchManager(?PluginManagerInterface $search_manager = NULL) {
    $this->searchManager = $search_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    parent::clearCachedDefinitions();
    $version = \Drupal::service('update.update_hook_registry')->getInstalledVersion('help');
    if ($this->searchManager && $version >= 10200) {
      // Rebuild the index on cache clear so that new help topics are indexed
      // and any changes due to help topics edits or translation changes are
      // picked up.
      $help_search = $this->searchManager->createInstance('help_search');
      $help_search->markForReindex();
    }
  }

}
