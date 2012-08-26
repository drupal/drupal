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
  public $name = '';
  public $langcode = '';
  public $direction = LANGUAGE_LTR;
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
    // Set all the provided properties for the language.
    foreach ($options as $name => $value) {
      $this->$name = $value;
    }
    // If some options were not set, set sane defaults of a predefined language.
    if (!isset($options['name']) || !isset($options['direction'])) {
      include_once DRUPAL_ROOT . '/core/includes/standard.inc';
      $predefined = standard_language_list();
      if (isset($predefined[$this->langcode])) {
        if (!isset($options['name'])) {
          $this->name = $predefined[$this->langcode][0];
        }
        if (!isset($options['direction']) && isset($predefined[$this->langcode][2])) {
          $this->direction = $predefined[$this->langcode][2];
        }
      }
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
