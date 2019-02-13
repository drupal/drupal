<?php

namespace Drupal\Core\Entity\Sql;

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a schema converter for entity types with existing data.
 *
 * @deprecated in Drupal 8.7.0, will be removed before Drupal 9.0.0.
 *   Use \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface::updateFieldableEntityType()
 *   instead.
 *
 * @see https://www.drupal.org/node/3029997
 */
class SqlContentEntityStorageSchemaConverter {

  /**
   * The entity type ID this schema converter is responsible for.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity definition update manager service.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * SqlContentEntityStorageSchemaConverter constructor.
   *
   * @param string $entity_type_id
   *   The ID of the entity type.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entity_definition_update_manager
   *   Entity definition update manager service.
   */
  public function __construct($entity_type_id, EntityTypeManagerInterface $entity_type_manager, EntityDefinitionUpdateManagerInterface $entity_definition_update_manager) {
    @trigger_error('\Drupal\Core\Entity\Sql\SqlContentEntityStorageSchemaConverter is deprecated in Drupal 8.7.0, will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface::updateFieldableEntityType() instead. See https://www.drupal.org/node/3029997.', E_USER_DEPRECATED);
    $this->entityTypeId = $entity_type_id;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDefinitionUpdateManager = $entity_definition_update_manager;
  }

  /**
   * Converts an entity type with existing data to be revisionable.
   *
   * This process does the following tasks:
   *   - creates the schema from scratch with the new revisionable entity type
   *     definition (i.e. the current definition of the entity type from code)
   *     using temporary table names;
   *   - loads the initial entity data by using the last installed entity and
   *     field storage definitions;
   *   - saves the entity data to the temporary tables;
   *   - at the end of the process:
   *     - deletes the original tables and replaces them with the temporary ones
   *       that hold the new (revisionable) entity data;
   *     - updates the installed entity schema data;
   *     - updates the entity type definition in order to trigger the
   *       \Drupal\Core\Entity\EntityTypeEvents::UPDATE event;
   *     - updates the field storage definitions in order to mark the
   *       revisionable ones as such.
   *
   * In case of an error during the entity save process, the temporary tables
   * are deleted and the original entity type and field storage definitions are
   * restored.
   *
   * @param array $sandbox
   *   The sandbox array from a hook_update_N() implementation.
   * @param string[] $fields_to_update
   *   (optional) An array of field names that should be converted to be
   *   revisionable. Note that the 'langcode' field, if present, is updated
   *   automatically. Defaults to an empty array.
   *
   * @throws \Exception
   *   Re-throws any exception raised during the update process.
   */
  public function convertToRevisionable(array &$sandbox, array $fields_to_update = []) {
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    $this->entityTypeManager->useCaches(FALSE);
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);

    /** @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $last_installed_schema_repository */
    $last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');
    $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('entity_test_update');

    // Add the revision ID field.
    $field_name = $entity_type->getKey('revision');
    $field_storage_definitions[$entity_type->getKey('revision')] = BaseFieldDefinition::create('integer')
      ->setName($field_name)
      ->setTargetEntityTypeId($entity_type->id())
      ->setTargetBundle(NULL)
      ->setLabel(t('Revision ID'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    // Add the 'revision_default' field.
    $field_name = $entity_type->getRevisionMetadataKey('revision_default');
    $field_storage_definitions[$field_name] = BaseFieldDefinition::create('boolean')
      ->setName($field_name)
      ->setTargetEntityTypeId($entity_type->id())
      ->setTargetBundle(NULL)
      ->setLabel(t('Default revision'))
      ->setDescription(t('A flag indicating whether this was a default revision when it was saved.'))
      ->setStorageRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE);

    // Add the 'revision_translation_affected' field if needed.
    if ($entity_type->isTranslatable()) {
      $field_name = $entity_type->getKey('revision_translation_affected');
      $field_storage_definitions[$field_name] = BaseFieldDefinition::create('boolean')
        ->setName($field_name)
        ->setTargetEntityTypeId($entity_type->id())
        ->setTargetBundle(NULL)
        ->setLabel(new TranslatableMarkup('Revision translation affected'))
        ->setDescription(new TranslatableMarkup('Indicates if the last edit of a translation belongs to current revision.'))
        ->setReadOnly(TRUE)
        ->setRevisionable(TRUE)
        ->setTranslatable(TRUE);
    }

    // Mark various fields as revisionable.
    $field_storage_definitions[$entity_type->getKey('langcode')]->setRevisionable(TRUE);
    foreach ($fields_to_update as $field_name) {
      $field_storage_definitions[$field_name]->setRevisionable(TRUE);
    }

    $this->entityDefinitionUpdateManager->updateFieldableEntityType($entity_type, $field_storage_definitions, $sandbox);
  }

}
