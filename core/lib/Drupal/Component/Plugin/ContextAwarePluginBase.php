<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\ContextAwarePluginBase
 */

namespace Drupal\Component\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Context\Context;
use Symfony\Component\Validator\ConstraintViolationList;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

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
   * Overrides \Drupal\Component\Plugin\PluginBase::__construct().
   *
   * Overrides the construction of context aware plugins to allow for
   * unvalidated constructor based injection of contexts.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    $context = array();
    if (isset($configuration['context'])) {
      $context = $configuration['context'];
      unset($configuration['context']);
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    foreach ($context as $key => $value) {
      $context_definition = $this->getContextDefinition($key);
      $this->context[$key] = new Context($context_definition);
      $this->context[$key]->setContextValue($value);
    }
  }

  /**
   * Implements \Drupal\Component\Plugin\ContextAwarePluginInterface::getContextDefinitions().
   */
  public function getContextDefinitions() {
    $definition = $this->getPluginDefinition();
    return !empty($definition['context']) ? $definition['context'] : array();
  }

  /**
   * Implements \Drupal\Component\Plugin\ContextAwarePluginInterface::getContextDefinition().
   */
  public function getContextDefinition($name) {
    $definition = $this->getPluginDefinition();
    if (empty($definition['context'][$name])) {
      throw new PluginException("The $name context is not a valid context.");
    }
    return $definition['context'][$name];
  }

  /**
   * Implements \Drupal\Component\Plugin\ContextAwarePluginInterface::getContexts().
   */
  public function getContexts() {
    $definitions = $this->getContextDefinitions();
    if ($definitions && empty($this->context)) {
      throw new PluginException("There are no set contexts.");
    }
    $contexts = array();
    foreach (array_keys($definitions) as $name) {
      if (empty($this->context[$name])) {
        throw new PluginException("The $name context is not yet set.");
      }
      $contexts[$name] = $this->context[$name];
    }
    return $contexts;
  }

  /**
   * Implements \Drupal\Component\Plugin\ContextAwarePluginInterface::getContext().
   */
  public function getContext($name) {
    // Check for a valid context definition.
    $this->getContextDefinition($name);
    // Check for a valid context value.
    if (!isset($this->context[$name])) {
      throw new PluginException("The $name context is not yet set.");
    }

    return $this->context[$name];
  }

  /**
   * Implements \Drupal\Component\Plugin\ContextAwarePluginInterface::getContextValues().
   */
  public function getContextValues() {
    $values = array();
    foreach ($this->getContextDefinitions() as $name => $definition) {
      $values[$name] = isset($this->context[$name]) ? $this->context[$name]->getContextValue() : NULL;
    }
    return $values;
  }

  /**
   * Implements \Drupal\Component\Plugin\ContextAwarePluginInterface::getContextValue().
   */
  public function getContextValue($name) {
    return $this->getContext($name)->getContextValue();
  }

  /**
   * Implements \Drupal\Component\Plugin\ContextAwarePluginInterface::setContextValue().
   */
  public function setContextValue($name, $value) {
    $context_definition = $this->getContextDefinition($name);
    $this->context[$name] = new Context($context_definition);
    $this->context[$name]->setContextValue($value);

    // Verify the provided value validates.
    $violations = $this->context[$name]->validate();
    if (count($violations) > 0) {
      throw new PluginException("The provided context value does not pass validation.");
    }
    return $this;
  }

  /**
   * Implements \Drupal\Component\Plugin\ContextAwarePluginInterface::valdidateContexts().
   */
  public function validateContexts() {
    $violations = new ConstraintViolationList();
    // @todo: Implement symfony validator API to let the validator traverse
    // and set property paths accordingly.

    foreach ($this->getContextDefinitions() as $name => $definition) {
      // Validate any set values.
      if (isset($this->context[$name])) {
        $violations->addAll($this->context[$name]->validate());
      }
      // @todo: If no value is set, make sure any mapping is validated.
    }
    return $violations;
  }

}
