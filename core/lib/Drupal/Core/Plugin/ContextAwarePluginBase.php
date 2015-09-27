<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\ContextAwarePluginBase.
 */

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\ContextAwarePluginBase as ComponentContextAwarePluginBase;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
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
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   */
  protected function createContextFromConfiguration(array $context_configuration) {
    // This method is overridden so that it will use
    // \Drupal\Core\Plugin\Context\Context instead.
    $contexts = [];
    foreach ($context_configuration as $key => $value) {
      $context_definition = $this->getContextDefinition($key);
      $contexts[$key] = new Context($context_definition, $value);
    }
    return $contexts;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface
   *   The context object.
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
  public function setContextValue($name, $value) {
    $this->context[$name] = Context::createFromContext($this->getContext($name), $value);
    return $this;
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
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Plugin\Context\ContextDefinitionInterface
   */
  public function getContextDefinition($name) {
    return parent::getContextDefinition($name);
  }

  /**
   * Wraps the context handler.
   *
   * @return \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected function contextHandler() {
    return \Drupal::service('context.handler');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = [];
    // Applied contexts can affect the cache contexts when this plugin is
    // involved in caching, collect and return them.
    foreach ($this->getContexts() as $context) {
      /** @var $context \Drupal\Core\Cache\CacheableDependencyInterface */
      if ($context instanceof CacheableDependencyInterface) {
        $cache_contexts = Cache::mergeContexts($cache_contexts, $context->getCacheContexts());
      }
    }
    return $cache_contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = [];
    // Applied contexts can affect the cache tags when this plugin is
    // involved in caching, collect and return them.
    foreach ($this->getContexts() as $context) {
      /** @var $context \Drupal\Core\Cache\CacheableDependencyInterface */
      if ($context instanceof CacheableDependencyInterface) {
        $tags = Cache::mergeTags($tags, $context->getCacheTags());
      }
    }
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $max_age = Cache::PERMANENT;

    // Applied contexts can affect the cache max age when this plugin is
    // involved in caching, collect and return them.
    foreach ($this->getContexts() as $context) {
      /** @var $context \Drupal\Core\Cache\CacheableDependencyInterface */
      if ($context instanceof CacheableDependencyInterface) {
        $max_age = Cache::mergeMaxAges($max_age, $context->getCacheMaxAge());
      }
    }
    return $max_age;
  }

}
