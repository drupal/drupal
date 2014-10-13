<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Plugin\DataType\Language.
 */

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\TypedData;

/**
 * Defines the 'language' data type.
 *
 * The plain value of a language is the language object, i.e. an instance of
 * \Drupal\Core\Language\Language. For setting the value the language object or
 * the language code as string may be passed.
 *
 * @DataType(
 *   id = "language",
 *   label = @Translation("Language"),
 *   description = @Translation("A language object.")
 * )
 */
class Language extends TypedData {

  /**
   * The id of the language.
   *
   * @var string
   */
  protected $id;

  /**
   * @var \Drupal\Core\Language
   */
  protected $language;

  /**
   * Overrides TypedData::getValue().
   *
   * @return \Drupal\Core\Language\LanguageInterface|null
   */
  public function getValue() {
    if (!isset($this->language) && $this->id) {
      $this->language = language_load($this->id);
    }
    return $this->language;
  }

  /**
   * Overrides TypedData::setValue().
   *
   * Both the langcode and the language object may be passed as value.
   */
  public function setValue($value, $notify = TRUE) {
    // Support passing language objects.
    if (is_object($value)) {
      $this->id = $value->getId();
      $this->language = $value;
    }
    elseif (isset($value) && !is_scalar($value)) {
      throw new \InvalidArgumentException('Value is no valid langcode or language object.');
    }
    else {
      $this->id = $value;
      $this->language = NULL;
    }
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * Overrides TypedData::getString().
   */
  public function getString() {
    $language = $this->getValue();
    return $language ? $language->name : '';
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    if (isset($this->id)) {
      return $this->id;
    }
    elseif (isset($this->language)) {
      return $this->language->getId();
    }
  }

}
