<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\ConfigDependencyManager.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Component\Graph\Graph;
use Drupal\Component\Utility\SortArray;

/**
 * Provides a class to discover configuration entity dependencies.
 *
 * Configuration entities can depend on modules, themes and other configuration
 * entities. The dependency system is used during configuration installation to
 * ensure that configuration entities are imported in the correct order. For
 * example, node types are created before their field storages and the field
 * storages are created before their fields.
 *
 * Dependencies are stored to the configuration entity's configuration object so
 * that they can be checked without the module that provides the configuration
 * entity class being installed. This is important for configuration
 * synchronization which needs to be able to validate configuration in the
 * staging directory before the synchronization has occurred.
 *
 * Configuration entities determine their dependencies by implementing
 * \Drupal\Core\Config\Entity\ConfigEntityInterface::calculateDependencies().
 * This method should be called from the configuration entity's implementation
 * of \Drupal\Core\Entity\EntityInterface::preSave(). Implementations should use
 * the helper method
 * \Drupal\Core\Config\Entity\ConfigEntityBase::addDependency() to add
 * dependencies. All the implementations in core call the parent method
 * \Drupal\Core\Config\Entity\ConfigEntityBase::calculateDependencies() which
 * resets the dependencies and provides an implementation to determine the
 * plugin providers for configuration entities that implement
 * \Drupal\Core\Entity\EntityWithPluginBagsInterface.
 *
 * The configuration manager service provides methods to find dependencies for
 * a specified module, theme or configuration entity.
 *
 * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::calculateDependencies()
 * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::getConfigDependencyName()
 * @see \Drupal\Core\Config\Entity\ConfigEntityBase::addDependency()
 * @see \Drupal\Core\Config\ConfigInstaller::installDefaultConfig()
 * @see \Drupal\Core\Config\Entity\ConfigEntityDependency
 */
class ConfigDependencyManager {

  /**
   * The config entity data.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityDependency[]
   */
  protected $data = array();

  /**
   * The directed acyclic graph.
   *
   * @var array
   */
  protected $graph;

  /**
   * Gets dependencies.
   *
   * @param string $type
   *   The type of dependency being checked. Either 'module', 'theme', 'entity'.
   * @param string $name
   *   The specific name to check. If $type equals 'module' or 'theme' then it
   *   should be a module name or theme name. In the case of entity it should be
   *   the full configuration object name.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityDependency[]
   *   An array of config entity dependency objects that are dependent.
   */
  public function getDependentEntities($type, $name) {
    $dependent_entities = array();

    $entities_to_check = array();
    if ($type == 'entity') {
      $entities_to_check[] = $name;
    }
    else {
      if ($type == 'module' || $type ==  'theme') {
        $dependent_entities = array_filter($this->data, function (ConfigEntityDependency $entity) use ($type, $name) {
          return $entity->hasDependency($type, $name);
        });
      }
      // If checking module or theme dependencies then discover which entities
      // are dependent on the entities that have a direct dependency.
      foreach ($dependent_entities as $entity) {
        $entities_to_check[] =  $entity->getConfigDependencyName();
      }
    }

    return array_merge($dependent_entities, $this->createGraphConfigEntityDependencies($entities_to_check));
  }

  /**
   * Sorts the dependencies in order of most dependent last.
   *
   * @return array
   *   The list of entities in order of most dependent last, otherwise
   *   alphabetical.
   */
  public function sortAll() {
    $graph = $this->getGraph();
    // Sort by reverse weight and alphabetically. The most dependent entities
    // are last and entities with the same weight are alphabetically ordered.
    uasort($graph, array($this, 'sortGraph'));
    return array_keys($graph);
  }

  /**
   * Sorts the dependency graph by reverse weight and alphabetically.
   *
   * @param array $a
   *   First item for comparison. The compared items should be associative
   *   arrays that include a 'weight' and a 'component' key.
   * @param array $b
   *   Second item for comparison.
   *
   * @return int
   *   The comparison result for uasort().
   */
  public function sortGraph(array $a, array $b) {
    $weight_cmp = SortArray::sortByKeyInt($a, $b, 'weight') * -1;

    if ($weight_cmp === 0) {
      return SortArray::sortByKeyString($a, $b, 'component');
    }
    return $weight_cmp;
  }

  /**
   * Creates a graph of config entity dependencies.
   *
   * @param array $entities_to_check
   *   The configuration entity full configuration names to determine the
   *   dependencies for.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityDependency[]
   *   A graph of config entity dependency objects that are dependent on the
   *   supplied entities to check.
   */
  protected function createGraphConfigEntityDependencies($entities_to_check) {
    $dependent_entities = array();
    $graph = $this->getGraph();

    foreach ($entities_to_check as $entity) {
      if (isset($graph[$entity]) && !empty($graph[$entity]['reverse_paths'])){
        foreach ($graph[$entity]['reverse_paths'] as $dependency => $value) {
          $dependent_entities[$dependency] = $this->data[$dependency];
        }
      }
    }
    return $dependent_entities;
  }

  /**
   * Gets the dependency graph of all the config entities.
   *
   * @return array
   *   The dependency graph of all the config entities.
   */
  protected function getGraph() {
    if (!isset($this->graph)) {
      $graph = array();
      foreach ($this->data as $entity) {
        $graph_key = $entity->getConfigDependencyName();
        $graph[$graph_key]['edges'] = array();
        $dependencies = $entity->getDependencies('entity');
        if (!empty($dependencies)) {
          foreach ($dependencies as $dependency) {
            $graph[$graph_key]['edges'][$dependency] = TRUE;
          }
        }
      }
      $graph_object = new Graph($graph);
      $this->graph = $graph_object->searchAndSort();
    }
    return $this->graph;
  }

  /**
   * Sets data to calculate dependencies for.
   *
   * The data is converted into lightweight ConfigEntityDependency objects.
   *
   * @param array $data
   *   Configuration data keyed by configuration object name. Typically the
   *   output of \Drupal\Core\Config\StorageInterface::loadMultiple().
   *
   * @return $this
   */
  public function setData(array $data) {
    array_walk($data, function (&$config, $name) {
      $config = new ConfigEntityDependency($name, $config);
    });
    $this->data = $data;
    $this->graph = NULL;
    return $this;
  }

}
