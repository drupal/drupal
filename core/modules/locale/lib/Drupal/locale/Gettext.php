<?php

/**
 * @file
 * Definition of Drupal\locale\Gettext.
 */

namespace Drupal\locale;

use Drupal\Component\Gettext\PoStreamReader;
use Drupal\Component\Gettext\PoMemoryWriter;
use Drupal\locale\PoDatabaseWriter;

/**
 * Static class providing Drupal specific Gettext functionality.
 *
 * The operations are related to pumping data from a source to a destination,
 * for example:
 * - Remote files http://*.po to memory
 * - File public://*.po to database
 */
class Gettext {

  /**
   * Reads the given Gettext PO files into a data structure.
   *
   * @param string $langcode
   *   Language code string.
   * @param array $files
   *   List of file objects with uri properties pointing to read.
   *
   * @return array
   *   Structured array as produced by a PoMemoryWriter.
   *
   * @see Drupal\Component\Gettext\PoMemoryWriter
   */
  static function filesToArray($langcode, array $files) {
    $writer = new PoMemoryWriter();
    $writer->setLangcode($langcode);
    foreach ($files as $file) {
      $reader = new PoStreamReader();
      $reader->setURI($file->uri);
      $reader->setLangcode($langcode);
      $reader->open();
      $writer->writeItems($reader, -1);
    }
    return $writer->getData();
  }

  /**
   * Reads the given PO files into the database.
   *
   * @param stdClass $file
   *   File object with an uri property pointing at the file's path.
   * @param string $langcode
   *   Language code string.
   * @param array $overwrite_options
   *   Overwrite options array as defined in Drupal\locale\PoDatabaseWriter.
   * @param boolean $customized
   *   Flag indicating whether the string imported from $file are customized
   *   translations or come from a community source. Use LOCALE_CUSTOMIZED or
   *   LOCALE_NOT_CUSTOMIZED.
   *
   * @return array
   *   Report array as defined in Drupal\locale\PoDatabaseWriter.
   *
   * @see Drupal\locale\PoDatabaseWriter
   */
  static function fileToDatabase($file, $langcode, $overwrite_options, $customized = LOCALE_NOT_CUSTOMIZED) {
    // Instantiate and initialize the stream reader for this file.
    $reader = new PoStreamReader();
    $reader->setLangcode($langcode);
    $reader->setURI($file->uri);

    try {
      $reader->open();
    }
    catch (Exception $exception) {
      throw $exception;
    }

    $header = $reader->getHeader();
    if (!$header) {
      throw new Exception('Missing or malformed header.');
    }

    // Initialize the database writer.
    $writer = new PoDatabaseWriter();
    $writer->setLangcode($langcode);
    $options = array(
      'overwrite_options' => $overwrite_options,
      'customized' => $customized,
    );
    $writer->setOptions($options);
    $writer->setHeader($header);

    // Attempt to pipe all items from the file to the database.
    try {
      $writer->writeItems($reader, -1);
    }
    catch (Exception $exception) {
      throw $exception;
    }

    // Report back with an array of status information.
    return $writer->getReport();
  }
}
