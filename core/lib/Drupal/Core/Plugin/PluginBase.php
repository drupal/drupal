<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\PluginBase
 */

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\PluginBase as ComponentPluginBase;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Base class for plugins supporting metadata inspection and translation.
 */
abstract class PluginBase extends ComponentPluginBase {

  /**
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Gets the current active user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   */
  protected function currentUser() {
    if (!$this->currentUser) {
      $this->currentUser = \Drupal::currentUser();
    }
    return $this->currentUser;
  }

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

}
