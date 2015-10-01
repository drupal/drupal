<?php

/**
 * @file
 * Contains \Drupal\Core\StringTranslation\PluralTranslatableString.
 */

namespace Drupal\Core\StringTranslation;

/**
 * A class to hold plural translatable strings.
 */
class PluralTranslatableString extends TranslatableString {

  /**
   * The delimiter used to split plural strings.
   *
   * This is the ETX (End of text) character and is used as a minimal means to
   * separate singular and plural variants in source and translation text. It
   * was found to be the most compatible delimiter for the supported databases.
   */
  const DELIMITER = "\03";

  /**
   * The item count to display.
   *
   * @var int
   */
  protected $count;

  /**
   * The already translated string.
   *
   * @var string
   */
  protected $translatedString;

  /**
   * A bool that statically caches whether locale_get_plural() exists.
   *
   * @var bool
   */
  protected static $localeEnabled;

  /**
   * Constructs a new PluralTranslatableString object.
   *
   * Parses values passed into this class through the format_plural() function
   * in Drupal and handles an optional context for the string.
   *
   * @param int $count
   *   The item count to display.
   * @param string $singular
   *   The string for the singular case. Make sure it is clear this is singular,
   *   to ease translation (e.g. use "1 new comment" instead of "1 new"). Do not
   *   use @count in the singular string.
   * @param string $plural
   *   The string for the plural case. Make sure it is clear this is plural, to
   *   ease translation. Use @count in place of the item count, as in
   *   "@count new comments".
   * @param array $args
   *   (optional) An array with placeholder replacements, keyed by placeholder.
   *   See \Drupal\Component\Utility\FormattableString::placeholderFormat() for
   *   additional information about placeholders. Note that you do not need to
   *   include @count in this array; this replacement is done automatically
   *   for the plural cases.
   * @param array $options
   *   (optional) An associative array of additional options. See t() for
   *   allowed keys.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   (optional) The string translation service.
   *
   * @see \Drupal\Component\Utility\FormattableString::placeholderFormat()
   */
  public function __construct($count, $singular, $plural, array $args = [], array $options = [], TranslationInterface $string_translation = NULL) {
    $this->count = $count;
    $translatable_string = implode(static::DELIMITER, array($singular, $plural));
    parent::__construct($translatable_string, $args, $options, $string_translation);
  }

  /**
   * Constructs a new class instance from an already translated string.
   *
   * This method ensures that the string is pluralized correctly. As opposed
   * to the __construct() method, this method is designed to be invoked with
   * a string already translated (such as with configuration translation).
   *
   * @param int $count
   *   The item count to display.
   * @param string $translated_string
   *   The already translated string.
   * @param array $args
   *   An associative array of replacements to make after translation. Instances
   *   of any key in this array are replaced with the corresponding value.
   *   Based on the first character of the key, the value is escaped and/or
   *   themed. See \Drupal\Component\Utility\SafeMarkup::format(). Note that you
   *   do not need to include @count in this array; this replacement is done
   *   automatically for the plural cases.
   * @param array $options
   *   An associative array of additional options. See t() for allowed keys.
   *
   * @return \Drupal\Core\StringTranslation\PluralTranslatableString
   *   A PluralTranslatableString object.
   */
  public static function createFromTranslatedString($count, $translated_string, array $args = [], array $options = []) {
    $plural = new static($count, '', '', $args, $options);
    $plural->translatedString = $translated_string;
    return $plural;
  }

  /**
   * Renders the object as a string.
   *
   * @return string
   *   The translated string.
   */
  public function render() {
    if (!$this->translatedString) {
      $this->translatedString = $this->getStringTranslation()->translateString($this);
    }
    if ($this->translatedString === '') {
      return '';
    }

    $arguments = $this->getArguments();
    $arguments['@count'] = $this->count;
    $translated_array = explode(static::DELIMITER, $this->translatedString);

    if ($this->count == 1) {
      return $this->placeholderFormat($translated_array[0], $arguments);
    }

    $index = $this->getPluralIndex();
    if ($index == 0) {
      // Singular form.
      $return = $translated_array[0];
    }
    else {
      if (isset($translated_array[$index])) {
        // N-th plural form.
        $return = $translated_array[$index];
      }
      else {
        // If the index cannot be computed or there's no translation, use the
        // second plural form as a fallback (which allows for most flexibility
        // with the replaceable @count value).
        $return = $translated_array[1];
      }
    }

    return $this->placeholderFormat($return, $arguments);
  }

  /**
   * Gets the plural index through the gettext formula.
   *
   * @return int
   */
  protected function getPluralIndex() {
    if (!isset(static::$localeEnabled)) {
      static::$localeEnabled = function_exists('locale_get_plural');
    }
    if (function_exists('locale_get_plural')) {
      return locale_get_plural($this->count, $this->getOption('langcode'));
    }
    return -1;
  }

}
