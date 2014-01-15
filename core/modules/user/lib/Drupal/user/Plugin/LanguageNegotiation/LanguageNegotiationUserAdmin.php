<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\LanguageNegotiation\LanguageNegotiationUserAdmin.
 */

namespace Drupal\user\Plugin\LanguageNegotiation;

use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Identifies admin language from the user preferences.
 *
 * @Plugin(
 *   id = Drupal\user\Plugin\LanguageNegotiation\LanguageNegotiationUserAdmin::METHOD_ID,
 *   types = {Drupal\Core\Language\Language::TYPE_INTERFACE},
 *   weight = 10,
 *   name = @Translation("Account administration pages"),
 *   description = @Translation("Account administration pages language setting.")
 * )
 */
class LanguageNegotiationUserAdmin extends LanguageNegotiationMethodBase {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-user-admin';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;

    // User preference (only for authenticated users).
    if ($this->languageManager && $this->currentUser->isAuthenticated() && $this->isAdminPath($request)) {
      $preferred_admin_langcode = $this->currentUser->getPreferredAdminLangcode();
      $default_langcode = $this->languageManager->getDefaultLanguage()->id;
      $languages = $this->languageManager->getLanguages();
      if (!empty($preferred_admin_langcode) && $preferred_admin_langcode != $default_langcode && isset($languages[$preferred_admin_langcode])) {
        $langcode = $preferred_admin_langcode;
      }
    }

    // No language preference from the user or not on an admin path.
    return $langcode;
  }

  /**
   * Checks whether the given path is an administrative one.
   *
   * @param string $path
   *   A Drupal path.
   *
   * @return bool
   *   TRUE if the path is administrative, FALSE otherwise.
   */
  public function isAdminPath(Request $request) {
    $result = FALSE;
    if ($request && function_exists('path_is_admin')) {
      $path = urldecode(trim($request->getPathInfo(), '/'));
      $result = path_is_admin($path);
    }
    return $result;
  }

}
