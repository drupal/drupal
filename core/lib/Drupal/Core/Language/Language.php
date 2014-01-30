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

  /**
   * The values to use to instantiate the default language.
   *
   * @var array
   */
  public static $defaultValues = array(
    'id' => 'en',
    'name' => 'English',
    'direction' => 0,
    'weight' => 0,
    'locked' => 0,
    'default' => TRUE,
  );

  // Properties within the Language are set up as the default language.

  /**
   * The human readable English name.
   *
   * @var string
   */
  public $name = '';

  /**
   * The ID, langcode.
   *
   * @var string
   */
  public $id = '';

  /**
   * The direction, left-to-right, or right-to-left.
   *
   * Defined using constants, either DIRECTION_LTR or const DIRECTION_RTL.
   *
   * @var int
   */
  public $direction = Language::DIRECTION_LTR;

  /**
   * The weight, used for ordering languages in lists, like selects or tables.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * Flag indicating if this is the only site default language.
   *
   * @var bool
   */
  public $default = FALSE;

  /**
   * The language negotiation method used when a language was detected.
   *
   * @var bool
   *
   * @see language_types_initialize()
   */
  public $method_id = NULL;

  /**
   * Locked indicates a language used by the system, not an actual language.
   *
   * Examples of locked languages are, LANGCODE_NOT_SPECIFIED, und, and
   * LANGCODE_NOT_APPLICABLE, zxx, which are usually shown in language selects
   * but hidden in places like the Language configuration and cannot be deleted.
   *
   * @var bool
   */
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
   * See the BCP 47 syntax for defining private language tags:
   * http://www.rfc-editor.org/rfc/bcp/bcp47.txt
   */
  const LANGCODE_DEFAULT = 'x-default';

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
      $this->{$name} = $value;
    }
    // If some options were not set, set sane defaults of a predefined language.
    if (!isset($options['name']) || !isset($options['direction'])) {
      $predefined = LanguageManager::getStandardLanguageList();
      if (isset($predefined[$this->id])) {
        if (!isset($options['name'])) {
          $this->name = $predefined[$this->id][0];
        }
        if (!isset($options['direction']) && isset($predefined[$this->id][2])) {
          $this->direction = $predefined[$this->id][2];
        }
      }
    }
  }

  /**
   * Sort language objects.
   *
   * @param array $languages
   *   The array of language objects keyed by langcode.
   */
  public static function sort(&$languages) {
    uasort($languages, 'Drupal\Component\Utility\SortArray::sortByWeightAndTitleKey');
  }

}
