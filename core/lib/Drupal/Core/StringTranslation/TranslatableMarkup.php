<?php

namespace Drupal\Core\StringTranslation;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\ToStringTrait;
use Drupal\Component\Utility\Unicode;

/**
 * Provides translatable markup class.
 *
 * This object, when cast to a string, will return the formatted, translated
 * string. Avoid casting it to a string yourself, because it is preferable to
 * let the rendering system do the cast as late as possible in the rendering
 * process, so that this object itself can be put, untranslated, into render
 * caches and thus the cache can be shared between different language contexts.
 *
 * @see \Drupal\Component\Render\FormattableMarkup
 * @see \Drupal\Core\StringTranslation\TranslationManager::translateString()
 * @see \Drupal\Core\Annotation\Translation
 */
class TranslatableMarkup extends FormattableMarkup {

  use ToStringTrait;

  /**
   * The translated markup without placeholder replacements.
   *
   * @var string
   */
  protected $translatedMarkup;

  /**
   * The translation options.
   *
   * @var array
   */
  protected $options;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Constructs a new class instance.
   *
   * When possible, use the
   * \Drupal\Core\StringTranslation\StringTranslationTrait $this->t(). Otherwise
   * create a new \Drupal\Core\StringTranslation\TranslatableMarkup object
   * directly.
   *
   * Calling the trait's t() method or instantiating a new TranslatableMarkup
   * object serves two purposes:
   * - At run-time it translates user-visible text into the appropriate
   *   language.
   * - Static analyzers detect calls to t() and new TranslatableMarkup, and add
   *   the first argument (the string to be translated) to the database of
   *   strings that need translation. These strings are expected to be in
   *   English, so the first argument should always be in English.
   * To allow the site to be localized, it is important that all human-readable
   * text that will be displayed on the site or sent to a user is made available
   * in one of the ways supported by the
   * @link https://www.drupal.org/node/322729 Localization API @endlink.
   * See the @link https://www.drupal.org/node/322729 Localization API @endlink
   * pages for more information, including recommendations on how to break up or
   * not break up strings for translation.
   *
   * @section sec_translating_vars Translating Variables
   * $string should always be an English literal string.
   *
   * $string should never contain a variable, such as:
   * @code
   * new TranslatableMarkup($text)
   * @endcode
   * There are several reasons for this:
   * - Using a variable for $string that is user input is a security risk.
   * - Using a variable for $string that has even guaranteed safe text (for
   *   example, user interface text provided literally in code), will not be
   *   picked up by the localization static text processor. (The parameter could
   *   be a variable if the entire string in $text has been passed into t() or
   *   new TranslatableMarkup() elsewhere as the first argument, but that
   *   strategy is not recommended.)
   *
   * It is especially important never to call new TranslatableMarkup($user_text)
   * or t($user_text) where $user_text is some text that a user entered -- doing
   * that can lead to cross-site scripting and other security problems. However,
   * you can use variable substitution in your string, to put variable text such
   * as user names or link URLs into translated text. Variable substitution
   * looks like this:
   * @code
   * new TranslatableMarkup("@name's blog", array('@name' => $account->getDisplayName()));
   * @endcode
   * Basically, you can put placeholders like @name into your string, and the
   * method will substitute the sanitized values at translation time. (See the
   * Localization API pages referenced above and the documentation of
   * \Drupal\Component\Render\FormattableMarkup::placeholderFormat()
   * for details about how to safely and correctly define variables in your
   * string.) Translators can then rearrange the string as necessary for the
   * language (e.g., in Spanish, it might be "blog de @name").
   *
   * @param string $string
   *   A string containing the English text to translate.
   * @param array $arguments
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
   *     string belongs to.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   (optional) The string translation service.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when $string is not a string.
   *
   * @see \Drupal\Component\Render\FormattableMarkup::placeholderFormat()
   * @see \Drupal\Core\StringTranslation\StringTranslationTrait::t()
   *
   * @ingroup sanitization
   */
  public function __construct($string, array $arguments = [], array $options = [], TranslationInterface $string_translation = NULL) {
    if (!is_string($string)) {
      $message = $string instanceof TranslatableMarkup ? '$string ("' . $string->getUntranslatedString() . '") must be a string.' : '$string ("' . (string) $string . '") must be a string.';
      throw new \InvalidArgumentException($message);
    }
    parent::__construct($string, $arguments);
    $this->options = $options;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Gets the untranslated string value stored in this translated string.
   *
   * @return string
   *   The string stored in this wrapper.
   */
  public function getUntranslatedString() {
    return $this->string;
  }

  /**
   * Gets a specific option from this translated string.
   *
   * @param string $name
   *   Option name.
   *
   * @return mixed
   *   The value of this option or empty string of option is not set.
   */
  public function getOption($name) {
    return isset($this->options[$name]) ? $this->options[$name] : '';
  }

  /**
   * Gets all options from this translated string.
   *
   * @return mixed[]
   *   The array of options.
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Gets all arguments from this translated string.
   *
   * @return mixed[]
   *   The array of arguments.
   */
  public function getArguments() {
    return $this->arguments;
  }

  /**
   * Renders the object as a string.
   *
   * @return string
   *   The translated string.
   */
  public function render() {
    if (!isset($this->translatedMarkup)) {
      $this->translatedMarkup = $this->getStringTranslation()->translateString($this);
    }

    // Handle any replacements.
    if ($args = $this->getArguments()) {
      return $this->placeholderFormat($this->translatedMarkup, $args);
    }
    return $this->translatedMarkup;
  }

  /**
   * Magic __sleep() method to avoid serializing the string translator.
   */
  public function __sleep() {
    return ['string', 'arguments', 'options'];
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
   * Returns the string length.
   *
   * @return int
   *   The length of the string.
   */
  public function count() {
    return Unicode::strlen($this->render());
  }

}
