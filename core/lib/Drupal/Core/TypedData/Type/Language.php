<?php

/**
 * @file
 * Definition of Drupal\Core\TypedData\Type\Language.
 */

namespace Drupal\Core\TypedData\Type;

use InvalidArgumentException;
use Drupal\Core\TypedData\ContextAwareTypedData;

/**
 * Defines the 'language' data type.
 *
 * The plain value of a language is the language object, i.e. an instance of
 * Drupal\Core\Language\Language. For setting the value the language object or
 * the language code as string may be passed.
 *
 * Optionally, this class may be used as computed property, see the supported
 * settings below. E.g., it is used as 'language' property of language items.
 *
 * Supported settings (below the definition's 'settings' key) are:
 *  - langcode source: If used as computed property, the langcode property used
 *    to load the language object.
 */
class Language extends ContextAwareTypedData {

  /**
   * The language code of the language if no 'langcode source' is used.
   *
   * @var string
   */
  protected $langcode;

  /**
   * Implements TypedDataInterface::getValue().
   */
  public function getValue() {
    $source = $this->getLanguageCodeSource();
    $langcode = $source ? $source->getValue() : $this->langcode;
    if ($langcode) {
      return language_load($langcode);
    }
  }

  /**
   * Helper to get the typed data object holding the source language code.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface|FALSE
   */
  protected function getLanguageCodeSource() {
    return !empty($this->definition['settings']['langcode source']) ? $this->parent->get($this->definition['settings']['langcode source']) : FALSE;
  }

  /**
   * Implements TypedDataInterface::setValue().
   *
   * Both the langcode and the language object may be passed as value.
   */
  public function setValue($value) {
    // Support passing language objects.
    if (is_object($value)) {
      $value = $value->langcode;
    }
    elseif (isset($value) && !is_scalar($value)) {
      throw new InvalidArgumentException('Value is no valid langcode or language object.');
    }

    $source = $this->getLanguageCodeSource();
    if ($source) {
      $source->setValue($value);
    }
    else {
      $this->langcode = $value;
    }
  }

  /**
   * Implements TypedDataInterface::getString().
   */
  public function getString() {
    $language = $this->getValue();
    return $language ? $language->name : '';
  }

  /**
   * Implements TypedDataInterface::validate().
   */
  public function validate() {
    // TODO: Implement validate() method.
  }
}
