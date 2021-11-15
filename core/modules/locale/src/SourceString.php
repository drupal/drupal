<?php

namespace Drupal\locale;

/**
 * Defines the locale source string object.
 *
 * This class represents a module-defined string value that is to be translated.
 * This string must at least contain a 'source' field, which is the raw source
 * value, and is assumed to be in English language.
 */
class SourceString extends StringBase {

  /**
   * {@inheritdoc}
   */
  public function isSource() {
    return isset($this->source);
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslation() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    return $this->source ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setString($string) {
    $this->source = $string;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return empty($this->lid);
  }

}
