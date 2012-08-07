<?php

/**
 * @file
 * Definition of Drupal\Core\Language\LanguageManager.
 */

namespace Drupal\Core\Language;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class responsible for initializing each language type.
 *
 * This service is dependent on the 'request' service and can therefore pass the
 * Request object to the code that deals with each particular language type.
 * This means the Request can be used directly for things like url-based language
 * negotiation.
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
    $this->languages[$type] = language_types_initialize($type, $this->request);
    return $this->languages[$type];
  }

  function reset() {
    $this->languages = array();
  }
}
