<?php

/**
 * @file
 * Definition of Drupal\locale\PoDatabaseReader.
 */

namespace Drupal\locale;

use Drupal\Component\Gettext\PoHeader;
use Drupal\Component\Gettext\PoItem;
use Drupal\Component\Gettext\PoReaderInterface;
use Drupal\locale\TranslationString;

/**
 * Gettext PO reader working with the locale module database.
 */
class PoDatabaseReader implements PoReaderInterface {

  /**
   * An associative array indicating which type of strings should be read.
   *
   * Elements of the array:
   *  - not_customized: boolean indicating if not customized strings should be
   *    read.
   *  - customized: boolean indicating if customized strings should be read.
   *  - no_translated: boolean indicating if non-translated should be read.
   *
   * The three options define three distinct sets of strings, which combined
   * cover all strings.
   *
   * @var array
   */
  private $options;

  /**
   * Language code of the language being read from the database.
   *
   * @var string
   */
  private $langcode;

  /**
   * Store the result of the query so it can be iterated later.
   *
   * @var resource
   */
  private $result;

  /**
   * Constructor, initializes with default options.
   */
  public function __construct() {
    $this->setOptions(array());
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface::getLangcode().
   */
  public function getLangcode() {
    return $this->langcode;
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface::setLangcode().
   */
  public function setLangcode($langcode) {
    $this->langcode = $langcode;
  }

  /**
   * Get the options used by the reader.
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Set the options for the current reader.
   */
  public function setOptions(array $options) {
    $options += array(
      'customized' => FALSE,
      'not_customized' => FALSE,
      'not_translated' => FALSE,
    );
    $this->options = $options;
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface::getHeader().
   */
  public function getHeader() {
    return new PoHeader($this->getLangcode());
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface::setHeader().
   *
   * @throws Exception
   *   Always, because you cannot set the PO header of a reader.
   */
  public function setHeader(PoHeader $header) {
    throw new \Exception('You cannot set the PO header in a reader.');
  }

  /**
   * Builds and executes a database query based on options set earlier.
   */
  private function loadStrings() {
    $langcode = $this->langcode;
    $options = $this->options;
    $conditions = array();

    if (array_sum($options) == 0) {
      // If user asked to not include anything in the translation files,
      // that would not make sense, so just fall back on providing a template.
      $langcode = NULL;
      // Force option to get both translated and untranslated strings.
      $options['not_translated'] = TRUE;
    }
    // Build and execute query to collect source strings and translations.
    if (!empty($langcode)) {
      $conditions['language'] = $langcode;
      // Translate some options into field conditions.
      if ($options['customized']) {
        if (!$options['not_customized']) {
          // Filter for customized strings only.
          $conditions['customized'] = LOCALE_CUSTOMIZED;
        }
        // Else no filtering needed in this case.
      }
      else {
        if ($options['not_customized']) {
          // Filter for non-customized strings only.
          $conditions['customized'] = LOCALE_NOT_CUSTOMIZED;
        }
        else {
          // Filter for strings without translation.
          $conditions['translated'] = FALSE;
        }
      }
      if (!$options['not_translated']) {
        // Filter for string with translation.
        $conditions['translated'] = TRUE;
      }
      return \Drupal::service('locale.storage')->getTranslations($conditions);
    }
    else {
      // If no language, we don't need any of the target fields.
      return \Drupal::service('locale.storage')->getStrings($conditions);
    }
  }

  /**
   * Get the database result resource for the given language and options.
   */
  private function readString() {
    if (!isset($this->result)) {
      $this->result = $this->loadStrings();
    }
    return array_shift($this->result);
  }

  /**
   * Implements Drupal\Component\Gettext\PoReaderInterface::readItem().
   */
  public function readItem() {
    if ($string = $this->readString()) {
      $values = (array) $string;
      $po_item = new PoItem();
      $po_item->setFromArray($values);
      return $po_item;
    }
  }

}
