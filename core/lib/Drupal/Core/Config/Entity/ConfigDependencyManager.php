<?php

namespace Drupal\Core\Config\Entity;

use Drupal\Component\Graph\Graph;
use Drupal\Component\Utility\SortArray;

/**
 * Provides a class to discover configuration entity dependencies.
 *
 * Configuration entities can depend on modules, themes and other configuration
 * entities. The dependency system is used during configuration installation,
 * uninstallation, and synchronization to ensure that configuration entities are
 * handled in the correct order. For example, node types are created before
 * their fields, and both are created before the view display configuration.
 *
 * The configuration dependency value is structured like this:
 * @code
 * array(
 *   'config' => array(
 *     // An array of configuration entity object names. Recalculated on save.
 *   ),
 *   'content' => array(
 *     // An array of content entity configuration dependency names. The default
 *     // format is "ENTITY_TYPE_ID:BUNDLE:UUID". Recalculated on save.
 *   ),
 *   'module' => array(
 *     // An array of module names. Recalculated on save.
 *   ),
 *   'theme' => array(
 *     // An array of theme names. Recalculated on save.
 *   ),
 *   'enforced' => array(
 *     // An array of configuration dependencies that the config entity is
 *     // ensured to have regardless of the details of the configuration. These
 *     // dependencies are not recalculated on save.
 *     'config' => array(),
 *     'content' => array(),
 *     'module' => array(),
 *     'theme' => array(),
 *   ),
 * );
 * @endcode
 *
 * Configuration entity dependencies are recalculated on save based on the
 * current values of the configuration. For example, a filter format will depend
 * on the modules that provide the filter plugins it configures. The filter
 * format can be reconfigured to use a different filter plugin provided by
 * another module. If this occurs, the dependencies will be recalculated on save
 * and the old module will be removed from the list of dependencies and replaced
 * with the new one.
 *
 * Configuration entity classes usually extend
 * \Drupal\Core\Config\Entity\ConfigEntityBase. The base class provides a
 * generic implementation of the calculateDependencies() method that can
 * discover dependencies due to plugins, and third party settings. If the
 * configuration entity has dependencies that cannot be discovered by the base
 * class's implementation, then it needs to implement
 * \Drupal\Core\Config\Entity\ConfigEntityInterface::calculateDependencies() to
 * calculate the dependencies. In this method, use
 * \Drupal\Core\Config\Entity\ConfigEntityBase::addDependency() to add
 * dependencies. Implementations should call the base class implementation to
 * inherit the generic functionality.
 *
 * Classes for configurable plugins are a special case. They can either declare
 * their configuration dependencies using the calculateDependencies() method
 * described in the paragraph above, or if they have only static dependencies,
 * these can be declared using the 'config_dependencies' annotation key.
 *
 * If an extension author wants a configuration entity to depend on something
 * that is not calculable then they can add these dependencies to the enforced
 * dependencies key. For example, the Forum module provides the forum node type
 * and in order for it to be deleted when the forum module is uninstalled it has
 * an enforced dependency on the module. The dependency on the Forum module can
 * not be calculated since there is nothing inherent in the state of the node
 * type configuration entity that depends on functionality provided by the Forum
 * module.
 *
 * Once declared properly, dependencies are saved to the configuration entity's
 * configuration object so that they can be checked without the module that
 * provides the configuration entity class being installed. This is important
 * for configuration synchronization, which needs to be able to validate
 * configuration in the sync directory before the synchronization has occurred.
 * Also, if you have a configuration entity object and you want to get the
 * current dependencies (without recalculation), you can use
 * \Drupal\Core\Config\Entity\ConfigEntityInterface::getDependencies().
 *
 * When uninstalling a module or a theme, configuration entities that are
 * dependent will also be removed. This default behavior can lead to undesirable
 * side effects, such as a node view mode being entirely removed when the module
 * defining a field or formatter it uses is uninstalled. To prevent this,
 * configuration entity classes can implement
 * \Drupal\Core\Config\Entity\ConfigEntityInterface::onDependencyRemoval(),
 * which allows the entity class to remove dependencies instead of being deleted
 * themselves. Implementations should save the entity if dependencies have been
 * successfully removed, in order to register the newly cleaned-out dependency
 * list. So, for example, the node view mode configuration entity class
 * should implement this method to remove references to formatters if the plugin
 * that supplies them depends on a module that is being uninstalled.
 *
 * If a configuration entity is provided as default configuration by an
 * extension (module, theme, or profile), the extension has to depend on any
 * modules or themes that the configuration depends on. For example, if a view
 * configuration entity is provided by an installation profile and the view will
 * not work without a certain module, the profile must declare a dependency on
 * this module in its info.yml file. If you do not want your extension to always
 * depend on a particular module that one of its default configuration entities
 * depends on, you can use a sub-module: move the configuration entity to the
 * sub-module instead of including it in the main extension, and declare the
 * module dependency in the sub-module only.
 *
 * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::calculateDependencies()
 * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::getDependencies()
 * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::onDependencyRemoval()
 * @see \Drupal\Core\Config\Entity\ConfigEntityBase::addDependency()
 * @see \Drupal\Core\Config\ConfigInstallerInterface::installDefaultConfig()
 * @see \Drupal\Core\Config\ConfigManagerInterface::uninstall()
 * @see \Drupal\Core\Config\Entity\ConfigEntityDependency
 * @see \Drupal\Core\Entity\EntityInterface::getConfigDependencyName()
 * @see \Drupal\Core\Plugin\PluginDependencyTrait
 */
