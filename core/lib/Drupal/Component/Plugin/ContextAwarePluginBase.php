<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\ContextAwarePluginBase
 */

namespace Drupal\Component\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Context\Context;

/**
 * Base class for plugins that are context aware.
 */
abstract class ContextAwarePluginBase extends PluginBase implements ContextAwarePluginInterface {

  /**
   * The data objects representing the context of this plugin.
   *
   * @var array
   */
  protected $context;

  /**
   * Implements \Drupal\Component\Plugin\ContextAwarePluginInterface::getContextDefinitions().
   */
  public function getContextDefinitions() {
    $definition = $this->getDefinition();
    return !empty($definition['context']) ? $definition['context'] : NULL;
  }

  /**
   * Implements \Drupal\Component\Plugin\ContextAwarePluginInterface::getContextDefinition().
   */
  public function getContextDefinition($key) {
    $definition = $this->getDefinition();
    if (empty($definition['context'][$key])) {
      throw new PluginException("The $key context is not a valid context.");
    }
    return $definition['context'][$key];
  }

  /**
   * Implements \Drupal\Component\Plugin\ContextAwarePluginInterface::getContexts().
   */
  public function getContexts() {
    $definitions = $this->getContextDefinitions();
    // If there are no contexts defined by the plugin, return an empty array.
    if (empty($definitions)) {
      return array();
    }
    if (empty($this->context)) {
      throw new PluginException("There are no set contexts.");
    }
    $contexts = array();
    foreach (array_keys($definitions) as $key) {
      if (empty($this->context[$key])) {
        throw new PluginException("The $key context is not yet set.");
      }
      $contexts[$key] = $this->context[$key];
    }
    return $contexts;
  }

  /**
   * Implements \Drupal\Component\Plugin\ContextAwarePluginInterface::getContext().
   */
  public function getContext($key) {
    // Check for a valid context definition.
    $this->getContextDefinition($key);
    // Check for a valid context value.
    if (empty($this->context[$key])) {
      throw new PluginException("The $key context is not yet set.");
    }

    return $this->context[$key];
  }

  /**
   * Implements \Drupal\Component\Plugin\ContextAwarePluginInterface::getContextValues().
   */
  public function getContextValues() {
    $contexts = array();
    foreach ($this->getContexts() as $key => $context) {
      $contexts[$key] = $context->getContextValue();
    }
    return $contexts;
  }

  /**
   * Implements \Drupal\Component\Plugin\ContextAwarePluginInterface::getContextValue().
   */
  public function getContextValue($key) {
    return $this->getContext($key)->getContextValue();
  }

  /**
   * Implements \Drupal\Component\Plugin\ContextAwarePluginInterface::setContextValue().
   */
  public function setContextValue($key, $value) {
    $context_definition = $this->getContextDefinition($key);
    $this->context[$key] = new Context($context_definition);
    $this->context[$key]->setContextValue($value);

    return $this;
  }

}
