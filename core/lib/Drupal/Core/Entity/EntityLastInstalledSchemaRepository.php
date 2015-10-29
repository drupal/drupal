<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityLastInstalledSchemaRepository.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * Provides a repository for installed entity definitions.
 */
class EntityLastInstalledSchemaRepository implements EntityLastInstalledSchemaRepositoryInterface {

  /**
   * The key-value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValueFactory;

  /**
   * Constructs a new EntityLastInstalledSchemaRepository.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key-value factory.
   */
  public function __construct(KeyValueFactoryInterface $key_value_factory) {
    $this->keyValueFactory = $key_value_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastInstalledDefinition($entity_type_id) {
    return $this->keyValueFactory->get('entity.definitions.installed')->get($entity_type_id . '.entity_type');
  }

  /**
   * {@inheritdoc}
   */
  public function setLastInstalledDefinition(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $this->keyValueFactory->get('entity.definitions.installed')->set($entity_type_id . '.entity_type', $entity_type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLastInstalledDefinition($entity_type_id) {
    $this->keyValueFactory->get('entity.definitions.installed')->delete($entity_type_id . '.entity_type');
    // Clean up field storage definitions as well. Even if the entity type
    // isn't currently fieldable, there might be legacy definitions or an
    // empty array stored from when it was.
    $this->keyValueFactory->get('entity.definitions.installed')->delete($entity_type_id . '.field_storage_definitions');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastInstalledFieldStorageDefinitions($entity_type_id) {
    return $this->keyValueFactory->get('entity.definitions.installed')->get($entity_type_id . '.field_storage_definitions', []);
  }

  /**
   * {@inheritdoc}
   */
  public function setLastInstalledFieldStorageDefinitions($entity_type_id, array $storage_definitions) {
    $this->keyValueFactory->get('entity.definitions.installed')->set($entity_type_id . '.field_storage_definitions', $storage_definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function setLastInstalledFieldStorageDefinition(FieldStorageDefinitionInterface $storage_definition) {
    $entity_type_id = $storage_definition->getTargetEntityTypeId();
    $definitions = $this->getLastInstalledFieldStorageDefinitions($entity_type_id);
    $definitions[$storage_definition->getName()] = $storage_definition;
    $this->setLastInstalledFieldStorageDefinitions($entity_type_id, $definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLastInstalledFieldStorageDefinition(FieldStorageDefinitionInterface $storage_definition) {
    $entity_type_id = $storage_definition->getTargetEntityTypeId();
    $definitions = $this->getLastInstalledFieldStorageDefinitions($entity_type_id);
    unset($definitions[$storage_definition->getName()]);
    $this->setLastInstalledFieldStorageDefinitions($entity_type_id, $definitions);
  }

}
