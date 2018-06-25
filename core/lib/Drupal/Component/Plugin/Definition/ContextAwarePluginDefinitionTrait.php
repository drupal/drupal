<?php

namespace Drupal\Component\Plugin\Definition;

use Drupal\Component\Plugin\Context\ContextDefinitionInterface;
use Drupal\Component\Plugin\Exception\ContextException;

/**
 * Provides a trait for context-aware object-based plugin definitions.
 */
trait ContextAwarePluginDefinitionTrait {

  /**
   * The context definitions for this plugin definition.
   *
   * @var \Drupal\Component\Plugin\Context\ContextDefinitionInterface[]
   */
  protected $contextDefinitions = [];

  /**
   * Implements \Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionInterface::hasContextDefinition().
   */
  public function hasContextDefinition($name) {
    return array_key_exists($name, $this->contextDefinitions);
  }

  /**
   * Implements \Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionInterface::getContextDefinitions().
   */
  public function getContextDefinitions() {
    return $this->contextDefinitions;
  }

  /**
   * Implements \Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionInterface::getContextDefinition().
   */
  public function getContextDefinition($name) {
    if ($this->hasContextDefinition($name)) {
      return $this->contextDefinitions[$name];
    }
    throw new ContextException($this->id() . " does not define a '$name' context");
  }

  /**
   * Implements \Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionInterface::addContextDefinition().
   */
  public function addContextDefinition($name, ContextDefinitionInterface $definition) {
    $this->contextDefinitions[$name] = $definition;
    return $this;
  }

  /**
   * Implements \Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionInterface::removeContextDefinition().
   */
  public function removeContextDefinition($name) {
    unset($this->contextDefinitions[$name]);
    return $this;
  }

}
