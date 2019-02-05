<?php

namespace Drupal\search\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for a configurable Search plugin.
 */
interface ConfigurableSearchPluginInterface extends ConfigurablePluginInterface, PluginFormInterface, SearchInterface {

  /**
   * Sets the ID for the search page using this plugin.
   *
   * @param string $search_page_id
   *   The search page ID.
   *
   * @return static
   */
  public function setSearchPageId($search_page_id);

}
