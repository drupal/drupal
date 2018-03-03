<?php

namespace Drupal\Component\Gettext;

/**
 * Defines a Gettext PO stream writer.
 */
class PoStreamWriter implements PoWriterInterface, PoStreamInterface {

  /**
   * URI of the PO stream that is being written.
   *
   * @var string
   */
  private $_uri;

  /**
   * The Gettext PO header.
   *
   * @var \Drupal\Component\Gettext\PoHeader
   */
  private $_header;

  /**
   * File handle of the current PO stream.
   *
   * @var resource
   */
  private $_fd;

  /**
   * Gets the PO header of the current stream.
   *
   * @return \Drupal\Component\Gettext\PoHeader
   *   The Gettext PO header.
   */
  public function getHeader() {
    return $this->_header;
  }

  /**
   * Set the PO header for the current stream.
   *
   * @param \Drupal\Component\Gettext\PoHeader $header
   *   The Gettext PO header to set.
   */
  public function setHeader(PoHeader $header) {
    $this->_header = $header;
  }

  /**
   * Gets the current language code used.
   *
   * @return string
   *   The language code.
   */
  public function getLangcode() {
    return $this->_langcode;
  }

  /**
   * Set the language code.
   *
   * @param string $langcode
   *   The language code.
   */
  public function setLangcode($langcode) {
    $this->_langcode = $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function open() {
    // Open in write mode. Will overwrite the stream if it already exists.
    $this->_fd = fopen($this->getURI(), 'w');
    // Write the header at the start.
    $this->writeHeader();
  }

  /**
   * Implements Drupal\Component\Gettext\PoStreamInterface::close().
   *
   * @throws \Exception
   *   If the stream is not open.
   */
  public function close() {
    if ($this->_fd) {
      fclose($this->_fd);
    }
    else {
      throw new \Exception('Cannot close stream that is not open.');
    }
  }

  /**
   * Write data to the stream.
   *
   * @param string $data
   *   Piece of string to write to the stream. If the value is not directly a
   *   string, casting will happen in writing.
   *
   * @throws \Exception
   *   If writing the data is not possible.
   */
  private function write($data) {
    $result = fwrite($this->_fd, $data);
    if ($result === FALSE || $result != strlen($data)) {
      throw new \Exception('Unable to write data: ' . substr($data, 0, 20));
    }
  }

  /**
   * Write the PO header to the stream.
   */
  private function writeHeader() {
    $this->write($this->_header);
  }

  /**
   * {@inheritdoc}
   */
  public function writeItem(PoItem $item) {
    $this->write($item);
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
   * Implements Drupal\Component\Gettext\PoStreamInterface::getURI().
   *
   * @throws \Exception
   *   If the URI is not set.
   */
  public function getURI() {
    if (empty($this->_uri)) {
      throw new \Exception('No URI set.');
    }
    return $this->_uri;
  }

  /**
   * {@inheritdoc}
   */
  public function setURI($uri) {
    $this->_uri = $uri;
  }

}
