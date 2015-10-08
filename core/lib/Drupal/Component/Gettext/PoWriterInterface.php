<?php

/**
 * @file
 * Contains \Drupal\Component\Gettext\PoWriterInterface.
 */

namespace Drupal\Component\Gettext;

/**
 * Shared interface definition for all Gettext PO Writers.
 */
interface PoWriterInterface extends PoMetadataInterface {

  /**
   * Writes the given item.
   *
   * @param PoItem $item
   *   One specific item to write.
   */
  public function writeItem(PoItem $item);

  /**
   * Writes all or the given amount of items.
   *
   * @param PoReaderInterface $reader
   *   Reader to read PoItems from.
   * @param $count
   *   Amount of items to read from $reader to write. If -1, all items are
   *   read from $reader.
   */
  public function writeItems(PoReaderInterface $reader, $count = -1);

}