class ConfigDependencyManager {

  /**
   * The config entity data.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityDependency[]
   */
  protected $data = [];

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
   *   The type of dependency being checked. Either 'module', 'theme', 'config'
   *   or 'content'.
   * @param string $name
   *   The specific name to check. If $type equals 'module' or 'theme' then it
   *   should be a module name or theme name. In the case of entity it should be
   *   the full configuration object name.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityDependency[]
   *   An array of config entity dependency objects that are dependent.
   */
  public function getDependentEntities($type, $name) {
    $dependent_entities = [];

    $entities_to_check = [];
    if ($type == 'config') {
      $entities_to_check[] = $name;
    }
    else {
      if ($type == 'module' || $type == 'theme' || $type == 'content') {
        $dependent_entities = array_filter($this->data, function (ConfigEntityDependency $entity) use ($type, $name) {
          return $entity->hasDependency($type, $name);
        });
      }
      // If checking content, module, or theme dependencies, discover which
      // entities are dependent on the entities that have a direct dependency.
      foreach ($dependent_entities as $entity) {
        $entities_to_check[] = $entity->getConfigDependencyName();
      }
    }
    $dependencies = array_merge($this->createGraphConfigEntityDependencies($entities_to_check), $dependent_entities);
    // Sort dependencies in the reverse order of the graph. So the least
    // dependent is at the top. For example, this ensures that fields are
    // always after field storages. This is because field storages need to be
    // created before a field.
    $graph = $this->getGraph();
    $sorts = $this->prepareMultisort($graph, ['weight', 'name']);
    array_multisort($sorts['weight'], SORT_DESC, SORT_NUMERIC, $sorts['name'], SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE, $graph);
    return array_replace(array_intersect_key($graph, $dependencies), $dependencies);
  }

  /**
   * Extracts data from the graph for use in array_multisort().
   *
   * @param array $graph
   *   The graph to extract data from.
   * @param array $keys
   *   The keys whose values to extract.
   *
   * @return
   *   An array keyed by the $keys passed in. The values are arrays keyed by the
   *   row from the graph and the value is the corresponding value for the key
   *   from the graph.
   */
  protected function prepareMultisort($graph, $keys) {
    $return = array_fill_keys($keys, []);
    foreach ($graph as $graph_key => $graph_row) {
      foreach ($keys as $key) {
        $return[$key][$graph_key] = $graph_row[$key];
      }
    }
    return $return;
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
    // Sort by weight and alphabetically. The most dependent entities
    // are last and entities with the same weight are alphabetically ordered.
    $sorts = $this->prepareMultisort($graph, ['weight', 'name']);
    array_multisort($sorts['weight'], SORT_ASC, SORT_NUMERIC, $sorts['name'], SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE, $graph);
    // Use array_intersect_key() to exclude modules and themes from the list.
    return array_keys(array_intersect_key($graph, $this->data));
  }

