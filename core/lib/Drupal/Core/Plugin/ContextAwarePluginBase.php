<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\ContextAwarePluginBase.
 */

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\ContextAwarePluginBase as ComponentContextAwarePluginBase;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\Component\Plugin\Context\ContextInterface as ComponentContextInterface;
use Drupal\Core\Plugin\Context\ContextInterface;

/**
 * Base class for plugins that are context aware.
 */
abstract class ContextAwarePluginBase extends ComponentContextAwarePluginBase implements ContextAwarePluginInterface {
  use TypedDataTrait;
  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   *
   * This code is identical to the Component in order to pick up a different
   * Context class.
   */
  public function getContext($name) {
    // Check for a valid context value.
    if (!isset($this->context[$name])) {
      $this->context[$name] = new Context($this->getContextDefinition($name));
    }
    return $this->context[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function setContext($name, ComponentContextInterface $context) {
    // Check that the context passed is an instance of our extended interface.
    if (!$context instanceof ContextInterface) {
      throw new ContextException("Passed $name context must be an instance of \\Drupal\\Core\\Plugin\\Context\\ContextInterface");
    }
    parent::setContext($name, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function getContextMapping() {
    $configuration = $this instanceof ConfigurablePluginInterface ? $this->getConfiguration() : $this->configuration;
    return isset($configuration['context_mapping']) ? $configuration['context_mapping'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setContextMapping(array $context_mapping) {
    if ($this instanceof ConfigurablePluginInterface) {
      $configuration = $this->getConfiguration();
      $configuration['context_mapping'] = $context_mapping;
      $this->setConfiguration($configuration);
    }
    else {
      $this->configuration['context_mapping'] = $context_mapping;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Plugin\Context\ContextDefinitionInterface[]
   */
  public function getContextDefinitions() {
    return parent::getContextDefinitions();
  }

  /**
   * Wraps the context handler.
   *
   * @return \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected function contextHandler() {
    return \Drupal::service('context.handler');
  }

}
