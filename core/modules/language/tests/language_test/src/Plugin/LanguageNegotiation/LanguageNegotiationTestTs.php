<?php

/**
 * @file
 * Contains \Drupal\language_test\Plugin\LanguageNegotiation\LanguageNegotiationTestTs.
 */

namespace Drupal\language_test\Plugin\LanguageNegotiation;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from a selected language.
 *
 * @LanguageNegotiation(
 *   id = "test_language_negotiation_method_ts",
 *   weight = -10,
 *   name = @Translation("Type-specific test"),
 *   description = @Translation("This is a test language negotiation method."),
 *   types = {"test_language_type"}
 * )
 */
class LanguageNegotiationTestTs extends LanguageNegotiationTest {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'test_language_negotiation_method_ts';

}
