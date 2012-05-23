<?php

namespace Drupal\Core\Config;

/**
 * Defines an interface for configuration storage manipulation.
 *
 * Classes implementing this interface allow reading and writing configuration
 * data from and to the storage.
 *
 * @todo Remove all active/file methods. They belong onto DrupalConfig only.
 */
interface StorageInterface {

  /**
   * Constructs a storage manipulation class.
   *
   * @param string $name
   *   (optional) The name of a configuration object to load.
   */
  function __construct($name = NULL);

  /**
   * Reads the configuration data from the storage.
   */
  function read();

  /**
   * Copies the configuration data from the storage into a file.
   */
  function copyToFile();

  /**
   * Copies the configuration data from the file into the storage.
   */
  function copyFromFile();

  /**
   * Deletes the configuration data file.
   */
  function deleteFile();

  /**
   * Checks whether the file and the storage is in sync.
   *
   * @return
   *   TRUE if the file and the storage contains the same data, FALSE
   *   if not.
   */
  function isOutOfSync();

  /**
   * Writes the configuration data into the active storage and the file.
   *
   * @param $data
   *   The configuration data to write.
   */
  function write($data);

  /**
   * Writes the configuration data into the active storage but not the file.
   *
   * Use this function if you need to make temporary changes to your
   * configuration.
   *
   * @param $data
   *   The configuration data to write into active storage.
   */
  function writeToActive($data);

  /**
   * Writes the configuration data into the file.
   *
   * @param $data
   *   The configuration data to write into the file.
   */
  function writeToFile($data);

  /**
   * Encodes configuration data into the storage-specific format.
   *
   * @param array $data
   *   The configuration data to encode.
   *
   * @return string
   *   The encoded configuration data.
   *
   * This is a publicly accessible static method to allow for alternative
   * usages in data conversion scripts and also tests.
   */
  public static function encode($data);

  /**
   * Decodes configuration data from the storage-specific format.
   *
   * @param string $raw
   *   The raw configuration data string to decode.
   *
   * @return array
   *   The decoded configuration data as an associative array.
   *
   * This is a publicly accessible static method to allow for alternative
   * usages in data conversion scripts and also tests.
   */
  public static function decode($raw);

  /**
   * Gets the name of this object.
   */
  public function getName();

  /**
   * Sets the name of this object.
   */
  public function setName($name);

  /**
   * Gets configuration object names starting with a given prefix.
   *
   * Given the following configuration objects:
   * - node.type.article
   * - node.type.page
   *
   * Passing the prefix 'node.type' will return an array containing the above
   * names.
   *
   * @param string $prefix
   *   (optional) The prefix to search for. If omitted, all configuration object
   *   names that exist are returned.
   *
   * @return array
   *   An array containing matching configuration object names.
   */
  static function getNamesWithPrefix($prefix = '');
}
