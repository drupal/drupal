<?php

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
   * Gets the unique identifier of the plugin.
   *
   * @return string
   *   The unique identifier of the plugin.
   */
  public function id();

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

  /**
   * Gets the plugin provider.
   *
   * The provider is the name of the module that provides the plugin, or "core',
   * or "component".
   *
   * @return string
   *   The provider.
   */
  public function getProvider();

}
