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
      if (\Drupal::service('stream_wrapper_manager')->getScheme($entity->getFileUri()) === 'public') {
        if ($operation === 'download') {
          return AccessResult::allowed();
        }
        else {
          return AccessResult::allowedIfHasPermission($account, 'access content');
        }
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
        // the file can access it even if it's not yet used.
        if ($account->isAnonymous()) {
          // For anonymous users, only the browser session that uploaded the
          // file is positively allowed access to it. See file_save_upload().
          // @todo Implement \Drupal\Core\Entity\EntityHandlerInterface so that
          //   services can be more properly injected.
          $allowed_fids = \Drupal::service('session')->get('anonymous_allowed_file_ids', []);
          if (!empty($allowed_fids[$entity->id()])) {
            return AccessResult::allowed()->addCacheContexts(['session', 'user']);
          }
        }
        else {
          return AccessResult::allowed()->addCacheContexts(['user']);
        }
      }
    }

    if ($operation == 'delete' || $operation == 'update') {
      $account = $this->prepareUser($account);
      $file_uid = $entity->get('uid')->getValue();
      // Only the file owner can update or delete the file entity.
      if ($account->id() == $file_uid[0]['target_id']) {
        return AccessResult::allowed();
      }
      return AccessResult::forbidden('Only the file owner can update or delete the file entity.');
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
    // Deny access to fields that should only be set on file creation, and
    // "status" which should only be changed based on a file's usage.
    $create_only_fields = [
      'uri',
      'filemime',
      'filesize',
    ];
    // The operation is 'edit' when the entity is being created or updated.
    // Determine if the entity is being updated by checking if it is new.
    $field_name = $field_definition->getName();
    if ($operation === 'edit' && $items && ($entity = $items->getEntity()) && !$entity->isNew() && in_array($field_name, $create_only_fields, TRUE)) {
      return AccessResult::forbidden();
    }
    // Regardless of whether the entity exists access should be denied to the
    // status field as this is managed via other APIs, for example:
    // - \Drupal\file\FileUsage\FileUsageBase::add()
    // - \Drupal\file\Plugin\EntityReferenceSelection\FileSelection::createNewEntity()
    if ($operation === 'edit' && $field_name === 'status') {
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
    // (e.g. an image for an article). A contributed module is free to alter
    // this to allow file entities to be created directly.
    return AccessResult::neutral();
  }

}
