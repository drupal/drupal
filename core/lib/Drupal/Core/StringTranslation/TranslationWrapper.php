<?php

/**
 * @file
 * Contains \Drupal\Core\StringTranslation\TranslationWrapper.
 */

namespace Drupal\Core\StringTranslation;

use Drupal\Component\Utility\PlaceholderTrait;
use Drupal\Component\Utility\SafeStringInterface;
use Drupal\Component\Utility\ToStringTrait;

/**
 * Provides translatable string class.
 *
 * This class delays translating strings until rendering them.
 *
 * This is useful for using translation in very low level subsystems like entity
 * definition and stream wrappers.
 *
 * @see \Drupal\Core\StringTranslation\TranslationManager::translate()
 * @see \Drupal\Core\StringTranslation\TranslationManager::translateString()
 * @see \Drupal\Core\Annotation\Translation
 */
class TranslationWrapper implements SafeStringInterface {

  use PlaceholderTrait;
  use ToStringTrait;

  /**
   * The string to be translated.
   *
   * @var string
   */
  protected $string;

  /**
   * The translated string without placeholder replacements.
   *
   * @var string
   */
  protected $translatedString;

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
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

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
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   (optional) The string translation service.
   */
  public function __construct($string, array $arguments = array(), array $options = array(), TranslationInterface $string_translation = NULL) {
    $this->string = $string;
    $this->arguments = $arguments;
    $this->options = $options;
    $this->stringTranslation = $string_translation;
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
   * Gets all options from this translation wrapper.
   *
   * @return mixed[]
   *   The array of options.
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Gets all argments from this translation wrapper.
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
    if (!isset($this->translatedString)) {
      $this->translatedString = $this->getStringTranslation()->translateString($this);
    }

    // Handle any replacements.
    // @todo https://www.drupal.org/node/2509218 Note that the argument
    //   replacement is not stored so that different sanitization strategies can
    //   be used in different contexts.
    if ($args = $this->getArguments()) {
      return $this->placeholderFormat($this->translatedString, $args);
    }
    return $this->translatedString;
  }

  /**
   * Magic __sleep() method to avoid serializing the string translator.
   */
  public function __sleep() {
    return array('string', 'arguments', 'options');
  }

  /**
   * Returns a representation of the object for use in JSON serialization.
   *
   * @return string
   *   The safe string content.
   */
  public function jsonSerialize() {
    return $this->__toString();
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

}
