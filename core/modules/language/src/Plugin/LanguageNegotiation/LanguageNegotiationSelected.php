<?php

namespace Drupal\language\Plugin\LanguageNegotiation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\language\Attribute\LanguageNegotiation;
use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from a selected language.
 */
#[LanguageNegotiation(
  id: LanguageNegotiationSelected::METHOD_ID,
  name: new TranslatableMarkup('Selected language'),
  weight: 12,
  description: new TranslatableMarkup("Language based on a selected language."),
  config_route_name: 'language.negotiation_selected'
)]
class LanguageNegotiationSelected extends LanguageNegotiationMethodBase {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-selected';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(?Request $request = NULL) {
    $langcode = NULL;

    if ($this->languageManager) {
      $langcode = $this->config->get('language.negotiation')->get('selected_langcode');
    }

    return $langcode;
  }

}
