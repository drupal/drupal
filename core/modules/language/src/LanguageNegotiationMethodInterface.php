<?php

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
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
   * @return string|null|false
   *   A valid language code if the negotiation was successful and either NULL
   *    or FALSE otherwise.
   *
   * @todo Determine whether string|false or string|null should be the
   *   normalized result across all implementations and update the @return and
   *   its comment accordingly.
   *
   * @see https://www.drupal.org/node/3329952
   */
  public function getLangcode(?Request $request = NULL);

  /**
   * Notifies the plugin that the language code it returned has been accepted.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The accepted language.
   */
  public function persist(LanguageInterface $language);

}
