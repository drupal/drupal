<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\DependencyTrait.
 */

namespace Drupal\Core\Entity;

/**
 * Provides a trait for managing an object's dependencies.
 */
trait DependencyTrait {

  /**
   * The object's dependencies.
   *
   * @var array
   */
  protected $dependencies = array();

  /**
   * Creates a dependency.
   *
   * @param string $type
   *   The type of dependency being checked. Either 'module', 'theme', 'entity'.
   * @param string $name
   *   If $type equals 'module' or 'theme' then it should be the name of the
   *   module or theme. In the case of entity it should be the full
   *   configuration object name.
   *
   * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::getConfigDependencyName()
   *
   * @return $this
   */
  protected function addDependency($type, $name) {
    if (empty($this->dependencies[$type])) {
      $this->dependencies[$type] = array($name);
      if (count($this->dependencies) > 1) {
        // Ensure a consistent order of type keys.
        ksort($this->dependencies);
      }
    }
    elseif (!in_array($name, $this->dependencies[$type])) {
      $this->dependencies[$type][] = $name;
      // Ensure a consistent order of dependency names.
      sort($this->dependencies[$type], SORT_FLAG_CASE);
    }
    return $this;
  }

  /**
   * Adds multiple dependencies.
   *
   * @param array $dependencies.
   *   An array of dependencies keyed by the type of dependency. One example:
   * @code
   * array(
   *   'module' => array(
   *     'node',
   *     'field',
   *     'image'
   *   ),
   * );
   * @endcode
   *
   * @see ::addDependency
   */
  protected function addDependencies(array $dependencies) {
    foreach ($dependencies as $dependency_type => $list) {
      foreach ($list as $name) {
        $this->addDependency($dependency_type, $name);
      }
    }
  }

}
