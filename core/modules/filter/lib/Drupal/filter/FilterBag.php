<?php

/**
 * @file
 * Contains \Drupal\filter\FilterBag.
 */

namespace Drupal\filter;

use Drupal\Component\Plugin\PluginBag;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Utility\NestedArray;

/**
 * A collection of filters.
 */
class FilterBag extends PluginBag {

  /**
   * The initial configuration for each filter in the bag.
   *
   * @var array
   *   An associative array containing the initial configuration for each filter
   *   in the bag, keyed by plugin instance ID.
   */
  protected $configurations = array();

  /**
   * The manager used to instantiate the plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * All possible filter plugin IDs.
   *
   * @var array
   */
  protected $definitions;

  /**
   * Constructs a FilterBag object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param array $configurations
   *   (optional) An associative array containing the initial configuration for
   *   each filter in the bag, keyed by plugin instance ID.
   */
  public function __construct(PluginManagerInterface $manager, array $configurations = array()) {
    $this->manager = $manager;
    $this->configurations = $configurations;

    if (!empty($configurations)) {
      $this->instanceIDs = array_combine(array_keys($configurations), array_keys($configurations));
    }
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
    }

    // Ensure that there is an instance of all available filters.
    // Note that getDefinitions() are keyed by $plugin_id. $instance_id is the
    // $plugin_id for filters, since a single filter plugin can only exist once
    // in a format.
    foreach ($this->definitions as $plugin_id => $definition) {
      $this->initializePlugin($plugin_id);
    }
    return $this->pluginInstances;
  }

  /**
   * Updates the configuration for a filter plugin instance.
   *
   * If there is no plugin instance yet, a new will be instantiated. Otherwise,
   * the existing instance is updated with the new configuration.
   *
   * @param string $instance_id
   *   The ID of a filter plugin to set the configuration for.
   * @param array $configuration
   *   The filter plugin configuration to set.
   */
  public function setConfig($instance_id, array $configuration) {
    $this->configurations[$instance_id] = $configuration;
    $this->get($instance_id)->setPluginConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    // If the filter was initialized before, just return.
    if (isset($this->pluginInstances[$instance_id])) {
      return;
    }

    // Filters have a 1:1 relationship to text formats and can be added and
    // instantiated at any time.
    $definition = $this->manager->getDefinition($instance_id);

    if (isset($definition)) {
      $this->addInstanceID($instance_id);
      // $configuration is the whole filter plugin instance configuration, as
      // contained in the text format configuration. The default configuration
      // is the filter plugin definition.
      // @todo Configuration should not be contained in definitions. Move into a
      //   FilterBase::init() method.
      $configuration = $definition;
      // Merge the actual configuration into the default configuration.
      if (isset($this->configurations[$instance_id])) {
        $configuration = NestedArray::mergeDeep($configuration, $this->configurations[$instance_id]);
      }
      $this->pluginInstances[$instance_id] = $this->manager->createInstance($instance_id, $configuration, $this);
    }
    else {
      throw new PluginException(format_string("Unknown filter plugin ID '@filter'.", array('@filter' => $instance_id)));
    }
  }

  /**
   * Sorts all filter plugin instances in this bag.
   *
   * @return \Drupal\filter\FilterBag
   */
  public function sort() {
    $this->getAll();
    uasort($this->instanceIDs, array($this, 'sortHelper'));
    return $this;
  }

  /**
   * uasort() callback to sort filters by status, weight, module, and name.
   *
   * @see \Drupal\filter\FilterFormatStorageController::preSave()
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
    if ($a->module != $b->module) {
      return strnatcasecmp($a->module, $b->module);
    }
    return strnatcasecmp($a->getPluginId(), $b->getPluginId());
  }

  /**
   * Returns the current configuration of all filters in this bag.
   *
   * @return array
   *   An associative array keyed by filter plugin instance ID, whose values
   *   are filter configurations.
   *
   * @see \Drupal\filter\Plugin\Filter\FilterInterface::export()
   */
  public function export() {
    $filters = array();
    $this->rewind();
    foreach ($this as $instance_id => $instance) {
      $filters[$instance_id] = $instance->export();
    }
    return $filters;
  }

}
