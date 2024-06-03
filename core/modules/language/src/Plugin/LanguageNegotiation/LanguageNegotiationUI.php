<?php

namespace Drupal\language\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\language\Attribute\LanguageNegotiation;
use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Identifies the language from the interface text language selected for page.
 */
#[LanguageNegotiation(
  id: LanguageNegotiationUI::METHOD_ID,
  name: new TranslatableMarkup('Interface'),
  types: [LanguageInterface::TYPE_CONTENT],
  weight: 9,
  description: new TranslatableMarkup("Use the detected interface language.")
)]
class LanguageNegotiationUI extends LanguageNegotiationMethodBase {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-interface';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(?Request $request = NULL) {
    return $this->languageManager ? $this->languageManager->getCurrentLanguage()->getId() : NULL;
  }

}
