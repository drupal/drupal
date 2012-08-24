<?php

/**
 * @file
 * Definition of Drupal\config\ConfigurableBase.
 */

namespace Drupal\config;

use Drupal\entity\StorableBase;

/**
 * Defines a base configurable entity class.
 */
abstract class ConfigurableBase extends StorableBase implements ConfigurableInterface {

  /**
   * The original ID of the configurable entity.
   *
   * The ID of a configurable entity is a unique string (machine name). When a
   * configurable entity is updated and its machine name is renamed, the
   * original ID needs to be known.
   *
   * @var string
   */
  protected $originalID;

  /**
   * Overrides Entity::__construct().
   */
  public function __construct(array $values = array(), $entity_type) {
    parent::__construct($values, $entity_type);

    // Backup the original ID, if any.
    if ($original_id = $this->id()) {
      $this->originalID = $original_id;
    }
  }

  /**
   * Implements ConfigurableInterface::getOriginalID().
   */
  public function getOriginalID() {
    return $this->originalID;
  }

  /**
   * Overrides Entity::isNew().
   *
   * EntityInterface::enforceIsNew() is not supported by configurable entities,
   * since each Configurable is unique.
   */
  final public function isNew() {
    return !$this->id();
  }

  /**
   * Overrides Entity::bundle().
   *
   * EntityInterface::bundle() is not supported by configurable entities, since
   * a Configurable is a bundle.
   */
  final public function bundle() {
    return $this->entityType;
  }

  /**
   * Overrides Entity::get().
   *
   * EntityInterface::get() implements support for fieldable entities, but
   * configurable entities are not fieldable.
   */
  public function get($property_name, $langcode = NULL) {
    // @todo: Add support for translatable properties being not fields.
    return isset($this->{$property_name}) ? $this->{$property_name} : NULL;
  }

  /**
   * Overrides Entity::set().
   *
   * EntityInterface::set() implements support for fieldable entities, but
   * configurable entities are not fieldable.
   */
  public function set($property_name, $value, $langcode = NULL) {
    // @todo: Add support for translatable properties being not fields.
    $this->{$property_name} = $value;
  }

  /**
   * Helper callback for uasort() to sort Configurable entities by weight and label.
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
