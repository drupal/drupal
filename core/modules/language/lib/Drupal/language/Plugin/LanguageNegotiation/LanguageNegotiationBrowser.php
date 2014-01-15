<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationBrowser.
 */

namespace Drupal\language\Plugin\LanguageNegotiation;

use Drupal\Component\Utility\UserAgent;
use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from the browser Accept-language HTTP header.
 *
 * @Plugin(
 *   id = \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationBrowser::METHOD_ID,
 *   weight = -2,
 *   cache = 0,
 *   name = @Translation("Browser"),
 *   description = @Translation("Language from the browser's language settings."),
 *   config_path = "admin/config/regional/language/detection/browser"
 * )
 */
class LanguageNegotiationBrowser extends LanguageNegotiationMethodBase {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-browser';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;

    if ($this->languageManager && $request && $request->server->get('HTTP_ACCEPT_LANGUAGE')) {
      $http_accept_language = $request->server->get('HTTP_ACCEPT_LANGUAGE');
      $langcodes = array_keys($this->languageManager->getLanguages());
      $mappings = $this->config->get('language.mappings')->get();
      $langcode = UserAgent::getBestMatchingLangcode($http_accept_language, $langcodes, $mappings);
    }

    return $langcode;
  }

}
