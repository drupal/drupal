<?php

/**
 * @file
 * Contains \Drupal\language_test\\Plugin\LanguageNegotiation\LanguageNegotiationTest.
 */

namespace Drupal\language_test\Plugin\LanguageNegotiation;

use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from a selected language.
 *
 * @Plugin(
 *   id = "test_language_negotiation_method",
 *   weight = -10,
 *   name = @Translation("Test"),
 *   description = @Translation("This is a test language negotiation method."),
 *   types = {Drupal\Core\Language\LanguageInterface::TYPE_CONTENT,
 *   "test_language_type", "fixed_test_language_type"}
 * )
 */
class LanguageNegotiationTest extends LanguageNegotiationMethodBase {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'test_language_negotiation_method';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    return 'it';
  }

}
