<?php

namespace Drupal\Core\Entity\Plugin\DataType;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Plugin\DataType\Deriver\EntityDeriver;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\TypedData\TypedData;

/**
 * Defines the "entity" data type.
 *
 * Instances of this class wrap entity objects and allow to deal with entities
 * based upon the Typed Data API.
 *
 * In addition to the "entity" data type, this exposes derived
 * "entity:$entity_type" and "entity:$entity_type:$bundle" data types.
 */
#[DataType(
  id: "entity",
  label: new TranslatableMarkup("Entity"),
  description: new TranslatableMarkup("All kind of entities, e.g. nodes, comments or users."),
  definition_class: EntityDataDefinition::class,
  deriver: EntityDeriver::class
)]
class EntityAdapter extends TypedData implements \IteratorAggregate, ComplexDataInterface {

  /**
   * The wrapped entity object.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $entity;

  /**
   * Creates an instance wrapping the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity object to wrap.
   *
   * @return static
   */
  public static function createFromEntity(EntityInterface $entity) {
    $definition = EntityDataDefinition::create()
      ->setEntityTypeId($entity->getEntityTypeId())
      ->setBundles([$entity->bundle()]);
    $instance = new static($definition);
    $instance->setValue($entity);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($entity, $notify = TRUE) {
    $this->entity = $entity;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($property_name) {
    if (!isset($this->entity)) {
      throw new MissingDataException("Unable to get property $property_name as no entity has been provided.");
    }
    if (!$this->entity instanceof FieldableEntityInterface) {
      throw new \InvalidArgumentException("Unable to get unknown property $property_name.");
    }
    // This will throw an exception for unknown fields.
    return $this->entity->get($property_name);
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value, $notify = TRUE) {
    if (!isset($this->entity)) {
      throw new MissingDataException("Unable to set property $property_name as no entity has been provided.");
    }
    if (!$this->entity instanceof FieldableEntityInterface) {
      throw new \InvalidArgumentException("Unable to set unknown property $property_name.");
    }
    // This will throw an exception for unknown fields.
    $this->entity->set($property_name, $value, $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties($include_computed = FALSE) {
    if (!isset($this->entity)) {
      throw new MissingDataException('Unable to get properties as no entity has been provided.');
    }
    if (!$this->entity instanceof FieldableEntityInterface) {
      return [];
    }
    return $this->entity->getFields($include_computed);
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    if (!isset($this->entity)) {
      throw new MissingDataException('Unable to get property values as no entity has been provided.');
    }
    return $this->entity->toArray();
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return !isset($this->entity);
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name) {
    if (isset($this->entity) && $this->entity instanceof FieldableEntityInterface) {
      // Let the entity know of any changes.
      $this->entity->onChange($property_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    return isset($this->entity) ? $this->entity->label() : '';
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Apply the default value of all properties.
    foreach ($this->getProperties() as $property) {
      $property->applyDefaultValue(FALSE);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function getIterator() {
    return $this->entity instanceof \IteratorAggregate ? $this->entity->getIterator() : new \ArrayIterator([]);
  }

  /**
   * Returns the wrapped entity object.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The wrapped entity object. If the entity is translatable and a specific
   *   translation is required, always request it by calling ::getTranslation()
   *   or ::getUntranslated() as the language of the returned object is not
   *   defined.
   */
  public function getEntity() {
    return $this->entity;
  }

}
