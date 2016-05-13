<?php

namespace Drupal\file;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a File access control handler.
 */
class FileAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\file\FileInterface $entity */
    if ($operation == 'download' || $operation == 'view') {
      if (\Drupal::service('file_system')->uriScheme($entity->getFileUri()) === 'public') {
        // Always allow access to file in public file system.
        return AccessResult::allowed();
      }
      elseif ($references = $this->getFileReferences($entity)) {
        foreach ($references as $field_name => $entity_map) {
          foreach ($entity_map as $referencing_entity_type => $referencing_entities) {
            /** @var \Drupal\Core\Entity\EntityInterface $referencing_entity */
            foreach ($referencing_entities as $referencing_entity) {
              $entity_and_field_access = $referencing_entity->access('view', $account, TRUE)->andIf($referencing_entity->$field_name->access('view', $account, TRUE));
              if ($entity_and_field_access->isAllowed()) {
                return $entity_and_field_access;
              }
            }
          }
        }
      }
      elseif ($entity->getOwnerId() == $account->id()) {
        // This case handles new nodes, or detached files. The user who uploaded
        // the file can always access if it's not yet used.
        return AccessResult::allowed();
      }
    }

    if ($operation == 'delete' || $operation == 'update') {
      $account = $this->prepareUser($account);
      $file_uid = $entity->get('uid')->getValue();
      // Only the file owner can delete and update the file entity.
      if ($account->id() == $file_uid[0]['target_id']) {
        return AccessResult::allowed();
      }
      return AccessResult::forbidden();
    }

    // No opinion.
    return AccessResult::neutral();
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

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    // No user can edit the status of a file. Prevents saving a new file as
    // persistent before even validating it.
    if ($field_definition->getName() === 'status' && $operation === 'edit') {
      return AccessResult::forbidden();
    }
    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // The file entity has no "create" permission because by default Drupal core
    // does not allow creating file entities independently. It allows you to
    // create file entities that are referenced from another entity
    // (e.g. an image for a article). A contributed module is free to alter
    // this to allow file entities to be created directly.
    // @todo Update comment to mention REST module when
    //   https://www.drupal.org/node/1927648 is fixed.
    return AccessResult::neutral();
  }

}
