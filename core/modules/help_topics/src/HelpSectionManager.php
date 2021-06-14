<?php

namespace Drupal\help_topics;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\help\HelpSectionManager as CoreHelpSectionManager;

/**
 * Decorates the Help Section plugin manager to provide help topic search.
 *
 * @internal
 *   Help Topics is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class HelpSectionManager extends CoreHelpSectionManager {

  /**
   * The search manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $searchManager;

  /**
   * Sets the search manager.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface|null $search_manager
   *   The search manager if the Search module is installed.
   */
  public function setSearchManager(PluginManagerInterface $search_manager = NULL) {
    $this->searchManager = $search_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    parent::clearCachedDefinitions();
    if ($this->searchManager && $this->searchManager->hasDefinition('help_search') && $this->moduleHandler->moduleExists('help_topics')) {
      // Rebuild the index on cache clear so that new help topics are indexed
      // and any changes due to help topics edits or translation changes are
      // picked up.
      $help_search = $this->searchManager->createInstance('help_search');
      $help_search->markForReindex();
    }
  }

}
