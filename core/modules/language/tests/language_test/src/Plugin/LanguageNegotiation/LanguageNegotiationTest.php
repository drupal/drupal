<?php

namespace Drupal\language_test\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\language\Attribute\LanguageNegotiation;
use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from a selected language.
 */
#[LanguageNegotiation(
  id: LanguageNegotiationTest::METHOD_ID,
  name: new TranslatableMarkup('Test'),
  types: [LanguageInterface::TYPE_CONTENT,
    'test_language_type',
    'fixed_test_language_type',
  ],
  weight: -10,
  description: new TranslatableMarkup('This is a test language negotiation method.'),
)]
class LanguageNegotiationTest extends LanguageNegotiationMethodBase {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'test_language_negotiation_method';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(?Request $request = NULL) {
    return 'it';
  }

}
