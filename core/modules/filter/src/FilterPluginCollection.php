<?php

/**
 * @file
 * Contains \Drupal\filter\FilterPluginCollection.
 */

namespace Drupal\filter;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of filters.
 */
class FilterPluginCollection extends DefaultLazyPluginCollection {

  /**
   * All possible filter plugin IDs.
   *
   * @var array
   */
  protected $definitions;

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\filter\Plugin\FilterInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * Retrieves filter definitions and creates an instance for each filter.
   *
   * This is exclusively used for the text format administration page, on which
   * all available filter plugins are exposed, regardless of whether the current
   * text format has an active instance.
   *
   * @todo Refactor text format administration to actually construct/create and
   *   destruct/remove actual filter plugin instances, using a library approach
   *   à la blocks.
   */
  public function getAll() {
    // Retrieve all available filter plugin definitions.
    if (!$this->definitions) {
      $this->definitions = $this->manager->getDefinitions();
      // Do not allow the null filter to be used directly, only as a fallback.
      unset($this->definitions['filter_null']);
    }

    // Ensure that there is an instance of all available filters.
    // Note that getDefinitions() are keyed by $plugin_id. $instance_id is the
    // $plugin_id for filters, since a single filter plugin can only exist once
    // in a format.
    foreach ($this->definitions as $plugin_id => $definition) {
      if (!isset($this->pluginInstances[$plugin_id])) {
        $this->initializePlugin($plugin_id);
      }
    }
    return $this->pluginInstances;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    // Filters have a 1:1 relationship to text formats and can be added and
    // instantiated at any time.
    // @todo $configuration is the whole filter plugin instance configuration,
    //   as contained in the text format configuration. The default
    //   configuration is the filter plugin definition. Configuration should not
    //   be contained in definitions. Move into a FilterBase::init() method.
    $configuration = $this->manager->getDefinition($instance_id);
    // Merge the actual configuration into the default configuration.
    if (isset($this->configurations[$instance_id])) {
      $configuration = NestedArray::mergeDeep($configuration, $this->configurations[$instance_id]);
    }
    $this->configurations[$instance_id] = $configuration;
    parent::initializePlugin($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function sort() {
    $this->getAll();
    return parent::sort();
  }

  /**
   * {@inheritdoc}
   */
  public function sortHelper($aID, $bID) {
    $a = $this->get($aID);
    $b = $this->get($bID);
    if ($a->status != $b->status) {
      return !empty($a->status) ? -1 : 1;
    }
    if ($a->weight != $b->weight) {
      return $a->weight < $b->weight ? -1 : 1;
    }
    if ($a->provider != $b->provider) {
      return strnatcasecmp($a->provider, $b->provider);
    }
    return parent::sortHelper($aID, $bID);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();
    // Remove configuration if it matches the defaults. In self::getAll(), we
    // load all available filters, in addition to the enabled filters stored in
    // configuration. In order to prevent those from bleeding through to the
    // stored configuration, remove all filters that match the default values.
    // Because filters are disabled by default, this will never remove the
    // configuration of an enabled filter.
    foreach ($configuration as $instance_id => $instance_config) {
      $default_config = array();
      $default_config['id'] = $instance_id;
      $default_config += $this->get($instance_id)->defaultConfiguration();
      if ($default_config === $instance_config) {
        unset($configuration[$instance_id]);
      }
    }
    return $configuration;
  }

}
