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
  public $direction = Language::DIRECTION_LTR;
  public $weight = 0;
  public $default = FALSE;
  public $method_id = NULL;
  public $locked = FALSE;

  /**
   * Special system language code (only applicable to UI language).
   *
   * Refers to the language used in Drupal and module/theme source code. Drupal
   * uses the built-in text for English by default, but if configured to allow
   * translation/customization of English, we need to differentiate between the
   * built-in language and the English translation.
   */
  const LANGCODE_SYSTEM = 'system';

  /**
   * The language code used when no language is explicitly assigned (yet).
   *
   * Should be used when language information is not available or cannot be
   * determined. This special language code is useful when we know the data
   * might have linguistic information, but we don't know the language.
   *
   * See http://www.w3.org/International/questions/qa-no-language#undetermined.
   */
  const LANGCODE_NOT_SPECIFIED = 'und';

  /**
   * The language code used when the marked object has no linguistic content.
   *
   * Should be used when we explicitly know that the data referred has no
   * linguistic content.
   *
   * See http://www.w3.org/International/questions/qa-no-language#nonlinguistic.
   */
  const LANGCODE_NOT_APPLICABLE = 'zxx';

  /**
   * Language code referring to the default language of data, e.g. of an entity.
   *
   * @todo: Change value to differ from Language::LANGCODE_NOT_SPECIFIED once
   * field API leverages the property API.
   */
  const LANGCODE_DEFAULT = 'und';

  /**
   * The language state when referring to configurable languages.
   */
  const STATE_CONFIGURABLE = 1;

  /**
   * The language state when referring to locked languages.
   */
  const STATE_LOCKED = 2;

  /**
   * The language state used when referring to all languages.
   */
  const STATE_ALL = 3;

  /**
   * The language state used when referring to the site's default language.
   */
  const STATE_SITE_DEFAULT = 4;

  /**
   * The type of language used to define the content language.
   */
  const TYPE_CONTENT = 'language_content';

  /**
   * The type of language used to select the user interface.
   */
  const TYPE_INTERFACE = 'language_interface';

  /**
   * The type of language used for URLs.
   */
  const TYPE_URL = 'language_url';

  /**
   * Language written left to right. Possible value of $language->direction.
   */
  const DIRECTION_LTR = 0;

  /**
   * Language written right to left. Possible value of $language->direction.
   */
  const DIRECTION_RTL = 1;

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
