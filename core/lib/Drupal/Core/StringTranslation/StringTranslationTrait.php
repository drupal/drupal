<?php

namespace Drupal\Core\StringTranslation;

/**
 * Wrapper methods for \Drupal\Core\StringTranslation\TranslationInterface.
 *
 * Using this trait will add t() and formatPlural() methods to the class. These
 * must be used for every translatable string, similar to how procedural code
 * must use the global functions t() and \Drupal::translation()->formatPlural().
 * This allows string extractor tools to find translatable strings.
 *
 * If the class is capable of injecting services from the container, it should
 * inject the 'string_translation' service and assign it to
 * $this->stringTranslation.
 *
 * @see \Drupal\Core\StringTranslation\TranslationInterface
 * @see container
 *
 * @ingroup i18n
 */
trait StringTranslationTrait {

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Translates a string to the current language or to a given language.
   *
   * See \Drupal\Core\StringTranslation\TranslatableMarkup::__construct() for
   * important security information and usage guidelines.
   *
   * In order for strings to be localized, make them available in one of the
   * ways supported by the
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
  protected function t($string, array $args = array(), array $options = array()) {
    return new TranslatableMarkup($string, $args, $options, $this->getStringTranslation());
  }

  /**
   * Formats a string containing a count of items.
   *
   * @see \Drupal\Core\StringTranslation\TranslationInterface::formatPlural()
   */
  protected function formatPlural($count, $singular, $plural, array $args = array(), array $options = array()) {
    return new PluralTranslatableMarkup($count, $singular, $plural, $args, $options, $this->getStringTranslation());
  }

  /**
   * Returns the number of plurals supported by a given language.
   *
   * @see \Drupal\locale\PluralFormulaInterface::getNumberOfPlurals()
   */
  protected function getNumberOfPlurals($langcode = NULL) {
    if (\Drupal::hasService('locale.plural.formula')) {
      return \Drupal::service('locale.plural.formula')->getNumberOfPlurals($langcode);
    }
    // We assume 2 plurals if Locale's services are not available.
    return 2;
  }

  /**
   * Gets the string translation service.
   *
   * @return \Drupal\Core\StringTranslation\TranslationInterface
   *   The string translation service.
   */
  protected function getStringTranslation() {
    if (!$this->stringTranslation) {
      $this->stringTranslation = \Drupal::service('string_translation');
    }

    return $this->stringTranslation;
  }

  /**
   * Sets the string translation service to use.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   *
   * @return $this
   */
  public function setStringTranslation(TranslationInterface $translation) {
    $this->stringTranslation = $translation;

    return $this;
  }

}
