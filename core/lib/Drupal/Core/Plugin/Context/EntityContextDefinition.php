<?php

namespace Drupal\Core\Plugin\Context;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Entity\Plugin\Validation\Constraint\BundleConstraint;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;

/**
 * Defines a class to provide entity context definitions.
 */
class EntityContextDefinition extends ContextDefinition {

  /**
   * {@inheritdoc}
   */
  public function __construct($data_type = 'any', $label = NULL, $required = TRUE, $multiple = FALSE, $description = NULL, $default_value = NULL) {
    // Prefix the data type with 'entity:' so that this class can be constructed
    // like so: new EntityContextDefinition('node')
    if (!str_starts_with($data_type, 'entity:')) {
      $data_type = "entity:$data_type";
    }
    parent::__construct($data_type, $label, $required, $multiple, $description, $default_value);
  }

  /**
   * Returns the entity type ID of this context.
   *
   * @return string
   *   The entity type ID.
   */
  protected function getEntityTypeId() {
    // The data type is the entity type ID prefixed by 'entity:' (7 characters).
    return substr($this->getDataType(), 7);
  }

  /**
   * {@inheritdoc}
   */
  protected function getConstraintObjects() {
    if (!$this->getConstraint('EntityType')) {
      $this->addConstraint('EntityType', [
        'type' => $this->getEntityTypeId(),
      ]);
    }
    return parent::getConstraintObjects();
  }

  /**
   * {@inheritdoc}
   */
  protected function getSampleValues() {
    // Get the constraints from the context's definition.
    $constraints = $this->getConstraintObjects();
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_type_id = $this->getEntityTypeId();
    $entity_type = $entity_type_manager->getDefinition($entity_type_id);
    $storage = $entity_type_manager->getStorage($entity_type_id);
    // If the storage can generate a sample entity we might delegate to that.
    if ($storage instanceof ContentEntityStorageInterface) {
      if (!empty($constraints['Bundle']) && $constraints['Bundle'] instanceof BundleConstraint) {
        foreach ($constraints['Bundle']->getBundleOption() as $bundle) {
          // We have a bundle, we are bundleable and we can generate a sample.
          $values = [$entity_type->getKey('bundle') => $bundle];
          yield EntityAdapter::createFromEntity($storage->create($values));
        }
        return;
      }
    }

    // Either no bundle, or not bundleable, so generate an entity adapter.
    $definition = EntityDataDefinition::create($entity_type_id);
    yield new EntityAdapter($definition);
  }

  /**
   * Creates a context definition from a given entity type ID.
   *
   * @param string $entity_type_id
   *   The entity type ID from which to derive a context definition.
   *
   * @return static
   */
  public static function fromEntityTypeId($entity_type_id) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    return static::fromEntityType($entity_type);
  }

  /**
   * Creates a context definition from a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type from which to derive a context definition.
   *
   * @return static
   */
  public static function fromEntityType(EntityTypeInterface $entity_type) {
    return new static('entity:' . $entity_type->id(), $entity_type->getLabel());
  }

  /**
   * Creates a context definition from a given entity object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity from which to derive a context definition.
   *
   * @return static
   */
  public static function fromEntity(EntityInterface $entity) {
    return static::fromEntityType($entity->getEntityType());
  }

}
