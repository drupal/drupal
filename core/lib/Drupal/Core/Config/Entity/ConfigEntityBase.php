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
  final public function isNew() {
    // Configuration entity IDs are strings, and '0' is a valid ID.
    return !empty($this->enforceIsNew) || $this->id() === NULL || $this->id() === '';
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
  public function set($property_name, $value, $langcode = NULL) {
    // @todo: Add support for translatable properties being not fields.
    $this->{$property_name} = $value;
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
}
