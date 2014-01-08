<?php

/**
 * @file
 * Contains \Drupal\search\Plugin\SearchPluginBag.
 */

namespace Drupal\search\Plugin;

use Drupal\Component\Plugin\DefaultSinglePluginBag;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides a container for lazily loading search plugins.
 */
class SearchPluginBag extends DefaultSinglePluginBag {

  /**
   * The unique ID for the search page using this plugin bag.
   *
   * @var string
   */
  protected $searchPageId;

  /**
   * {@inheritdoc}
   */
  public function __construct(PluginManagerInterface $manager, array $instance_ids, array $configuration, $search_page_id) {
    parent::__construct($manager, $instance_ids, $configuration);

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
