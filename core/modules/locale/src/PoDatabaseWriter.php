<?php

/**
 * @file
 * Contains \Drupal\locale\PoDatabaseWriter.
 */

namespace Drupal\locale;

use Drupal\Component\Gettext\PoHeader;
use Drupal\Component\Gettext\PoItem;
use Drupal\Component\Gettext\PoReaderInterface;
use Drupal\Component\Gettext\PoWriterInterface;

/**
 * Gettext PO writer working with the locale module database.
 */
class PoDatabaseWriter implements PoWriterInterface {

  /**
   * An associative array indicating what data should be overwritten, if any.
   *
   * Elements of the array:
   * - override_options
   *   - not_customized: boolean indicating that not customized strings should
   *     be overwritten.
   *   - customized: boolean indicating that customized strings should be
   *     overwritten.
   * - customized: the strings being imported should be saved as customized.
   *     One of LOCALE_CUSTOMIZED or LOCALE_NOT_CUSTOMIZED.
   *
   * @var array
   */
  private $options;

  /**
   * Language code of the language being written to the database.
   *
   * @var string
   */
  private $langcode;

  /**
   * Header of the po file written to the database.
   *
   * @var \Drupal\Component\Gettext\PoHeader
   */
  private $header;

  /**
   * Associative array summarizing the number of changes done.
   *
   * Keys for the array:
   *  - additions: number of source strings newly added
   *  - updates: number of translations updated
   *  - deletes: number of translations deleted
   *  - skips: number of strings skipped due to disallowed HTML
   *
   * @var array
   */
  private $report;

  /**
   * Constructor, initialize reporting array.
   */
  public function __construct() {
    $this->setReport();
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
   * Get the report of the write operations.
   */
  public function getReport() {
    return $this->report;
  }

  /**
   * Set the report array of write operations.
   *
   * @param array $report
   *   Associative array with result information.
   */
  public function setReport($report = array()) {
    $report += array(
      'additions' => 0,
      'updates' => 0,
      'deletes' => 0,
      'skips' => 0,
      'strings' => array(),
    );
    $this->report = $report;
  }

  /**
   * Get the options used by the writer.
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Set the options for the current writer.
   */
  public function setOptions(array $options) {
    if (!isset($options['overwrite_options'])) {
      $options['overwrite_options'] = array();
    }
    $options['overwrite_options'] += array(
      'not_customized' => FALSE,
      'customized' => FALSE,
    );
    $options += array(
      'customized' => LOCALE_NOT_CUSTOMIZED,
    );
    $this->options = $options;
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface::getHeader().
   */
  public function getHeader() {
    return $this->header;
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface::setHeader().
   *
   * Sets the header and configure Drupal accordingly.
   *
   * Before being able to process the given header we need to know in what
   * context this database write is done. For this the options must be set.
   *
   * A langcode is required to set the current header's PluralForm.
   *
   * @param \Drupal\Component\Gettext\PoHeader $header
   *   Header metadata.
   *
   * @throws Exception
   */
  public function setHeader(PoHeader $header) {
    $this->header = $header;
    $locale_plurals = \Drupal::state()->get('locale.translation.plurals') ?: array();

    // Check for options.
    $options = $this->getOptions();
    if (empty($options)) {
      throw new \Exception('Options should be set before assigning a PoHeader.');
    }
    $overwrite_options = $options['overwrite_options'];

    // Check for langcode.
    $langcode = $this->langcode;
    if (empty($langcode)) {
      throw new \Exception('Langcode should be set before assigning a PoHeader.');
    }

    if (array_sum($overwrite_options) || empty($locale_plurals[$langcode]['plurals'])) {
      // Get and store the plural formula if available.
      $plural = $header->getPluralForms();
      if (isset($plural) && $p = $header->parsePluralForms($plural)) {
        list($nplurals, $formula) = $p;
        \Drupal::service('locale.plural.formula')->setPluralFormula($langcode, $nplurals, $formula);
      }
    }
  }

  /**
   * Implements Drupal\Component\Gettext\PoWriterInterface::writeItem().
   */
  public function writeItem(PoItem $item) {
    if ($item->isPlural()) {
      $item->setSource(implode(LOCALE_PLURAL_DELIMITER, $item->getSource()));
      $item->setTranslation(implode(LOCALE_PLURAL_DELIMITER, $item->getTranslation()));
    }
    $this->importString($item);
  }

  /**
   * Implements Drupal\Component\Gettext\PoWriterInterface::writeItems().
   */
  public function writeItems(PoReaderInterface $reader, $count = -1) {
    $forever = $count == -1;
    while (($count-- > 0 || $forever) && ($item = $reader->readItem())) {
      $this->writeItem($item);
    }
  }

  /**
   * Imports one string into the database.
   *
   * @param \Drupal\Component\Gettext\PoItem $item
   *   The item being imported.
   *
   * @return int
   *   The string ID of the existing string modified or the new string added.
   */
  private function importString(PoItem $item) {
    // Initialize overwrite options if not set.
    $this->options['overwrite_options'] += array(
      'not_customized' => FALSE,
      'customized' => FALSE,
    );
    $overwrite_options = $this->options['overwrite_options'];
    $customized = $this->options['customized'];

    $context = $item->getContext();
    $source = $item->getSource();
    $translation = $item->getTranslation();

    // Look up the source string and any existing translation.
    $strings = \Drupal::service('locale.storage')->getTranslations(array(
      'language' => $this->langcode,
      'source' => $source,
      'context' => $context,
    ));
    $string = reset($strings);

    if (!empty($translation)) {
      // Skip this string unless it passes a check for dangerous code.
      if (!locale_string_is_safe($translation)) {
        \Drupal::logger('locale')->error('Import of string "%string" was skipped because of disallowed or malformed HTML.', array('%string' => $translation));
        $this->report['skips']++;
        return 0;
      }
      elseif ($string) {
        $string->setString($translation);
        if ($string->isNew()) {
          // No translation in this language.
          $string->setValues(array(
            'language' => $this->langcode,
            'customized' => $customized,
          ));
          $string->save();
          $this->report['additions']++;
        }
        elseif ($overwrite_options[$string->customized ? 'customized' : 'not_customized']) {
          // Translation exists, only overwrite if instructed.
          $string->customized = $customized;
          $string->save();
          $this->report['updates']++;
        }
        $this->report['strings'][] = $string->getId();
        return $string->lid;
      }
      else {
        // No such source string in the database yet.
        $string = \Drupal::service('locale.storage')->createString(array('source' => $source, 'context' => $context))
          ->save();
        \Drupal::service('locale.storage')->createTranslation(array(
          'lid' => $string->getId(),
          'language' => $this->langcode,
          'translation' => $translation,
          'customized' => $customized,
        ))->save();

        $this->report['additions']++;
        $this->report['strings'][] = $string->getId();
        return $string->lid;
      }
    }
    elseif ($string && !$string->isNew() && $overwrite_options[$string->customized ? 'customized' : 'not_customized']) {
      // Empty translation, remove existing if instructed.
      $string->delete();
      $this->report['deletes']++;
      $this->report['strings'][] = $string->lid;
      return $string->lid;
    }
  }

}
