<?php

/**
 * @file
 * Definition of Drupal\locale\StringInterface.
 */

namespace Drupal\locale;

/**
 * Defines the locale string interface.
 */
interface StringInterface {

  /**
   * Gets the string unique identifier.
   *
   * @return int
   *   The string identifier.
   */
  public function getId();

  /**
   * Sets the string unique identifier.
   *
   * @param int $id
   *   The string identifier.
   *
   * @return $this
   */
  public function setId($id);

  /**
   * Gets the string version.
   *
   * @return string
   *   Version identifier.
   */
  public function getVersion();

  /**
   * Sets the string version.
   *
   * @param string $version
   *   Version identifier.
   *
   * @return $this
   */
  public function setVersion($version);

  /**
   * Gets plain string contained in this object.
   *
   * @return string
   *   The string contained in this object.
   */
  public function getString();

  /**
   * Sets the string contained in this object.
   *
   * @param string $string
   *   String to set as value.
   *
   * @return $this
   */
  public function setString($string);

  /**
   * Splits string to work with plural values.
   *
   * @return array
   *   Array of strings that are plural variants.
   */
  public function getPlurals();

  /**
   * Sets this string using array of plural values.
   *
   * Serializes plural variants in one string glued by LOCALE_PLURAL_DELIMITER.
   *
   * @param array $plurals
   *   Array of strings with plural variants.
   *
   * @return $this
   */
  public function setPlurals($plurals);

  /**
   * Gets the string storage.
   *
   * @return \Drupal\locale\StringStorageInterface
   *   The storage used for this string.
   */
  public function getStorage();

  /**
   * Sets the string storage.
   *
   * @param \Drupal\locale\StringStorageInterface $storage
   *   The storage to use for this string.
   *
   * @return $this
   */
  public function setStorage($storage);

  /**
   * Checks whether the object is not saved to storage yet.
   *
   * @return bool
   *   TRUE if the object exists in the storage, FALSE otherwise.
   */
  public function isNew();

  /**
   * Checks whether the object is a source string.
   *
   * @return bool
   *   TRUE if the object is a source string, FALSE otherwise.
   */
  public function isSource();

  /**
   * Checks whether the object is a translation string.
   *
   * @return bool
   *   TRUE if the object is a translation string, FALSE otherwise.
   */
  public function isTranslation();

  /**
   * Sets an array of values as object properties.
   *
   * @param array $values
   *   Array with values indexed by property name,
   * @param bool $override
   *   (optional) Whether to override already set fields, defaults to TRUE.
   *
   * @return $this
   */
  public function setValues(array $values, $override = TRUE);

  /**
   * Gets field values that are set for given field names.
   *
   * @param array $fields
   *   Array of field names.
   *
   * @return array
   *   Array of field values indexed by field name.
   */
  public function getValues(array $fields);

  /**
   * Gets location information for this string.
   *
   * Locations are arbitrary pairs of type and name strings, used to store
   * information about the origins of the string, like the file name it
   * was found on, the path on which it was discovered, etc...
   *
   * A string can have any number of locations since the same string may be
   * found on different places of Drupal code and configuration.
   *
   * @param bool $check_only
   *   (optional) Set to TRUE to get only new locations added during the
   *   current page request and not loading all existing locations.
   *
   * @return array
   *   Location ids indexed by type and name.
   */
  public function getLocations($check_only = FALSE);

  /**
   * Adds a location for this string.
   *
   * @param string $type
   *   Location type that may be any arbitrary string. Types used in Drupal
   *   core are: 'javascript', 'path', 'code', 'configuration'.
   * @param string $name
   *   Location name. Drupal path in case of online discovered translations,
   *   file path in case of imported strings, configuration name for strings
   *   that come from configuration, etc...
   *
   * @return $this
   */
  public function addLocation($type, $name);

  /**
   * Checks whether the string has a given location.
   *
   * @param string $type.
   *   Location type.
   * @param string $name.
   *   Location name.
   *
   * @return bool
   *   TRUE if the string has a location with this type and name.
   */
  public function hasLocation($type, $name);

  /**
   * Saves string object to storage.
   *
   * @return $this
   *
   * @throws \Drupal\locale\StringStorageException
   *   In case of failures, an exception is thrown.
   */
  public function save();

  /**
   * Deletes string object from storage.
   *
   * @return $this
   *
   * @throws \Drupal\locale\StringStorageException
   *   In case of failures, an exception is thrown.
   */
  public function delete();

}
