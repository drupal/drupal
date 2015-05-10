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
   * Adds a dependency.
   *
   * @param string $type
   *   Type of dependency being added: 'module', 'theme', 'config', 'content'.
   * @param string $name
   *   If $type is 'module' or 'theme', the name of the module or theme. If
   *   $type is 'config' or 'content', the result of
   *   EntityInterface::getConfigDependencyName().
   *
   * @see \Drupal\Core\Entity\EntityInterface::getConfigDependencyName()
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
   *   @code
   *   array(
   *     'module' => array(
   *       'node',
   *       'field',
   *       'image',
   *     ),
   *   );
   *   @endcode
   *
   * @see \Drupal\Core\Entity\DependencyTrait::addDependency
   */
  protected function addDependencies(array $dependencies) {
    foreach ($dependencies as $dependency_type => $list) {
      foreach ($list as $name) {
        $this->addDependency($dependency_type, $name);
      }
    }
  }

}
