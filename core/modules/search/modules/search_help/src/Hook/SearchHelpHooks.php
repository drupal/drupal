<?php

declare(strict_types=1);

namespace Drupal\search_help\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Plugin\CachedDiscoveryClearerInterface;
use Drupal\search\SearchPluginManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hook implementations for help.
 */
class SearchHelpHooks {

  public function __construct(
    #[Autowire(service: 'plugin.manager.search')]
    protected readonly SearchPluginManager $searchManager,
    #[Autowire(service: 'plugin.cache_clearer')]
    protected readonly CachedDiscoveryClearerInterface $pluginCacheClearer,
  ) {}

  /**
   * Implements hook_modules_uninstalled().
   */
  #[Hook('modules_uninstalled')]
  public function modulesUninstalled(array $modules): void {
    $this->searchUpdate($modules);
  }

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled(array $modules, $is_syncing): void {
    $this->searchUpdate();
  }

  /**
   * Implements hook_themes_installed().
   *
   * Implements hook_themes_uninstalled().
   */
  #[Hook('themes_installed')]
  #[Hook('themes_uninstalled')]
  public function themesInstallOrUninstall(array $themes): void {
    $this->pluginCacheClearer->clearCachedDefinitions();
    $this->searchUpdate();
  }

  /**
   * Implements hook_rebuild().
   */
  #[Hook('rebuild')]
  public function rebuild(): void {
    if ($this->searchManager->hasDefinition('help_search')) {
      $help_search = $this->searchManager->createInstance('help_search');
      $help_search->markForReindex();
    }
  }

  /**
   * Ensure that search is updated when extensions are installed or uninstalled.
   *
   * @param string[] $extensions
   *   (optional) If modules are being uninstalled, the names of the modules
   *   being uninstalled. For themes being installed/uninstalled, or modules
   *   being installed, omit this parameter.
   */
  protected function searchUpdate(array $extensions = []): void {
    // Early return if we're uninstalling this module.
    if (in_array('search_help', $extensions)) {
      return;
    }

    // Ensure that topics for extensions that have been uninstalled are removed
    // and that the index state variable is updated.
    $help_search = $this->searchManager->createInstance('help_search');
    $help_search->updateTopicList();
    $help_search->updateIndexState();
  }

}
