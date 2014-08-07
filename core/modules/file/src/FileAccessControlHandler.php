<?php

/**
 * @file
 * Contains \Drupal\file\FileAccessControlHandler.
 */

namespace Drupal\file;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a File access control handler.
 */
class FileAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {

    if ($operation == 'download') {
      foreach ($this->getFileReferences($entity) as $field_name => $entity_map) {
        foreach ($entity_map as $referencing_entity_type => $referencing_entities) {
          /** @var \Drupal\Core\Entity\EntityInterface $referencing_entity */
          foreach ($referencing_entities as $referencing_entity) {
            if ($referencing_entity->access('view', $account) && $referencing_entity->$field_name->access('view', $account)) {
              return TRUE;
            }
          }
        }
      }

      return FALSE;
    }
  }

  /**
   * Wrapper for file_get_file_references().
   *
   * @param \Drupal\file\FileInterface $file
   *   The file object for which to get references.
   *
   * @return array
   *   A multidimensional array. The keys are field_name, entity_type,
   *   entity_id and the value is an entity referencing this file.
   *
   * @see file_get_file_references()
   */
  protected function getFileReferences(FileInterface $file) {
    return file_get_file_references($file, NULL, EntityStorageInterface::FIELD_LOAD_REVISION, NULL);
  }

}
