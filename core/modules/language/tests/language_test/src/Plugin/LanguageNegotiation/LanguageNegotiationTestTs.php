<?php

declare(strict_types=1);

namespace Drupal\language_test\Plugin\LanguageNegotiation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\language\Attribute\LanguageNegotiation;

/**
 * Class for identifying language from a selected language.
 */
#[LanguageNegotiation(
  id: LanguageNegotiationTestTs::METHOD_ID,
  name: new TranslatableMarkup('Type-specific test'),
  types: ['test_language_type'],
  weight: -10,
  description: new TranslatableMarkup('This is a test language negotiation method.'),
)]
class LanguageNegotiationTestTs extends LanguageNegotiationTest {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'test_language_negotiation_method_ts';

}
