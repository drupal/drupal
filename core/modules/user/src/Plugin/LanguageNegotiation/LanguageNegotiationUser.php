<?php

namespace Drupal\user\Plugin\LanguageNegotiation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\language\Attribute\LanguageNegotiation;
use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from the user preferences.
 */
#[LanguageNegotiation(
  id: LanguageNegotiationUser::METHOD_ID,
  name: new TranslatableMarkup('User'),
  weight: -4,
  description: new TranslatableMarkup("Follow the user's language preference.")
)]
class LanguageNegotiationUser extends LanguageNegotiationMethodBase {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-user';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(?Request $request = NULL) {
    $langcode = NULL;

    // User preference (only for authenticated users).
    if ($this->languageManager && $this->currentUser->isAuthenticated()) {
      $preferred_langcode = $this->currentUser->getPreferredLangcode(FALSE);
      $languages = $this->languageManager->getLanguages();
      if (!empty($preferred_langcode) && isset($languages[$preferred_langcode])) {
        $langcode = $preferred_langcode;
      }
    }

    // No language preference from the user.
    return $langcode;
  }

}
