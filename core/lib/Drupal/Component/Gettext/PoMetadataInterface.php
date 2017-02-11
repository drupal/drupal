<?php

namespace Drupal\Component\Gettext;

/**
 * Methods required for both reader and writer implementations.
 *
 * @see \Drupal\Component\Gettext\PoReaderInterface
 * @see \Drupal\Component\Gettext\PoWriterInterface
 */
interface PoMetadataInterface {

  /**
   * Set language code.
   *
   * @param string $langcode
   *   Language code string.
   */
  public function setLangcode($langcode);

  /**
   * Get language code.
   *
   * @return string
   *   Language code string.
   */
  public function getLangcode();

  /**
   * Set header metadata.
   *
   * @param \Drupal\Component\Gettext\PoHeader $header
   *   Header object representing metadata in a PO header.
   */
  public function setHeader(PoHeader $header);

  /**
   * Get header metadata.
   *
   * @return \Drupal\Component\Gettext\PoHeader
   *   Header instance representing metadata in a PO header.
   */
  public function getHeader();

}
