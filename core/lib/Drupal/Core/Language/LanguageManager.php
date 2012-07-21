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

  public function __construct(Request $request = NULL) {
    $this->request = $request;
  }

  public function getLanguage($type) {
    $language = language_types_initialize($type, array('request' => $this->request));
    return $language;
  }

}