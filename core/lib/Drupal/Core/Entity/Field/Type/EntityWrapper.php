<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\Type\EntityWrapper.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\Core\TypedData\TypedDataInterface;
use ArrayIterator;
use IteratorAggregate;
use InvalidArgumentException;

/**
 * Defines an 'entity' data type, e.g. the computed 'entity' property of entity references.
 *
 * This object wraps the regular entity object and implements the
 * ComplexDataInterface by forwarding most of its methods to the wrapped entity
 * (if set).
 *
 * The plain value of this wrapper is the entity object, i.e. an instance of
 * Drupal\Core\Entity\EntityInterface. For setting the value the entity object
 * or the entity ID may be passed, whereas passing the ID is only supported if
 * an 'entity type' constraint is specified.
 *
 * Supported constraints (below the definition's 'constraints' key) are:
 *  - EntityType: The entity type.
 *  - Bundle: The bundle or an array of possible bundles.
 *
 * Supported settings (below the definition's 'settings' key) are:
 *  - id source: If used as computed property, the ID property used to load
 *    the entity object.
 */
class EntityWrapper extends TypedData implements IteratorAggregate, ComplexDataInterface {

  /**
   * The referenced entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The entity ID if no 'id source' is used.
   *
   * @var string
   */
  protected $id;

  /**
   * If set, a new entity to create and reference.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $newEntity;

  /**
   * Overrides TypedData::__construct().
   */
  public function __construct(array $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->entityType = isset($this->definition['constraints']['EntityType']) ? $this->definition['constraints']['EntityType'] : NULL;
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::getValue().
   */
  public function getValue() {
    if (isset($this->newEntity)) {
      return $this->newEntity;
    }
    if (!empty($this->definition['settings']['id source'])) {
      $this->id = $this->parent->__get($this->definition['settings']['id source']);
    }
    return $this->id ? entity_load($this->entityType, $this->id) : NULL;
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::setValue().
   *
   * Both the entity ID and the entity object may be passed as value.
   */
  public function setValue($value, $notify = TRUE) {
    // Support passing in the entity object. If it's not yet saved we have
    // to store the whole entity such that it could be saved later on.
    if ($value instanceof EntityInterface && $value->isNew()) {
      $this->newEntity = $value;
      $this->entityType = $value->entityType();
      $value = 0;
    }
    elseif ($value instanceof EntityInterface) {
      $this->entityType = $value->entityType();
      $value = $value->id();
      unset($this->newEntity);
    }
    elseif (isset($value) && !(is_scalar($value) && !empty($this->definition['constraints']['EntityType']))) {
      throw new InvalidArgumentException('Value is not a valid entity.');
    }
    // Update the 'id source' property, if given.
    if (!empty($this->definition['settings']['id source'])) {
      $this->parent->__set($this->definition['settings']['id source'], $value, $notify);
    }
    else {
      // Notify the parent of any changes to be made.
      if ($notify && isset($this->parent)) {
        $this->parent->onChange($this->name);
      }
      $this->id = $value;
    }
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::getString().
   */
  public function getString() {
    if ($entity = $this->getValue()) {
      return $entity->label();
    }
    return '';
  }

  /**
   * Implements \IteratorAggregate::getIterator().
   */
  public function getIterator() {
    if ($entity = $this->getValue()) {
      return $entity->getIterator();
    }
    return new ArrayIterator(array());
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::get().
   */
  public function get($property_name) {
    // @todo: Allow navigating through the tree without data as well.
    if ($entity = $this->getValue()) {
      return $entity->get($property_name);
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::set().
   */
  public function set($property_name, $value, $notify = TRUE) {
    $this->get($property_name)->setValue($value, FALSE);
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getProperties().
   */
  public function getProperties($include_computed = FALSE) {
    if ($entity = $this->getValue()) {
      return $entity->getProperties($include_computed);
    }
    return array();
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinition().
   */
  public function getPropertyDefinition($name) {
    $definitions = $this->getPropertyDefinitions();
    if (isset($definitions[$name])) {
      return $definitions[$name];
    }
    else {
      return FALSE;
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    // @todo: Support getting definitions if multiple bundles are specified.
    return \Drupal::entityManager()->getFieldDefinitionsByConstraints($this->entityType, $this->definition['constraints']);
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyValues().
   */
  public function getPropertyValues() {
    if ($entity = $this->getValue()) {
      return $entity->getPropertyValues();
    }
    return array();
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::setPropertyValues().
   */
  public function setPropertyValues($values) {
    if ($entity = $this->getValue()) {
      $entity->setPropertyValues($values);
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::isEmpty().
   */
  public function isEmpty() {
    return !$this->getValue();
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::onChange().
   */
  public function onChange($property_name) {
    // Notify the parent of changes.
    if (isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }
}
