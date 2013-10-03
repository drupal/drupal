<?php

/**
 * @file
 * Definition of Drupal\locale\TranslationString.
 */

namespace Drupal\locale;

/**
 * Defines the locale translation string object.
 *
 * This class represents a translation of a source string to a given language,
 * thus it must have at least a 'language' which is the language code and a
 * 'translation' property which is the translated text of the the source string
 * in the specified language.
 */
class TranslationString extends StringBase {
  /**
   * The language code.
   *
   * @var string
   */
  public $language;

  /**
   * The string translation.
   *
   * @var string
   */
  public $translation;

  /**
   * Integer indicating whether this string is customized.
   *
   * @var int
   */
  public $customized;

  /**
   * Boolean indicating whether the string object is new.
   *
   * @var bool
   */
  protected $is_new;

  /**
   * Overrides Drupal\locale\StringBase::__construct().
   */
  public function __construct($values = array()) {
    parent::__construct($values);
    if (!isset($this->is_new)) {
      // We mark the string as not new if it is a complete translation.
      // This will work when loading from database, otherwise the storage
      // controller that creates the string object must handle it.
      $this->is_new = !$this->isTranslation();
    }
  }

  /**
   * Sets the string as customized / not customized.
   *
   * @param bool $customized
   *   (optional) Whether the string is customized or not. Defaults to TRUE.
   *
   * @return \Drupal\locale\TranslationString
   *   The called object.
   */
  public function setCustomized($customized = TRUE) {
    $this->customized = $customized ? LOCALE_CUSTOMIZED : LOCALE_NOT_CUSTOMIZED;
    return $this;
  }

  /**
   * Implements Drupal\locale\StringInterface::isSource().
   */
  public function isSource() {
    return FALSE;
  }

  /**
   * Implements Drupal\locale\StringInterface::isTranslation().
  */
  public function isTranslation() {
    return !empty($this->lid) && !empty($this->language) && isset($this->translation);
  }

  /**
   * Implements Drupal\locale\StringInterface::getString().
   */
  public function getString() {
    return isset($this->translation) ? $this->translation : '';
  }

  /**
   * Implements Drupal\locale\StringInterface::setString().
   */
  public function setString($string) {
    $this->translation = $string;
    return $this;
  }

  /**
   * Implements Drupal\locale\StringInterface::isNew().
   */
  public function isNew() {
    return $this->is_new;
  }

  /**
   * Implements Drupal\locale\StringInterface::save().
   */
  public function save() {
    parent::save();
    $this->is_new = FALSE;
    return $this;
  }

  /**
   * Implements Drupal\locale\StringInterface::delete().
   */
  public function delete() {
    parent::delete();
    $this->is_new = TRUE;
    return $this;
  }

}
