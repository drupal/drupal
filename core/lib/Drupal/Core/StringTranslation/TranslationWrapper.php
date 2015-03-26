<?php

/**
 * @file
 * Contains \Drupal\Core\StringTranslation\TranslationWrapper.
 */

namespace Drupal\Core\StringTranslation;

/**
 * Provides a class to wrap a translatable string.
 *
 * This class can be used to delay translating strings until the translation
 * system is ready. This is useful for using translation in very low level
 * subsystems like entity definition and stream wrappers.
 *
 * @see \Drupal\Core\Annotation\Translation
 */
class TranslationWrapper {
  use StringTranslationTrait;

  /**
   * The string to be translated.
   *
   * @var string
   */
  protected $string;

  /**
   * The translation arguments.
   *
   * @var array
   */
  protected $arguments;

  /**
   * The translation options.
   *
   * @var array
   */
  protected $options;

  /**
   * Constructs a new class instance.
   *
   * Parses values passed into this class through the t() function in Drupal and
   * handles an optional context for the string.
   *
   * @param string $string
   *   The string that is to be translated.
   * @param array $arguments
   *   (optional) An array with placeholder replacements, keyed by placeholder.
   * @param array $options
   *   (optional) An array of additional options.
   */
  public function __construct($string, array $arguments = array(), array $options = array()) {
    $this->string = $string;
    $this->arguments = $arguments;
    $this->options = $options;
  }

  /**
   * Gets the untranslated string value stored in this translation wrapper.
   *
   * @return string
   *   The string stored in this wrapper.
   */
  public function getUntranslatedString() {
    return $this->string;
  }

  /**
   * Gets a specific option from this translation wrapper.
   *
   * @param $name
   *   Option name.
   *
   * @return mixed
   *   The value of this option or empty string of option is not set.
   */
  public function getOption($name) {
    return isset($this->options[$name]) ? $this->options[$name] : '';
  }

  /**
   * Implements the magic __toString() method.
   */
  public function __toString() {
    return $this->render();
  }

  /**
   * Renders the object as a string.
   *
   * @return string
   *   The translated string.
   */
  public function render() {
    return $this->t($this->string, $this->arguments, $this->options);
  }

  /**
   * Magic __sleep() method to avoid serializing the string translator.
   */
  public function __sleep() {
    return array('string', 'arguments', 'options');
  }

}
