<?php

namespace Drupal\locale;

use Drupal\Component\Gettext\PoItem;

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
   * @var int
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
  public function __construct($values = []) {
    $this->setValues((array) $values);
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->lid ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($lid) {
    $this->lid = $lid;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return $this->version ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setVersion($version) {
    $this->version = $version;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlurals() {
    return explode(PoItem::DELIMITER, $this->getString());
  }

  /**
   * {@inheritdoc}
   */
  public function setPlurals($plurals) {
    $this->setString(implode(PoItem::DELIMITER, $plurals));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage() {
    return $this->storage ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setStorage($storage) {
    $this->storage = $storage;
    return $this;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getValues(array $fields) {
    $values = [];
    foreach ($fields as $field) {
      if (isset($this->$field)) {
        $values[$field] = $this->$field;
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocations($check_only = FALSE) {
    if (!isset($this->locations) && !$check_only) {
      $this->locations = [];
      foreach ($this->getStorage()->getLocations(['sid' => $this->getId()]) as $location) {
        $this->locations[$location->type][$location->name] = $location->lid;
      }
    }
    return $this->locations ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function addLocation($type, $name) {
    $this->locations[$type][$name] = TRUE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasLocation($type, $name) {
    $locations = $this->getLocations();
    return isset($locations[$type]) ? !empty($locations[$type][$name]) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    if ($storage = $this->getStorage()) {
      $storage->save($this);
    }
    else {
      throw new StringStorageException('The string cannot be saved because its not bound to a storage: ' . $this->getString());
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    if (!$this->isNew()) {
      if ($storage = $this->getStorage()) {
        $storage->delete($this);
      }
      else {
        throw new StringStorageException('The string cannot be deleted because its not bound to a storage: ' . $this->getString());
      }
    }
    return $this;
  }

}
