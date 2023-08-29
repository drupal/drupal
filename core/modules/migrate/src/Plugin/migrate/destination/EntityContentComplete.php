<?php

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\migrate\EntityFieldDefinitionTrait;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;

/**
 * Provides a destination for migrating the entire entity revision table.
 *
 * @MigrateDestination(
 *   id = "entity_complete",
 *   deriver = "Drupal\migrate\Plugin\Derivative\MigrateEntityComplete"
 * )
 */
class EntityContentComplete extends EntityContentBase {

  use EntityFieldDefinitionTrait;

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [];
    $id_key = $this->getKey('id');
    $ids[$id_key] = $this->getDefinitionFromEntity($id_key);

    $revision_key = $this->getKey('revision');
    if ($revision_key) {
      $ids[$revision_key] = $this->getDefinitionFromEntity($revision_key);
    }

    $langcode_key = $this->getKey('langcode');
    if ($langcode_key) {
      $ids[$langcode_key] = $this->getDefinitionFromEntity($langcode_key);
    }

    return $ids;
  }

  /**
   * Gets the entity.
   *
   * @param \Drupal\migrate\Row $row
   *   The row object.
   * @param array $old_destination_id_values
   *   The old destination IDs.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  protected function getEntity(Row $row, array $old_destination_id_values) {
    $revision_id = $old_destination_id_values
      ? $old_destination_id_values[1]
      : $row->getDestinationProperty($this->getKey('revision'));
    // If we are re-running a migration with set revision IDs and the
    // destination revision ID already exists then do not create a new revision.
    $entity = NULL;
    if (!empty($revision_id)) {
      /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
      $storage = $this->storage;
      if ($entity = $storage->loadRevision($revision_id)) {
        $entity->setNewRevision(FALSE);
      }
    }
    if ($entity === NULL && ($entity_id = $row->getDestinationProperty($this->getKey('id'))) && ($entity = $this->storage->load($entity_id))) {
      // We want to create a new entity. Set enforceIsNew() FALSE is  necessary
      // to properly save a new entity while setting the ID. Without it, the
      // system would see that the ID is already set and assume it is an update.
      $entity->enforceIsNew(FALSE);
      // Intentionally create a new revision. Setting new revision TRUE here may
      // not be necessary, it is done for clarity.
      $entity->setNewRevision(TRUE);
    }
    if ($entity === NULL) {
      // Attempt to set the bundle.
      if ($bundle = $this->getBundle($row)) {
        $row->setDestinationProperty($this->getKey('bundle'), $bundle);
      }

      // Stubs might need some required fields filled in.
      if ($row->isStub()) {
        $this->processStubRow($row);
      }
      $entity = $this->storage->create($row->getDestination());
      $entity->enforceIsNew();
    }

    // We need to update the entity, so that the destination row IDs are
    // correct.
    $entity = $this->updateEntity($entity, $row);
    $entity->isDefaultRevision(TRUE);
    if ($entity instanceof EntityChangedInterface && $entity instanceof ContentEntityInterface) {
      // If we updated any untranslatable fields, update the timestamp for the
      // other translations.
      /** @var \Drupal\Core\Entity\ContentEntityInterface|\Drupal\Core\Entity\EntityChangedInterface $entity */
      foreach ($entity->getTranslationLanguages() as $langcode => $language) {
        // If we updated an untranslated field, then set the changed time for
        // for all translations to match the current row that we are saving.
        // In this context, getChangedTime() should return the value we just
        // set in the updateEntity() call above.
        if ($entity->getTranslation($langcode)->hasTranslationChanges()) {
          $entity->getTranslation($langcode)->setChangedTime($entity->getChangedTime());
        }
      }
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function updateEntity(EntityInterface $entity, Row $row) {
    $entity = parent::updateEntity($entity, $row);
    // Always set the rollback action to delete. This is because the parent
    // updateEntity will set the rollback action to preserve for the original
    // language row, which is needed for the classic node migrations.
    $this->setRollbackAction($row->getIdMap(), MigrateIdMapInterface::ROLLBACK_DELETE);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function save(ContentEntityInterface $entity, array $old_destination_id_values = []) {
    parent::save($entity, $old_destination_id_values);
    return [
      $entity->id(),
      $entity->getRevisionId(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    // We want to delete the entity and all the translations so use
    // Entity:rollback because EntityContentBase::rollback will not remove the
    // default translation.
    Entity::rollback($destination_identifier);
  }

}
