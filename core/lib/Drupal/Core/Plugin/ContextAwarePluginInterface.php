<?php

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\ContextAwarePluginInterface as ComponentContextAwarePluginInterface;

/**
 * An override of ContextAwarePluginInterface for documentation purposes.
 *
 * @see \Drupal\Component\Plugin\ContextAwarePluginInterface
 *
 * @ingroup plugin_api
 */
interface ContextAwarePluginInterface extends ComponentContextAwarePluginInterface {

  /**
   * Gets the context definitions of the plugin.
   *
   * @return \Drupal\Core\Plugin\Context\ContextDefinitionInterface[]
   *   The array of context definitions, keyed by context name.
   */
  public function getContextDefinitions();

  /**
   * Gets a specific context definition of the plugin.
   *
   * @param string $name
   *   The name of the context in the plugin definition.
   *
   * @return \Drupal\Core\Plugin\Context\ContextDefinitionInterface
   *   The definition against which the context value must validate.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the requested context is not defined.
   */
  public function getContextDefinition($name);

}
