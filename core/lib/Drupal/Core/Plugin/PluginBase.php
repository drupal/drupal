<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\PluginBase
 */

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\PluginBase as ComponentPluginBase;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for plugins supporting metadata inspection and translation.
 */
abstract class PluginBase extends ComponentPluginBase {

  /**
   * An array of service IDs keyed by property name used for serialization.
   *
   * @todo Remove when Drupal\Core\DependencyInjection\DependencySerialization
   * is converted to a trait.
   *
   * @var array
   */
  protected $_serviceIds = array();

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
      $this->translationManager = \Drupal::getContainer()->get('string_translation');
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

  /**
   * {@inheritdoc}
   *
   * @todo Remove when Drupal\Core\DependencyInjection\DependencySerialization
   * is converted to a trait.
   */
  public function __sleep() {
    $this->_serviceIds = array();
    $vars = get_object_vars($this);
    foreach ($vars as $key => $value) {
      if (is_object($value) && isset($value->_serviceId)) {
        // If a class member was instantiated by the dependency injection
        // container, only store its ID so it can be used to get a fresh object
        // on unserialization.
        $this->_serviceIds[$key] = $value->_serviceId;
        unset($vars[$key]);
      }
      // Special case the container, which might not have a service ID.
      elseif ($value instanceof ContainerInterface) {
        $this->_serviceIds[$key] = 'service_container';
        unset($vars[$key]);
      }
    }

    return array_keys($vars);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove when Drupal\Core\DependencyInjection\DependencySerialization
   * is converted to a trait.
   */
  public function __wakeup() {
    $container = \Drupal::getContainer();
    foreach ($this->_serviceIds as $key => $service_id) {
      $this->$key = $container->get($service_id);
    }
    unset($this->_serviceIds);
  }

}
