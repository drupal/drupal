<?php

namespace Drupal\Core\Entity\Plugin\DataType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\DataReferenceBase;

/**
 * Defines an 'entity_reference' data type.
 *
 * This serves as 'entity' property of entity reference field items and gets
 * its value set from the parent, i.e. LanguageItem.
 *
 * The plain value of this reference is the entity object, i.e. an instance of
 * \Drupal\Core\Entity\EntityInterface. For setting the value the entity object
 * or the entity ID may be passed.
 *
 * Note that the definition of the referenced entity's type is required, whereas
 * defining referencable entity bundle(s) is optional. A reference defining the
 * type and bundle of the referenced entity can be created as following:
 * @code
 * $definition = \Drupal\Core\Entity\EntityDefinition::create($entity_type)
 *   ->addConstraint('Bundle', $bundle);
 * \Drupal\Core\TypedData\DataReferenceDefinition::create('entity')
 *   ->setTargetDefinition($definition);
 * @endcode
 *
 * @DataType(
 *   id = "entity_reference",
 *   label = @Translation("Entity reference"),
 *   definition_class = "\Drupal\Core\TypedData\DataReferenceDefinition"
 * )
 */
class EntityReference extends DataReferenceBase {

  /**
   * The entity ID.
   *
   * @var int|string
   */
  protected $id;

  /**
   * Gets the definition of the referenced entity.
   *
   * @return \Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface
   *   The reference target's definition.
   */
  public function getTargetDefinition() {
    return $this->definition->getTargetDefinition();
  }

  /**
   * Checks whether the target entity has not been saved yet.
   *
   * @return bool
   *   TRUE if the entity is new, FALSE otherwise.
   */
  public function isTargetNew() {
    // If only an ID is given, the reference cannot be a new entity.
    return !isset($this->id) && isset($this->target) && $this->target->getValue()->isNew();
  }

  /**
   * {@inheritdoc}
   */
  public function getTarget() {
    if (!isset($this->target) && isset($this->id)) {
      // If we have a valid reference, return the entity's TypedData adapter.
      $entity = entity_load($this->getTargetDefinition()->getEntityTypeId(), $this->id);
      $this->target = isset($entity) ? $entity->getTypedData() : NULL;
    }
    return $this->target;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetIdentifier() {
    if (isset($this->id)) {
      return $this->id;
    }
    elseif ($entity = $this->getValue()) {
      return $entity->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    unset($this->target);
    unset($this->id);

    // Both the entity ID and the entity object may be passed as value. The
    // reference may also be unset by passing NULL as value.
    if (!isset($value)) {
      $this->target = NULL;
    }
    elseif ($value instanceof EntityInterface) {
      $this->target = $value->getTypedData();
    }
    elseif (!is_scalar($value) || $this->getTargetDefinition()->getEntityTypeId() === NULL) {
      throw new \InvalidArgumentException('Value is not a valid entity.');
    }
    else {
      $this->id = $value;
    }
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    if ($entity = $this->getValue()) {
      return $entity->label();
    }
    return '';
  }
}
