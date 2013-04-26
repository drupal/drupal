<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\Language.
 */

namespace Drupal\Core\TypedData\Type;

use InvalidArgumentException;
use Drupal\Core\Language\Language as LanguageObject;
use Drupal\Core\TypedData\TypedData;

/**
 * Defines the 'language' data type.
 *
 * The plain value of a language is the language object, i.e. an instance of
 * \Drupal\Core\Language\Language. For setting the value the language object or
 * the language code as string may be passed.
 *
 * Optionally, this class may be used as computed property, see the supported
 * settings below. E.g., it is used as 'language' property of language items.
 *
 * Supported settings (below the definition's 'settings' key) are:
 *  - langcode source: If used as computed property, the langcode property used
 *    to load the language object.
 */
class Language extends TypedData {

  /**
   * The language code of the language if no 'langcode source' is used.
   *
   * @var string
   */
  protected $langcode;

  /**
   * Overrides TypedData::getValue().
   */
  public function getValue() {
    if (!empty($this->definition['settings']['langcode source'])) {
      $this->langcode = $this->parent->__get($this->definition['settings']['langcode source']);
    }
   if ($this->langcode) {
      $language = language_load($this->langcode);
      return $language ?: new LanguageObject(array('langcode' => $this->langcode));
    }
  }

  /**
   * Overrides TypedData::setValue().
   *
   * Both the langcode and the language object may be passed as value.
   */
  public function setValue($value, $notify = TRUE) {
    // Support passing language objects.
    if (is_object($value)) {
      $value = $value->langcode;
    }
    elseif (isset($value) && !is_scalar($value)) {
      throw new InvalidArgumentException('Value is no valid langcode or language object.');
    }
    // Update the 'langcode source' property, if given.
    if (!empty($this->definition['settings']['langcode source'])) {
      $this->parent->__set($this->definition['settings']['langcode source'], $value, $notify);
    }
    else {
      // Notify the parent of any changes to be made.
      if ($notify && isset($this->parent)) {
        $this->parent->onChange($this->name);
      }
      $this->langcode = $value;
    }
  }

  /**
   * Overrides TypedData::getString().
   */
  public function getString() {
    $language = $this->getValue();
    return $language ? $language->name : '';
  }
}
