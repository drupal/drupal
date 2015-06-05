<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUI.
 */

namespace Drupal\language\Plugin\LanguageNegotiation;

use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Identifies the language from the interface text language selected for page.
 *
 * @LanguageNegotiation(
 *   id = Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUI::METHOD_ID,
 *   types = {Drupal\Core\Language\LanguageInterface::TYPE_CONTENT},
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
    return $this->languageManager ? $this->languageManager->getCurrentLanguage()->getId() : NULL;
  }

}
