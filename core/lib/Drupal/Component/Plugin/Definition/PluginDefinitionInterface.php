<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\PluginDefinitionInterface.
 */

namespace Drupal\Component\Plugin\Definition;

/**
 * Defines a plugin definition.
 *
 * Object-based plugin definitions MUST implement this interface.
 *
 * @ingroup Plugin
 */
interface PluginDefinitionInterface {

  /**
   * Sets the class.
   *
   * @param string $class
   *   A fully qualified class name.
   *
   * @return static
   *
   * @throws \InvalidArgumentException
   *   If the class is invalid.
   */
  public function setClass($class);

  /**
   * Gets the class.
   *
   * @return string
   *   A fully qualified class name.
   */
  public function getClass();

}
