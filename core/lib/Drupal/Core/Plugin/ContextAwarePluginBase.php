<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\ContextAwarePluginBase
 */

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\ContextAwarePluginBase as ComponentContextAwarePluginBase;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Drupal specific class for plugins that use context.
 *
 * This class specifically overrides setContextValue to use the core version of
 * the Context class. This code is exactly the same as what is in Component
 * ContextAwarePluginBase but it is using a different Context class.
 */
abstract class ContextAwarePluginBase extends ComponentContextAwarePluginBase {

  /**
   * Override of \Drupal\Component\Plugin\ContextAwarePluginBase::__construct().
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
   * Override of \Drupal\Component\Plugin\ContextAwarePluginBase::setContextValue().
   */
  public function setContextValue($name, $value) {
    $context_definition = $this->getContextDefinition($name);
    // Use the Drupal specific context class.
    $this->context[$name] = new Context($context_definition);
    $this->context[$name]->setContextValue($value);

    // Verify the provided value validates.
    if ($this->context[$name]->validate()->count() > 0) {
      throw new PluginException("The provided context value does not pass validation.");
    }
    return $this;
  }

  /**
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translationManager()->translate($string, $args, $options);
  }

  /**
   * Gets the translation manager.
   *
   * @return \Drupal\Core\StringTranslation\TranslationInterface
   *   The translation manager.
   */
  protected function translationManager() {
    if (!$this->translationManager) {
      $this->translationManager = \Drupal::translation();
    }
    return $this->translationManager;
  }

  /**
   * Sets the translation manager for this plugin.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   *
   * @return self
   *   The plugin object.
   */
  public function setTranslationManager(TranslationInterface $translation_manager) {
    $this->translationManager = $translation_manager;
    return $this;
  }

}
