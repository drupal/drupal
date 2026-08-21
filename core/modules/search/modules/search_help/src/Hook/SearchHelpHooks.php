<?php

declare(strict_types=1);

namespace Drupal\search_help\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\RemoveHook;
use Drupal\Core\Plugin\CachedDiscoveryClearerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\help\Hook\HelpHooks;
use Drupal\search\SearchPluginManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hook implementations for help.
 */
class SearchHelpHooks {

  use StringTranslationTrait;

  public function __construct(
    #[Autowire(service: 'plugin.manager.search')]
    protected readonly SearchPluginManager $searchManager,
    #[Autowire(service: 'plugin.cache_clearer')]
    protected readonly CachedDiscoveryClearerInterface $pluginCacheClearer,
    protected readonly HelpHooks $helpHooks,
  ) {}

  /**
   * Implements hook_help() on behalf of the help module.
   *
   * \Drupal\help\Controller\HelpController::helpPage() only invokes the
   * implementation of the module whose page is requested, so the section about
   * help search has to be part of the help module implementation. This method
   * replaces it: it returns the original output and appends the section.
   */
  #[Hook('help', module: 'help')]
  #[RemoveHook('help', class: HelpHooks::class, method: 'help')]
  public function help($route_name, RouteMatchInterface $route_match): string|array|null {
    $output = $this->helpHooks->help($route_name, $route_match);
    if ($route_name !== 'help.page.help') {
      return $output;
    }
    $search_help = Url::fromRoute('help.page', ['name' => 'search'])->toString();
    // Remove that trailing close description list tag.
    $output['#markup'] = rtrim($output['#markup'], "</dl>");
    $section = '<dt>' . $this->t('Configuring help search') . '</dt>';
    $section .= '<dd>' . $this->t('To search help, configure a search page and add a search block to the Help page or another administrative page. (A search page is provided automatically, and if you use the core Administrative theme, a help search block is shown on the main Help page.) Then users with search permissions, and permission to view help, will be able to search help. See the <a href=":search_help">Search module help page</a> for more information.', [':search_help' => $search_help]) . '</dd>';
    $section .= '</dl>';
    $output['#markup'] .= $section;
    return $output;
  }

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
