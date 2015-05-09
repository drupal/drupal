<?php

/**
 * @file
 * Definition of Drupal\Component\Gettext\PoItem.
 */

namespace Drupal\Component\Gettext;

/**
 * PoItem handles one translation.
 *
 * @todo: This class contains some really old legacy code.
 * @see https://drupal.org/node/1637662
 */
class PoItem {

  /**
   * The language code this translation is in.
   *
   * @car string
   */
  private $_langcode;

  /**
   * The context this translation belongs to.
   *
   * @var string
   */
  private $_context = '';

  /**
   * The source string or array of strings if it has plurals.
   *
   * @var string or array
   * @see $_plural
   */
  private $_source;

  /**
   * Flag indicating if this translation has plurals.
   *
   * @var bool
   */
  private $_plural;

  /**
   * The comment of this translation.
   *
   * @var string
   */
  private $_comment;

  /**
   * The translation string or array of strings if it has plurals.
   *
   * @var string or array
   * @see $_plural
   */
  private $_translation;

  /**
   * Gets the language code of the currently used language.
   *
   * @return string with langcode
   */
  function getLangcode() {
    return $this->_langcode;
  }

  /**
   * Set the language code of the current language.
   *
   * @param string $langcode
   */
  function setLangcode($langcode) {
    $this->_langcode = $langcode;
  }

  /**
   * Gets the context this translation belongs to.
   *
   * @return string $context
   */
  function getContext() {
    return $this->_context;
  }

  /**
   * Set the context this translation belongs to.
   *
   * @param string $context
   */
  function setContext($context) {
    $this->_context = $context;
  }

  /**
   * Gets the source string or the array of strings if the translation has
   * plurals.
   *
   * @return string or array $translation
   */
  function getSource() {
    return $this->_source;
  }

  /**
   * Set the source string or the array of strings if the translation has
   * plurals.
   *
   * @param string or array $source
   */
  function setSource($source) {
    $this->_source = $source;
  }

  /**
   * Gets the translation string or the array of strings if the translation has
   * plurals.
   *
   * @return string or array $translation
   */
  function getTranslation() {
    return $this->_translation;
  }

  /**
   * Set the translation string or the array of strings if the translation has
   * plurals.
   *
   * @param string or array $translation
   */
  function setTranslation($translation) {
    $this->_translation = $translation;
  }

  /**
   * Set if the translation has plural values.
   *
   * @param bool $plural
   */
  function setPlural($plural) {
    $this->_plural = $plural;
  }

  /**
   * Get if the translation has plural values.
   *
   * @return boolean $plural
   */
  function isPlural() {
    return $this->_plural;
  }

  /**
   * Gets the comment of this translation.
   *
   * @return String $comment
   */
  function getComment() {
    return $this->_comment;
  }

  /**
   * Set the comment of this translation.
   *
   * @param String $comment
   */
  function setComment($comment) {
    $this->_comment = $comment;
  }

  /**
   * Create the PoItem from a structured array.
   *
   * @param array values
   */
  public function setFromArray(array $values = array()) {
    if (isset($values['context'])) {
      $this->setContext($values['context']);
    }
    if (isset($values['source'])) {
      $this->setSource($values['source']);
    }
    if (isset($values['translation'])) {
      $this->setTranslation($values['translation']);
    }
    if (isset($values['comment'])){
      $this->setComment($values['comment']);
    }
    if (isset($this->_source) &&
        strpos($this->_source, LOCALE_PLURAL_DELIMITER) !== FALSE) {
      $this->setSource(explode(LOCALE_PLURAL_DELIMITER, $this->_source));
      $this->setTranslation(explode(LOCALE_PLURAL_DELIMITER, $this->_translation));
      $this->setPlural(count($this->_translation) > 1);
    }
  }

  /**
   * Output the PoItem as a string.
   */
  public function __toString() {
    return $this->formatItem();
  }

  /**
   * Format the POItem as a string.
   */
  private function formatItem() {
    $output = '';

    // Format string context.
    if (!empty($this->_context)) {
      $output .= 'msgctxt ' . $this->formatString($this->_context);
    }

    // Format translation.
    if ($this->_plural) {
      $output .= $this->formatPlural();
    }
    else {
      $output .= $this->formatSingular();
    }

    // Add one empty line to separate the translations.
    $output .= "\n";

    return $output;
  }

  /**
   * Formats a plural translation.
   */
  private function formatPlural() {
    $output = '';

    // Format source strings.
    $output .= 'msgid ' . $this->formatString($this->_source[0]);
    $output .= 'msgid_plural ' . $this->formatString($this->_source[1]);

    foreach ($this->_translation as $i => $trans) {
      if (isset($this->_translation[$i])) {
        $output .= 'msgstr[' . $i . '] ' . $this->formatString($trans);
      }
      else {
        $output .= 'msgstr[' . $i . '] ""' . "\n";
      }
    }

    return $output;
  }

  /**
   * Formats a singular translation.
   */
  private function formatSingular() {
    $output = '';
    $output .= 'msgid ' . $this->formatString($this->_source);
    $output .= 'msgstr ' . (isset($this->_translation) ? $this->formatString($this->_translation) : '""');
    return $output;
  }

  /**
   * Formats a string for output on multiple lines.
   */
  private function formatString($string) {
    // Escape characters for processing.
    $string = addcslashes($string, "\0..\37\\\"");

    // Always include a line break after the explicit \n line breaks from
    // the source string. Otherwise wrap at 70 chars to accommodate the extra
    // format overhead too.
    $parts = explode("\n", wordwrap(str_replace('\n', "\\n\n", $string), 70, " \n"));

    // Multiline string should be exported starting with a "" and newline to
    // have all lines aligned on the same column.
    if (count($parts) > 1) {
      return "\"\"\n\"" . implode("\"\n\"", $parts) . "\"\n";
    }
    // Single line strings are output on the same line.
    else {
      return "\"$parts[0]\"\n";
    }
  }

}
