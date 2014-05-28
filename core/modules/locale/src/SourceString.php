<?php

/**
 * @file
 * Definition of Drupal\locale\SourceString.
 */

namespace Drupal\locale;

use Drupal\locale\LocaleString;

/**
 * Defines the locale source string object.
 *
 * This class represents a module-defined string value that is to be translated.
 * This string must at least contain a 'source' field, which is the raw source
 * value, and is assumed to be in English language.
 */
class SourceString extends StringBase {
  /**
   * Implements Drupal\locale\StringInterface::isSource().
   */
  public function isSource() {
    return isset($this->source);
  }

  /**
   * Implements Drupal\locale\StringInterface::isTranslation().
   */
  public function isTranslation() {
    return FALSE;
  }

  /**
   * Implements Drupal\locale\LocaleString::getString().
   */
  public function getString() {
    return isset($this->source) ? $this->source : '';
  }

  /**
   * Implements Drupal\locale\LocaleString::setString().
   */
  public function setString($string) {
    $this->source = $string;
    return $this;
  }

  /**
   * Implements Drupal\locale\LocaleString::isNew().
   */
  public function isNew() {
    return empty($this->lid);
  }

}
