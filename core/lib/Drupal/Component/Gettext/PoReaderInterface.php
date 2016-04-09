<?php

namespace Drupal\Component\Gettext;

/**
 * Shared interface definition for all Gettext PO Readers.
 */
interface PoReaderInterface extends PoMetadataInterface {

  /**
   * Reads and returns a PoItem (source/translation pair).
   *
   * @return \Drupal\Component\Gettext\PoItem
   *   Wrapper for item data instance.
   */
  public function readItem();

}
