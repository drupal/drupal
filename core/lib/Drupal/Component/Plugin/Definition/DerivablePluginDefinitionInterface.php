<?php

namespace Drupal\Component\Plugin\Definition;

/**
 * Provides an interface for a derivable plugin definition.
 *
 * @see \Drupal\Component\Plugin\Derivative\DeriverInterface
 */
interface DerivablePluginDefinitionInterface extends PluginDefinitionInterface {

  /**
   * Gets the name of the deriver of this plugin definition, if it exists.
   *
   * @return string|null
   *   Either the deriver class name, or NULL if the plugin is not derived.
   */
  public function getDeriver();

  /**
   * Sets the deriver of this plugin definition.
   *
   * @param string|null $deriver
   *   Either the name of a class that implements
   *   \Drupal\Component\Plugin\Derivative\DeriverInterface, or NULL.
   *
   * @return $this
   */
  public function setDeriver($deriver);

}
