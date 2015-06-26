<?php

/**
 * @file
 * Contains \Drupal\Component\Gettext\PoReaderInterface.
 */

namespace Drupal\Component\Gettext;

use Drupal\Component\Gettext\PoMetadataInterface;

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
