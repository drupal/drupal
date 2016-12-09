<?php

namespace Drupal\Core\Layout;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;

/**
 * Provides an interface for a derivable plugin definition.
 *
 * @see \Drupal\Component\Plugin\Derivative\DeriverInterface
 * @see \Drupal\Core\Layout\ObjectDefinitionContainerDerivativeDiscoveryDecorator
 *
 * @internal
 *   The layout system is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 *
 * @todo Move into \Drupal\Component\Plugin\Definition in
 *   https://www.drupal.org/node/2821189.
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
