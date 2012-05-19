<?php

namespace Drupal\Core\Config;

/**
 * Represents the file storage interface.
 *
 * Classes implementing this interface allow reading and writing configuration
 * data to and from disk.
 */
class FileStorage {

  /**
   * Constructs a FileStorage object.
   *
   * @param string $name
   *   The name for the configuration data. Should be lowercase.
   */
  public function __construct($name) {
    $this->name = $name;
  }

  /**
   * Reads and returns a file.
   *
   * @return
   *   The data of the file.
   *
   * @throws
   *   Exception
   */
  protected function readData() {
    $data = file_get_contents($this->getFilePath());
    if ($data === FALSE) {
      throw new FileStorageReadException('Read file is invalid.');
    }
    return $data;
  }

  /**
   * Checks whether the XML configuration file already exists on disk.
   *
   * @return
   *   @todo
   */
  protected function exists() {
    return file_exists($this->getFilePath());
  }

  /**
   * Returns the path to the XML configuration file.
   *
   * @return
   *   @todo
   */
  public function getFilePath() {
    return config_get_config_directory() . '/' . $this->name  . '.xml';
  }

  /**
   * Writes the contents of the configuration file to disk.
   *
   * @param $data
   *   The data to be written to the file.
   *
   * @throws
   *   Exception
   */
  public function write($data) {
    $data = $this->encode($data);
    if (!file_put_contents($this->getFilePath(), $data)) {
      throw new FileStorageException('Failed to write configuration file: ' . $this->getFilePath());
    }
  }

  /**
   * Returns the contents of the configuration file.
   *
   * @return
   *   @todo
   */
  public function read() {
    if ($this->exists()) {
      $data = $this->readData();
      return $this->decode($data);
    }
    return FALSE;
  }

  /**
   * Deletes a configuration file.
   */
  public function delete() {
    // Needs error handling and etc.
    @drupal_unlink($this->getFilePath());
  }

  /**
   * Implements StorageInterface::encode().
   */
  public static function encode($data) {
    // Convert the supplied array into a SimpleXMLElement.
    $xml_object = new \SimpleXMLElement("<?xml version=\"1.0\"?><config></config>");
    self::encodeArrayToXml($data, $xml_object);

    // Pretty print the result.
    $dom = new \DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml_object->asXML());

    return $dom->saveXML();
  }

  /**
   * Encodes an array into XML
   *
   * @param $array
   *   An associative array to encode.
   *
   * @return
   *   A representation of $array in XML.
   */
  protected static function encodeArrayToXml($array, &$xml_object) {
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        if (!is_numeric($key)){
          $subnode = $xml_object->addChild("$key");
          self::encodeArrayToXml($value, $subnode);
        }
        else {
          self::encodeArrayToXml($value, $xml_object);
        }
      }
      else {
        $xml_object->addChild($key, $value);
      }
    }
  }

  /**
   * Implements StorageInterface::decode().
   */
  public static function decode($raw) {
    if (empty($raw)) {
      return array();
    }

    // This is the fastest and easiest way to get from a string of XML to a PHP
    // array since SimpleXML and json_decode()/encode() are native to PHP. Our
    // only other choice would be a custom userspace implementation which would
    // be a lot less performant and more complex.
    $xml = new \SimpleXMLElement($raw);
    $json = json_encode($xml);
    return json_decode($json, TRUE);
  }
}
