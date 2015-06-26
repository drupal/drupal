<?php

/**
 * @file
 * Contains \Drupal\locale\StringBase.
 */

namespace Drupal\locale;

use Drupal\Component\Utility\SafeMarkup;

/**
 * Defines the locale string base class.
 *
 * This is the base class to be used for locale string objects and contains
 * the common properties and methods for source and translation strings.
 */
abstract class StringBase implements StringInterface {
  /**
   * The string identifier.
   *
   * @var integer
   */
  public $lid;

  /**
   * The string locations indexed by type.
   *
   * @var string
   */
  public $locations;

  /**
   * The source string.
   *
   * @var string
   */
  public $source;

  /**
   * The string context.
   *
   * @var string
   */
  public $context;

  /**
   * The string version.
   *
   * @var string
   */
  public $version;

  /**
   * The locale storage this string comes from or is to be saved to.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new locale string object.
   *
   * @param object|array $values
   *   Object or array with initial values.
   */
  public function __construct($values = array()) {
    $this->setValues((array) $values);
  }

  /**
   * Implements Drupal\locale\StringInterface::getId().
   */
  public function getId() {
    return isset($this->lid) ? $this->lid : NULL;
  }

  /**
   * Implements Drupal\locale\StringInterface::setId().
   */
  public function setId($lid) {
    $this->lid = $lid;
    return $this;
  }

  /**
   * Implements Drupal\locale\StringInterface::getVersion().
   */
  public function getVersion() {
    return isset($this->version) ? $this->version : NULL;
  }

  /**
   * Implements Drupal\locale\StringInterface::setVersion().
   */
  public function setVersion($version) {
    $this->version = $version;
    return $this;
  }

  /**
   * Implements Drupal\locale\StringInterface::getPlurals().
   */
  public function getPlurals() {
    return explode(LOCALE_PLURAL_DELIMITER, $this->getString());
  }

  /**
   * Implements Drupal\locale\StringInterface::setPlurals().
   */
  public function setPlurals($plurals) {
    $this->setString(implode(LOCALE_PLURAL_DELIMITER, $plurals));
    return $this;
  }

  /**
   * Implements Drupal\locale\StringInterface::getStorage().
   */
  public function getStorage() {
    return isset($this->storage) ? $this->storage : NULL;
  }

  /**
   * Implements Drupal\locale\StringInterface::setStorage().
   */
  public function setStorage($storage) {
    $this->storage = $storage;
    return $this;
  }

  /**
   * Implements Drupal\locale\StringInterface::setValues().
   */
  public function setValues(array $values, $override = TRUE) {
    foreach ($values as $key => $value) {
      if (property_exists($this, $key) && ($override || !isset($this->$key))) {
        $this->$key = $value;
      }
    }
    return $this;
  }

  /**
   * Implements Drupal\locale\StringInterface::getValues().
   */
  public function getValues(array $fields) {
    $values = array();
    foreach ($fields as $field) {
      if (isset($this->$field)) {
        $values[$field] = $this->$field;
      }
    }
    return $values;
  }

  /**
   * Implements Drupal\locale\StringInterface::getLocation().
   */
  public function getLocations($check_only = FALSE) {
    if (!isset($this->locations) && !$check_only) {
      $this->locations = array();
      foreach ($this->getStorage()->getLocations(array('sid' => $this->getId())) as $location) {
        $this->locations[$location->type][$location->name] = $location->lid;
      }
    }
    return isset($this->locations) ? $this->locations : array();
  }

  /**
   * Implements Drupal\locale\StringInterface::addLocation().
   */
  public function addLocation($type, $name) {
    $this->locations[$type][$name] = TRUE;
    return $this;
  }

  /**
   * Implements Drupal\locale\StringInterface::hasLocation().
   */
  public function hasLocation($type, $name) {
    $locations = $this->getLocations();
    return isset($locations[$type]) ? !empty($locations[$type][$name]) : FALSE;
  }

  /**
   * Implements Drupal\locale\LocaleString::save().
   */
  public function save() {
    if ($storage = $this->getStorage()) {
      $storage->save($this);
    }
    else {
      throw new StringStorageException(SafeMarkup::format('The string cannot be saved because its not bound to a storage: @string', array(
        '@string' => $this->getString(),
      )));
    }
    return $this;
  }

  /**
   * Implements Drupal\locale\LocaleString::delete().
   */
  public function delete() {
    if (!$this->isNew()) {
      if ($storage = $this->getStorage()) {
        $storage->delete($this);
      }
      else {
        throw new StringStorageException(SafeMarkup::format('The string cannot be deleted because its not bound to a storage: @string', array(
          '@string' => $this->getString(),
        )));
      }
    }
    return $this;
  }

}
