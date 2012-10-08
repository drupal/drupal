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
   * @return Drupal\locale\LocaleString
   *   The called object.
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
   * @return Drupal\locale\LocaleString
   *   The called object.
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
   * @return Drupal\locale\LocaleString
   *   The called object.
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
   * @return Drupal\locale\LocaleString
   *   The called object.
  */
  public function setPlurals($plurals);

  /**
   * Sets the string storage.
   *
   * @param Drupal\locale\StringStorageInterface $storage
   *   The storage to use for this string.
   *
   * @return Drupal\locale\LocaleString
   *   The called object.
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
   * @return Drupal\locale\LocaleString
   *   The called object.
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
   * Saves string object to storage.
   *
   * @return Drupal\locale\LocaleString
   *   The called object.
   *
   * @throws Drupal\locale\StringStorageException
   *   In case of failures, an exception is thrown.
   */
  public function save();

  /**
   * Deletes string object from storage.
   *
   * @return Drupal\locale\LocaleString
   *   The called object.
   *
   * @throws Drupal\locale\StringStorageException
   *   In case of failures, an exception is thrown.
   */
  public function delete();

}
