<?php

/**
 * @file
 * Definition of Drupal\locale\PoDatabaseReader.
 */

namespace Drupal\locale;

use Drupal\Component\Gettext\PoHeader;
use Drupal\Component\Gettext\PoItem;
use Drupal\Component\Gettext\PoReaderInterface;

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
  private $_options;

  /**
   * Language code of the language being read from the database.
   *
   * @var string
   */
  private $_langcode;

  /**
   * Store the result of the query so it can be iterated later.
   *
   * @var resource
   */
  private $_result;

  /**
   * Constructor, initializes with default options.
   */
  function __construct() {
    $this->setOptions(array());
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface::getLangcode().
   */
  public function getLangcode() {
    return $this->_langcode;
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface::setLangcode().
   */
  public function setLangcode($langcode) {
    $this->_langcode = $langcode;
  }

  /**
   * Get the options used by the reader.
   */
  function getOptions() {
    return $this->_options;
  }

  /**
   * Set the options for the current reader.
   */
  function setOptions(array $options) {
    $options += array(
      'customized' => FALSE,
      'not_customized' => FALSE,
      'not_translated' => FALSE,
    );
    $this->_options = $options;
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface::getHeader().
   */
  function getHeader() {
    return new PoHeader($this->getLangcode());
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface::setHeader().
   *
   * @throws Exception
   *   Always, because you cannot set the PO header of a reader.
   */
  function setHeader(PoHeader $header) {
    throw new Exception('You cannot set the PO header in a reader.');
  }

  /**
   * Builds and executes a database query based on options set earlier.
   */
  private function buildQuery() {
    $langcode = $this->_langcode;
    $options = $this->_options;

    if (array_sum($options) == 0) {
      // If user asked to not include anything in the translation files,
      // that would not make sense, so just fall back on providing a template.
      $langcode = NULL;
    }

    // Build and execute query to collect source strings and translations.
    $query = db_select('locales_source', 's');
    if (!empty($langcode)) {
      if ($options['not_translated']) {
        // Left join to keep untranslated strings in.
        $query->leftJoin('locales_target', 't', 's.lid = t.lid AND t.language = :language', array(':language' => $langcode));
      }
      else {
        // Inner join to filter for only translations.
        $query->innerJoin('locales_target', 't', 's.lid = t.lid AND t.language = :language', array(':language' => $langcode));
      }
      if ($options['customized']) {
        if (!$options['not_customized']) {
          // Filter for customized strings only.
          $query->condition('t.customized', LOCALE_CUSTOMIZED);
        }
        // Else no filtering needed in this case.
      }
      else {
        if ($options['not_customized']) {
          // Filter for non-customized strings only.
          $query->condition('t.customized', LOCALE_NOT_CUSTOMIZED);
        }
        else {
          // Filter for strings without translation.
          $query->isNull('t.translation');
        }
      }
      $query->fields('t', array('translation'));
    }
    else {
      $query->leftJoin('locales_target', 't', 's.lid = t.lid');
    }
    $query->fields('s', array('lid', 'source', 'context', 'location'));

    $this->_result = $query->execute();
  }

  /**
   * Get the database result resource for the given language and options.
   */
  private function getResult() {
    if (!isset($this->_result)) {
      $this->buildQuery();
    }
    return $this->_result;
  }

  /**
   * Implements Drupal\Component\Gettext\PoReaderInterface::readItem().
   */
  function readItem() {
    $result = $this->getResult();
    $values = $result->fetchAssoc();
    if ($values) {
      $poItem = new PoItem();
      $poItem->setFromArray($values);
      return $poItem;
    }
  }

}
