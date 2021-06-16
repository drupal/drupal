<?php

namespace Drupal\Component\Gettext;

/**
 * Defines a Gettext PO memory writer, to be used by the installer.
 */
class PoMemoryWriter implements PoWriterInterface {

  /**
   * Array to hold all PoItem elements.
   *
   * @var array
   */
  protected $items;

  /**
   * Constructor, initialize empty items.
   */
  public function __construct() {
    $this->items = [];
  }

  /**
   * {@inheritdoc}
   */
  public function writeItem(PoItem $item) {
    if (is_array($item->getSource())) {
      $item->setSource(implode(PoItem::DELIMITER, $item->getSource()));
      $item->setTranslation(implode(PoItem::DELIMITER, $item->getTranslation()));
    }
    $context = $item->getContext();
    $this->items[$context != NULL ? $context : ''][$item->getSource()] = $item->getTranslation();
  }

  /**
   * {@inheritdoc}
   */
  public function writeItems(PoReaderInterface $reader, $count = -1) {
    $forever = $count == -1;
    while (($count-- > 0 || $forever) && ($item = $reader->readItem())) {
      $this->writeItem($item);
    }
  }

  /**
   * Get all stored PoItem's.
   *
   * @return array PoItem
   */
  public function getData() {
    return $this->items;
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface:setLangcode().
   *
   * Not implemented. Not relevant for the MemoryWriter.
   */
  public function setLangcode($langcode) {
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface:getLangcode().
   *
   * Not implemented. Not relevant for the MemoryWriter.
   */
  public function getLangcode() {
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface:getHeader().
   *
   * Not implemented. Not relevant for the MemoryWriter.
   */
  public function getHeader() {
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface:setHeader().
   *
   * Not implemented. Not relevant for the MemoryWriter.
   */
  public function setHeader(PoHeader $header) {
  }

}
