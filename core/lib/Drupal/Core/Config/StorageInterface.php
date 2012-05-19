<?php

namespace Drupal\Core\Config;

/**
 * Defines an interface for configuration storage manipulation.
 *
 * This class allows reading and writing configuration data from/to the
 * storage and copying to/from the file storing the same data.
 */
interface StorageInterface {

  /**
   * Constructs a storage manipulation class.
   *
   * @param $name
   *   Lowercase string, the name for the configuration data.
   */
  function __construct($name);

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
   */
  public static function encode($data);

  /**
   * Decodes configuration data from the storage-specific format.
   */
  public static function decode($raw);

  /**
   * Gets names starting with this prefix.
   *
   * @param $prefix
   *   @todo
   */
  static function getNamesWithPrefix($prefix);

  /**
   * Gets the name of this object.
   */
  public function getName();
}
