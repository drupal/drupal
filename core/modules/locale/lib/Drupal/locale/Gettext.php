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
   * Reads the given PO files into the database.
   *
   * @param object $file
   *   File object with an URI property pointing at the file's path.
   *   - "langcode": The language the strings will be added to.
   *   - "uri": File URI.
   * @param array $options
   *   An array with options that can have the following elements:
   *   - 'overwrite_options': Overwrite options array as defined in
   *     Drupal\locale\PoDatabaseWriter. Optional, defaults to an empty array.
   *   - 'customized': Flag indicating whether the strings imported from $file
   *     are customized translations or come from a community source. Use
   *     LOCALE_CUSTOMIZED or LOCALE_NOT_CUSTOMIZED. Optional, defaults to
   *     LOCALE_NOT_CUSTOMIZED.
   *   - 'seek': Specifies from which position in the file should the reader
   *     start reading the next items. Optional, defaults to 0.
   *   - 'items': Specifies the number of items to read. Optional, defaults to
   *     -1, which means that all the items from the stream will be read.
   *
   * @return array
   *   Report array as defined in Drupal\locale\PoDatabaseWriter.
   *
   * @see \Drupal\locale\PoDatabaseWriter
   */
  static function fileToDatabase($file, $options) {
    // Add the default values to the options array.
    $options += array(
      'overwrite_options' => array(),
      'customized' => LOCALE_NOT_CUSTOMIZED,
      'items' => -1,
      'seek' => 0,
    );
    // Instantiate and initialize the stream reader for this file.
    $reader = new PoStreamReader();
    $reader->setLangcode($file->langcode);
    $reader->setURI($file->uri);

    try {
      $reader->open();
    }
    catch (\Exception $exception) {
      throw $exception;
    }

    $header = $reader->getHeader();
    if (!$header) {
      throw new \Exception('Missing or malformed header.');
    }

    // Initialize the database writer.
    $writer = new PoDatabaseWriter();
    $writer->setLangcode($file->langcode);
    $writer_options = array(
      'overwrite_options' => $options['overwrite_options'],
      'customized' => $options['customized'],
    );
    $writer->setOptions($writer_options);
    $writer->setHeader($header);

    // Attempt to pipe all items from the file to the database.
    try {
      if ($options['seek']) {
        $reader->setSeek($options['seek']);
      }
      $writer->writeItems($reader, $options['items']);
    }
    catch (\Exception $exception) {
      throw $exception;
    }

    // Report back with an array of status information.
    $report = $writer->getReport();

    // Add the seek position to the report. This is useful for the batch
    // operation.
    $report['seek'] = $reader->getSeek();
    return $report;
  }
}
