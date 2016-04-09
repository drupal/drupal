<?php

namespace Drupal\search\Plugin;

use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides a container for lazily loading search plugins.
 */
class SearchPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The unique ID for the search page using this plugin collection.
   *
   * @var string
   */
  protected $searchPageId;

  /**
   * Constructs a new SearchPluginCollection.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param string $search_page_id
   *   The unique ID of the search page using this plugin.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, $search_page_id) {
    parent::__construct($manager, $instance_id, $configuration);

    $this->searchPageId = $search_page_id;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\search\Plugin\SearchInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    parent::initializePlugin($instance_id);

    $plugin_instance = $this->pluginInstances[$instance_id];
    if ($plugin_instance instanceof ConfigurableSearchPluginInterface) {
      $plugin_instance->setSearchPageId($this->searchPageId);
    }
  }

}
