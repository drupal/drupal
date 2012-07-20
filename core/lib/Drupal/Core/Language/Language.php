<?php

/**
 * @file
 * Definition of Drupal\Core\Language\Language.
 */

namespace Drupal\Core\Language;

/**
 * An object containing the information for an interface language.
 *
 * @todo To keep backwards compatibility with stdClass, we currently use
 * public scopes for the Language class's variables. We will change these to
 * full get/set functions in a follow-up issue: http://drupal.org/node/1512424
 *
 * @see language_default()
 */
class Language {
  // Properties within the Language are set up as the default language.
  public $name = 'English';
  public $langcode = 'en';
  public $direction = 0;
  public $weight = 0;
  public $default = FALSE;
  public $method_id = NULL;
  public $locked = FALSE;

  /**
   * Language constructor builds the default language object.
   *
   * @param array $options
   *   The properties used to construct the language.
   */
  public function __construct(array $options = array()) {
    // Set all the properties for the language.
    foreach ($options as $name => $value) {
      $this->$name = $value;
    }
  }

  /**
   * Extend $this with properties from the given object.
   *
   * @todo Remove this function once $GLOBALS['language'] is gone.
   */
  public function extend($obj) {
    $vars = get_object_vars($obj);
    foreach ($vars as $var => $value) {
      $this->$var = $value;
    }
  }
}
