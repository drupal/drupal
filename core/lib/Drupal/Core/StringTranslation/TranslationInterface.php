<?php

namespace Drupal\Core\StringTranslation;

/**
 * Interface for the translation.manager translation service.
 *
 * @ingroup i18n
 */
interface TranslationInterface {

  /**
   * Translates a string to the current language or to a given language.
   *
   * Never call this translate() method directly. In order for strings to be
   * localized, make them available in one of the ways supported by the
   * @link https://www.drupal.org/node/322729 Localization API @endlink. When
   * possible, use the \Drupal\Core\StringTranslation\StringTranslationTrait
   * $this->t(). Otherwise create a new
   * \Drupal\Core\StringTranslation\TranslatableMarkup object.
   *
   * @param string $string
   *   A string containing the English text to translate.
   * @param array $args
   *   (optional) An associative array of replacements to make after
   *   translation. Based on the first character of the key, the value is
   *   escaped and/or themed. See
   *   \Drupal\Component\Render\FormattableMarkup::placeholderFormat() for
   *   details.
   * @param array $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'langcode' (defaults to the current language): A language code, to
   *     translate to a language other than what is used to display the page.
   *   - 'context' (defaults to the empty context): The context the source
   *     string belongs to. See the
   *     @link i18n Internationalization topic @endlink for more information
   *     about string contexts.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   An object that, when cast to a string, returns the translated string.
   *
   * @see \Drupal\Component\Render\FormattableMarkup::placeholderFormat()
   * @see \Drupal\Core\StringTranslation\TranslatableMarkup::__construct()
   *
   * @ingroup sanitization
   */
  public function translate($string, array $args = [], array $options = []);

  /**
   * Translates a TranslatableMarkup object to a string.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $translated_string
   *   A TranslatableMarkup object.
   *
   * @return string
   *   The translated string.
   */
  public function translateString(TranslatableMarkup $translated_string);

  /**
   * Formats a string containing a count of items.
   *
   * This function ensures that the string is pluralized correctly. Since
   * TranslationInterface::translate() is called by this function, make sure not
   * to pass already-localized strings to it. See
   * PluralTranslatableMarkup::createFromTranslatedString() for that.
   *
   * For example:
   * @code
   *   $output = $string_translation->formatPlural($node->comment_count, '1 comment', '@count comments');
   * @endcode
   *
   * Example with additional replacements:
   * @code
   *   $output = $string_translation->formatPlural($update_count,
   *     'Changed the content type of 1 post from %old-type to %new-type.',
   *     'Changed the content type of @count posts from %old-type to %new-type.',
   *     array('%old-type' => $info->old_type, '%new-type' => $info->new_type));
   * @endcode
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
   *   An associative array of replacements to make after translation. Instances
   *   of any key in this array are replaced with the corresponding value.
   *   Based on the first character of the key, the value is escaped and/or
   *   themed. See \Drupal\Component\Render\FormattableMarkup. Note that you do
   *   not need to include @count in this array; this replacement is done
   *   automatically for the plural cases.
   * @param array $options
   *   An associative array of additional options. See t() for allowed keys.
   *
   * @return \Drupal\Core\StringTranslation\PluralTranslatableMarkup
   *   A translated string.
   *
   * @see \Drupal\Core\StringTranslation\TranslationInterface::translate()
   * @see t()
   * @see \Drupal\Component\Render\FormattableMarkup
   * @see \Drupal\Core\StringTranslation\PluralTranslatableMarkup::createFromTranslatedString()
   */
  public function formatPlural($count, $singular, $plural, array $args = [], array $options = []);

}
