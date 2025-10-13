<?php

namespace Drupal\Core\Plugin\Context;

/**
 * Provides a trait for plugin managers that support context-aware plugins.
 */
trait ContextAwarePluginManagerTrait {

  /**
   * Wraps the context handler.
   *
   * @return \Drupal\Core\Plugin\Context\ContextHandlerInterface
   *   The context handler service.
   */
  protected function contextHandler() {
    return \Drupal::service('context.handler');
  }

  /**
   * Determines plugins whose constraints are satisfied by a set of contexts.
   *
   * @see \Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface::getDefinitionsForContexts()
   */
  public function getDefinitionsForContexts(array $contexts = []) {
    return $this->contextHandler()->filterPluginDefinitionsByContexts($contexts, $this->getDefinitions());
  }

  /**
   * Gets a specific plugin definition.
   *
   * @see \Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinitions()
   */
  abstract public function getDefinitions();

}
