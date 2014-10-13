<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl.
 */

namespace Drupal\user\Plugin\LanguageNegotiation;

use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from the user preferences.
 *
 * @Plugin(
 *   id = \Drupal\user\Plugin\LanguageNegotiation\LanguageNegotiationUser::METHOD_ID,
 *   weight = -4,
 *   name = @Translation("User"),
 *   description = @Translation("Follow the user's language preference.")
 * )
 */
class LanguageNegotiationUser extends LanguageNegotiationMethodBase {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-user';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;

    // User preference (only for authenticated users).
    if ($this->languageManager && $this->currentUser->isAuthenticated()) {
      $preferred_langcode = $this->currentUser->getPreferredLangcode();
      $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
      $languages = $this->languageManager->getLanguages();
      if (!empty($preferred_langcode) && $preferred_langcode != $default_langcode && isset($languages[$preferred_langcode])) {
        $langcode = $preferred_langcode;
      }
    }

    // No language preference from the user.
    return $langcode;
  }

}