  /**
   * Sorts the dependency graph by weight and alphabetically.
   *
   * @deprecated in Drupal 8.2.0, will be removed before Drupal 9.0.0. Use
   * \Drupal\Core\Config\Entity\ConfigDependencyManager::prepareMultisort() and
   * array_multisort() instead.
   *
   * @param array $a
   *   First item for comparison. The compared items should be associative
   *   arrays that include a 'weight' and a 'name' key.
   * @param array $b
   *   Second item for comparison.
   *
   * @return int
   *   The comparison result for uasort().
   */
  protected static function sortGraphByWeight(array $a, array $b) {
    $weight_cmp = SortArray::sortByKeyInt($a, $b, 'weight');

    if ($weight_cmp === 0) {
      return SortArray::sortByKeyString($a, $b, 'name');
    }
    return $weight_cmp;
  }

  /**
   * Sorts the dependency graph by reverse weight and alphabetically.
   *
   * @deprecated in Drupal 8.2.0, will be removed before Drupal 9.0.0. Use
   * \Drupal\Core\Config\Entity\ConfigDependencyManager::prepareMultisort() and
   * array_multisort() instead.
   *
   * @param array $a
   *   First item for comparison. The compared items should be associative
   *   arrays that include a 'weight' and a 'name' key.
   * @param array $b
   *   Second item for comparison.
   *
   * @return int
   *   The comparison result for uasort().
   */
  public static function sortGraph(array $a, array $b) {
    $weight_cmp = SortArray::sortByKeyInt($a, $b, 'weight') * -1;

    if ($weight_cmp === 0) {
      return SortArray::sortByKeyString($a, $b, 'name');
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
    $dependent_entities = [];
    $graph = $this->getGraph();

    foreach ($entities_to_check as $entity) {
      if (isset($graph[$entity]) && !empty($graph[$entity]['paths'])) {
        foreach ($graph[$entity]['paths'] as $dependency => $value) {
          if (isset($this->data[$dependency])) {
            $dependent_entities[$dependency] = $this->data[$dependency];
          }
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
      $graph = [];
      foreach ($this->data as $entity) {
        $graph_key = $entity->getConfigDependencyName();
        if (!isset($graph[$graph_key])) {
          $graph[$graph_key] = [
            'edges' => [],
            'name' => $graph_key,
          ];
        }
        // Include all dependencies in the graph so that topographical sorting
        // works.
        foreach (array_merge($entity->getDependencies('config'), $entity->getDependencies('module'), $entity->getDependencies('theme')) as $dependency) {
          $graph[$dependency]['edges'][$graph_key] = TRUE;
          $graph[$dependency]['name'] = $dependency;
        }
      }
      // Ensure that order of the graph is consistent.
      krsort($graph);
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

  /**
   * Updates one of the lightweight ConfigEntityDependency objects.
   *
   * @param $name
   *   The configuration dependency name.
   * @param array $dependencies
   *   The configuration dependencies. The array is structured like this:
   *   @code
   *   array(
   *     'config' => array(
   *       // An array of configuration entity object names.
   *     ),
   *     'content' => array(
   *       // An array of content entity configuration dependency names. The default
   *       // format is "ENTITY_TYPE_ID:BUNDLE:UUID".
   *     ),
   *     'module' => array(
   *       // An array of module names.
   *     ),
   *     'theme' => array(
   *       // An array of theme names.
   *     ),
   *   );
   *   @endcode
   *
   * @return $this
   */
  public function updateData($name, array $dependencies) {
    $this->graph = NULL;
    $this->data[$name] = new ConfigEntityDependency($name, ['dependencies' => $dependencies]);
    return $this;
  }

}
