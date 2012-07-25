<?php

/**
 * @file
 * Definition of Drupal\Core\Language\LanguageManager.
 */

namespace Drupal\Core\Language;

use Symfony\Component\HttpFoundation\Request;

/**
 * The LanguageManager service intializes the language types passing in the
 * Request object, which can then be used for e.g. url-based language negotiation.
 */
class LanguageManager {

  private $request;
  private $languages;

  public function __construct(Request $request = NULL) {
    $this->request = $request;
  }

  public function getLanguage($type) {
    if (isset($this->languages[$type])) {
      return $this->languages[$type];
    }

    // @todo Objectify the language system so that we don't have to do this.
    include_once DRUPAL_ROOT . '/core/includes/language.inc';
    $this->languages[$type] = language_types_initialize($type, array('request' => $this->request));
    return $this->languages[$type];
  }

  function reset() {
    $this->languages = array();
  }
}