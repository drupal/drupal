<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\KeyValueStore\KeyValueContentEntityStorage.
 */

namespace Drupal\Core\Entity\KeyValueStore;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Provides a key value backend for content entities.
 */
class KeyValueContentEntityStorage extends KeyValueEntityStorage implements ContentEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function countFieldData($storage_definition, $as_bool = FALSE) {}

  /**
   * {@inheritdoc}
   */
  public function hasData() {}

  /**
   * {@inheritdoc}
   */
  public function purgeFieldData(FieldDefinitionInterface $field_definition, $batch_size) {}

  /**
   * {@inheritdoc}
   */
  public function finalizePurge(FieldStorageDefinitionInterface $storage_definition) {}

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionCreate(FieldDefinitionInterface $field_definition) {}

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionUpdate(FieldDefinitionInterface $field_definition, FieldDefinitionInterface $original) {}

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionDelete(FieldDefinitionInterface $field_definition) {}

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {}

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {}

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {}

  /**
   * {@inheritdoc}
   */
  public function createTranslation(ContentEntityInterface $entity, $langcode, array $values = []) {}

}
