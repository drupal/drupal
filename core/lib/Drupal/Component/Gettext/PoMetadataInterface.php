<?php

/**
 * @file
 * Definition of Drupal\Component\Gettext\PoMetadataInterface.
 */

namespace Drupal\Component\Gettext;

use Drupal\Component\Gettext\PoHeader;

/**
 * Methods required for both reader and writer implementations.
 *
 * @see Drupal\Component\Gettext\PoReaderInterface
 * @see Drupal\Component\Gettext\PoWriterInterface
 */
interface PoMetadataInterface {

  /**
   * Set language code.
   *
   * @param string $langcode
   *   Language code string.
   */
  function setLangcode($langcode);

  /**
   * Get language code.
   *
   * @return string
   *   Language code string.
   */
  function getLangcode();

  /**
   * Set header metadata.
   *
   * @param Drupal\Component\Gettext\PoHeader $header
   *   Header object representing metadata in a PO header.
   */
  function setHeader(PoHeader $header);

  /**
   * Get header metadata.
   *
   * @return Drupal\Component\Gettext\PoHeader $header
   *   Header instance representing metadata in a PO header.
   */
  function getHeader();
}
