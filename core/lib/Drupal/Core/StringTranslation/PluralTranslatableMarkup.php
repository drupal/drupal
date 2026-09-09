<?php

namespace Drupal\Core\StringTranslation;

use Drupal\Component\Gettext\PoItem;

/**
 * A class to hold plural translatable markup.
 */
class PluralTranslatableMarkup extends TranslatableMarkup {

  /**
   * The item count to display.
   *
   * @var numeric
   */
  protected $count;

  /**
   * The already translated string.
   *
   * @var string
   */
  protected $translatedString;

  /**
   * Constructs a new PluralTranslatableMarkup object.
   *
   * Parses values passed into this class through the format_plural() function
   * in Drupal and handles an optional context for the string.
   *
   * @param numeric $count
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
   *   See \Drupal\Component\Render\FormattableMarkup::placeholderFormat() for
   *   additional information about placeholders. Note that you do not need to
   *   include @count in this array; this replacement is done automatically
   *   for the plural cases.
   * @param array $options
   *   (optional) An associative array of additional options. See t() for
   *   allowed keys.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   (optional) The string translation service.
   *
   * @see \Drupal\Component\Render\FormattableMarkup::placeholderFormat()
   */
  public function __construct($count, $singular, $plural, array $args = [], array $options = [], ?TranslationInterface $string_translation = NULL) {
    $this->count = $count;
    $translatable_string = implode(PoItem::DELIMITER, [$singular, $plural]);
    parent::__construct($translatable_string, $args, $options, $string_translation);
  }

  /**
   * Constructs a new class instance from already translated markup.
   *
   * This method ensures that the string is pluralized correctly. As opposed
   * to the __construct() method, this method is designed to be invoked with
   * a string already translated (such as with configuration translation).
   *
   * @param numeric $count
   *   The item count to display.
   * @param string $translated_string
   *   The already translated string.
   * @param array $args
   *   An associative array of replacements to make after translation. Instances
   *   of any key in this array are replaced with the corresponding value.
   *   Based on the first character of the key, the value is escaped and/or
   *   themed. See \Drupal\Component\Render\FormattableMarkup. Note that you
   *   do not need to include @count in this array; this replacement is done
   *   automatically for the plural cases.
   * @param array $options
   *   An associative array of additional options. See t() for allowed keys.
   *
   * @return static
   *   A PluralTranslatableMarkup object.
   */
  public static function createFromTranslatedString($count, $translated_string, array $args = [], array $options = []) {
    $plural = new static($count, '', '', $args, $options);
    $plural->translatedString = $translated_string;
    return $plural;
  }

  /**
   * {@inheritdoc}
   */
  protected function translateString(): string {
    $this->translatedString ??= parent::translateString();
    return $this->getStringTranslation()->selectPluralForm($this->count, $this->translatedString, $this->getOptions()['langcode'] ?? NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getArguments(): array {
    return parent::getArguments() + ['@count' => $this->count];
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep(): array {
    return array_merge(parent::__sleep(), ['count']);
  }

}
