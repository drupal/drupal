<?php

/**
 * @file
 * Contains \Drupal\language\LanguageNegotiationMethodBase.
 */

namespace Drupal\language;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Base class for language negotiation methods.
 */
abstract class LanguageNegotiationMethodBase implements LanguageNegotiationMethodInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The current active user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function setLanguageManager(ConfigurableLanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfig(ConfigFactoryInterface $config) {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentUser(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function persist(LanguageInterface $language) {
    // Default implementation persists nothing.
  }

}
