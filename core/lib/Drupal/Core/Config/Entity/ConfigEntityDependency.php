<?php

namespace Drupal\Core\Config\Entity;

use Drupal\Component\Utility\NestedArray;

/**
 * Provides a value object to discover configuration dependencies.
 *
 * @see \Drupal\Core\Config\Entity\ConfigDependencyManager
 */
class ConfigEntityDependency {

  /**
   * The configuration entity's configuration object name.
   *
   * @var string
   */
  protected $name;

  /**
   * The configuration entity's dependencies.
   *
   * @var array
   */
  protected $dependencies = [];

  /**
   * Constructs the configuration entity dependency from the entity values.
   *
   * @param string $name
   *   The configuration entity's configuration object name.
   * @param array $values
   *   (optional) The configuration entity's values.
   */
  public function __construct($name, $values = []) {
    $this->name = $name;
    if (isset($values['dependencies']) && isset($values['dependencies']['enforced'])) {
      // Merge the enforced dependencies into the list of dependencies.
      $enforced_dependencies = $values['dependencies']['enforced'];
      unset($values['dependencies']['enforced']);
      $this->dependencies = NestedArray::mergeDeep($values['dependencies'], $enforced_dependencies);
    }
    elseif (isset($values['dependencies'])) {
      $this->dependencies = $values['dependencies'];
    }
  }

  /**
   * Gets the configuration entity's dependencies of the supplied type.
   *
   * @param string $type
   *   The type of dependency to return. Either 'module', 'theme', 'config' or
   *   'content'.
   *
   * @return array
   *   The list of dependencies of the supplied type.
   */
  public function getDependencies($type) {
    $dependencies = [];
    if (isset($this->dependencies[$type])) {
      $dependencies = $this->dependencies[$type];
    }
    if ($type == 'module') {
      $dependencies[] = substr($this->name, 0, strpos($this->name, '.'));
    }
    return $dependencies;
  }

  /**
   * Determines if the entity is dependent on extensions or entities.
   *
   * @param string $type
   *   The type of dependency being checked. Either 'module', 'theme', 'config'
   *   or 'content'.
   * @param string $name
   *   The specific name to check. If $type equals 'module' or 'theme' then it
   *   should be a module name or theme name. In the case of entity it should be
   *   the full configuration object name.
   *
   * @return bool
   */
  public function hasDependency($type, $name) {
    // A config entity is always dependent on its provider.
    if ($type == 'module' && strpos($this->name, $name . '.') === 0) {
      return TRUE;
    }
    return isset($this->dependencies[$type]) && array_search($name, $this->dependencies[$type]) !== FALSE;
  }

  /**
   * Gets the configuration entity's configuration dependency name.
   *
   * @see \Drupal\Core\Entity\EntityInterface::getConfigDependencyName()
   *
   * @return string
   *   The configuration dependency name for the entity.
   */
  public function getConfigDependencyName() {
    return $this->name;
  }

}
