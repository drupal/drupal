<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUI.
 */

namespace Drupal\language\Plugin\LanguageNegotiation;

use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying the language from the current interface language.
 *
 * @Plugin(
 *   id = Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUI::METHOD_ID,
 *   types = {Drupal\Core\Language\Language::TYPE_CONTENT},
 *   weight = 9,
 *   name = @Translation("Interface"),
 *   description = @Translation("Use the detected interface language.")
 * )
 */
class LanguageNegotiationUI extends LanguageNegotiationMethodBase {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-interface';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    return $this->languageManager ? $this->languageManager->getCurrentLanguage()->id : NULL;
  }

}
