<?php

/**
 * @file
 * Contains \Drupal\Component\Gettext\PoMemoryWriter.
 */

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
  private $_items;

  /**
   * Constructor, initialize empty items.
   */
  function __construct() {
    $this->_items = array();
  }

  /**
   * Implements Drupal\Component\Gettext\PoWriterInterface::writeItem().
   */
  public function writeItem(PoItem $item) {
    if (is_array($item->getSource())) {
      $item->setSource(implode(LOCALE_PLURAL_DELIMITER, $item->getSource()));
      $item->setTranslation(implode(LOCALE_PLURAL_DELIMITER, $item->getTranslation()));
    }
    $context = $item->getContext();
    $this->_items[$context != NULL ? $context : ''][$item->getSource()] = $item->getTranslation();
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
   * Get all stored PoItem's.
   *
   * @return array PoItem
   */
  public function getData() {
    return $this->_items;
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface:setLangcode().
   *
   * Not implemented. Not relevant for the MemoryWriter.
   */
  function setLangcode($langcode) {
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface:getLangcode().
   *
   * Not implemented. Not relevant for the MemoryWriter.
   */
  function getLangcode() {
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface:getHeader().
   *
   * Not implemented. Not relevant for the MemoryWriter.
   */
  function getHeader() {
  }

  /**
   * Implements Drupal\Component\Gettext\PoMetadataInterface:setHeader().
   *
   * Not implemented. Not relevant for the MemoryWriter.
   */
  function setHeader(PoHeader $header) {
  }

}
