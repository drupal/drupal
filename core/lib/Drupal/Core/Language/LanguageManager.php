<?php

/**
 * @file
 * Definition of Drupal\Core\Language\LanguageManager.
 */

namespace Drupal\Core\Language;

use Symfony\Component\HttpFoundation\Request;

/**
 * Ajl
 */
class LanguageManager {

  private $container;

  public function __construct(Request $request) {
    $this->request = $request;
  }

  public function interfaceLanguage() {
    $language = language_types_initialize(LANGUAGE_TYPE_INTERFACE);
    return $language;
  }

  public function contentLanguage() {
    $language = language_types_initialize(LANGUAGE_TYPE_CONTENT);
    return $language;
  }

  public function urlLanguage() {
    $language = language_types_initialize(LANGUAGE_TYPE_URL);
    return $language;
  }
}