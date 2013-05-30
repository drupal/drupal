<?php

/**
 * @file
 * Definition of Drupal\Core\Config\Entity\ConfigEntityBase.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Entity\Entity;

/**
 * Defines a base configuration entity class.
 */
abstract class ConfigEntityBase extends Entity implements ConfigEntityInterface {

  /**
   * The original ID of the configuration entity.
   *
   * The ID of a configuration entity is a unique string (machine name). When a
   * configuration entity is updated and its machine name is renamed, the
   * original ID needs to be known.
   *
   * @var string
   */
  protected $originalID;

  /**
   * The enabled/disabled status of the configuration entity.
   *
   * @var bool
   */
  public $status = TRUE;

  /**
   * Overrides Entity::__construct().
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    // Backup the original ID, if any.
    // Configuration entity IDs are strings, and '0' is a valid ID.
    $original_id = $this->id();
    if ($original_id !== NULL && $original_id !== '') {
      $this->setOriginalID($original_id);
    }
  }

  /**
   * Implements ConfigEntityInterface::getOriginalID().
   */
  public function getOriginalID() {
    return $this->originalID;
  }

  /**
   * Implements ConfigEntityInterface::setOriginalID().
   */
  public function setOriginalID($id) {
    $this->originalID = $id;
  }

  /**
   * Overrides Entity::isNew().
   *
   * EntityInterface::enforceIsNew() is only supported for newly created
   * configuration entities but has no effect after saving, since each
   * configuration entity is unique.
   */
  public function isNew() {
    return !empty($this->enforceIsNew);
  }

  /**
   * Overrides Entity::get().
   *
   * EntityInterface::get() implements support for fieldable entities, but
   * configuration entities are not fieldable.
   */
  public function get($property_name, $langcode = NULL) {
    // @todo: Add support for translatable properties being not fields.
    return isset($this->{$property_name}) ? $this->{$property_name} : NULL;
  }

  /**
   * Overrides Entity::set().
   *
   * EntityInterface::set() implements support for fieldable entities, but
   * configuration entities are not fieldable.
   */
  public function set($property_name, $value, $langcode = NULL, $notify = TRUE) {
    // @todo: Add support for translatable properties being not fields.
    $this->{$property_name} = $value;
  }

  /**
   * Implements \Drupal\Core\Config\Entity\ConfigEntityInterface::enable().
   */
  public function enable() {
    return $this->setStatus(TRUE);
  }

  /**
   * Implements \Drupal\Core\Config\Entity\ConfigEntityInterface::disable().
   */
  public function disable() {
    return $this->setStatus(FALSE);
  }

  /**
   * Implements \Drupal\Core\Config\Entity\ConfigEntityInterface::setStatus().
   */
  public function setStatus($status) {
    $this->status = (bool) $status;
    return $this;
  }

  /**
   * Implements \Drupal\Core\Config\Entity\ConfigEntityInterface::status().
   */
  public function status() {
    return !empty($this->status);
  }

  /**
   * Overrides Entity::createDuplicate().
   */
  public function createDuplicate() {
    $duplicate = parent::createDuplicate();
    // Prevent the new duplicate from being misinterpreted as a rename.
    $duplicate->setOriginalID(NULL);
    return $duplicate;
  }

  /**
   * Helper callback for uasort() to sort configuration entities by weight and label.
   */
  public static function sort($a, $b) {
    $a_weight = isset($a->weight) ? $a->weight : 0;
    $b_weight = isset($b->weight) ? $b->weight : 0;
    if ($a_weight == $b_weight) {
      $a_label = $a->label();
      $b_label = $b->label();
      return strnatcasecmp($a_label, $b_label);
    }
    return ($a_weight < $b_weight) ? -1 : 1;
  }

  /**
   * Overrides \Drupal\Core\Entity\Entity::getExportProperties().
   */
  public function getExportProperties() {
    // Configuration objects do not have a schema. Extract all key names from
    // class properties.
    $class_info = new \ReflectionClass($this);
    $properties = array();
    foreach ($class_info->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
      $name = $property->getName();
      $properties[$name] = $this->get($name);
    }
    return $properties;
  }
}
