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
   * See \Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface::getDefinitionsForContexts().
   */
  public function getDefinitionsForContexts(array $contexts = []) {
    return $this->contextHandler()->filterPluginDefinitionsByContexts($contexts, $this->getDefinitions());
  }

  /**
   * See \Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinitions().
   */
  abstract public function getDefinitions();

}
