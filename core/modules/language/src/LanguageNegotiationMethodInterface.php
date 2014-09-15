<?php

/**
 * @file
 * Contains \Drupal\language\LanguageNegotiationMethodInterface.
 */

namespace Drupal\language;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface for language negotiation classes.
 */
interface LanguageNegotiationMethodInterface {

  /**
   * Injects the language manager.
   *
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *   The language manager to be used to retrieve the language list and the
   *   already negotiated languages.
   */
  public function setLanguageManager(ConfigurableLanguageManagerInterface $language_manager);

  /**
   * Injects the configuration factory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function setConfig(ConfigFactoryInterface $config);

  /**
   * Injects the current user.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current active user.
   */
  public function setCurrentUser(AccountInterface $current_user);

  /**
   * Performs language negotiation.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   (optional) The current request. Defaults to NULL if it has not been
   *   initialized yet.
   *
   * @return string
   *   A valid language code or FALSE if the negotitation was unsuccessful.
   */
  public function getLangcode(Request $request = NULL);

  /**
   * Notifies the plugin that the language code it returned has been accepted.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The accepted language.
   */
  public function persist(LanguageInterface $language);

}
