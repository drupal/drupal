<?php

namespace Drupal\Component\Plugin\Definition;

use Drupal\Component\Plugin\Context\ContextDefinitionInterface;

/**
 * Provides an interface for plugin definitions which use contexts.
 *
 * @ingroup Plugin
 */
interface ContextAwarePluginDefinitionInterface extends PluginDefinitionInterface {

  /**
   * Checks if the plugin defines a particular context.
   *
   * @param string $name
   *   The context name.
   *
   * @return bool
   *   TRUE if the plugin defines the given context, otherwise FALSE.
   */
  public function hasContextDefinition($name);

  /**
   * Returns all context definitions for this plugin.
   *
   * @return \Drupal\Component\Plugin\Context\ContextDefinitionInterface[]
   *   The context definitions.
   */
  public function getContextDefinitions();

  /**
   * Returns a particular context definition for this plugin.
   *
   * @param string $name
   *   The context name.
   *
   * @return \Drupal\Component\Plugin\Context\ContextDefinitionInterface
   *   The context definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   *   Thrown if the plugin does not define the given context.
   */
  public function getContextDefinition($name);

  /**
   * Adds a context to this plugin definition.
   *
   * @param string $name
   *   The context name.
   * @param \Drupal\Component\Plugin\Context\ContextDefinitionInterface $definition
   *   The context definition.
   *
   * @return $this
   *   The called object.
   */
  public function addContextDefinition($name, ContextDefinitionInterface $definition);

  /**
   * Removes a context definition from this plugin.
   *
   * @param string $name
   *   The context name.
   *
   * @return $this
   *   The called object.
   */
  public function removeContextDefinition($name);

}
