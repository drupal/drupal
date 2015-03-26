<?php

/**
 * @file
 * Contains \Drupal\comment\CommentAccessControlHandler.
 */

namespace Drupal\comment;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the comment entity type.
 *
 * @see \Drupal\comment\Entity\Comment
 */
class CommentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\user\EntityOwnerInterface $entity */

    if ($account->hasPermission('administer comments')) {
      $access = AccessResult::allowed()->cachePerPermissions();
      return ($operation != 'view') ? $access : $access->andIf($entity->getCommentedEntity()->access($operation, $account, TRUE));
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIf($account->hasPermission('access comments') && $entity->isPublished())->cachePerPermissions()->cacheUntilEntityChanges($entity)
          ->andIf($entity->getCommentedEntity()->access($operation, $account, TRUE));

      case 'update':
        return AccessResult::allowedIf($account->id() && $account->id() == $entity->getOwnerId() && $entity->isPublished() && $account->hasPermission('edit own comments'))->cachePerPermissions()->cachePerUser()->cacheUntilEntityChanges($entity);

      default:
        // No opinion.
        return AccessResult::neutral()->cachePerPermissions();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'post comments');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    if ($operation == 'edit') {
      // Only users with the "administer comments" permission can edit
      // administrative fields.
      $administrative_fields = array(
        'uid',
        'status',
        'created',
        'date',
      );
      if (in_array($field_definition->getName(), $administrative_fields, TRUE)) {
        return AccessResult::allowedIfHasPermission($account, 'administer comments');
      }

      // No user can change read-only fields.
      $read_only_fields = array(
        'hostname',
        'uuid',
        'cid',
        'thread',
        'comment_type',
        'pid',
        'entity_id',
        'entity_type',
        'field_name',
      );
      if (in_array($field_definition->getName(), $read_only_fields, TRUE)) {
        return AccessResult::forbidden();
      }

      // If the field is configured to accept anonymous contact details - admins
      // can edit name, homepage and mail. Anonymous users can also fill in the
      // fields on comment creation.
      if (in_array($field_definition->getName(), ['name', 'mail', 'homepage'], TRUE)) {
        if (!$items) {
          // We cannot make a decision about access to edit these fields if we
          // don't have any items and therefore cannot determine the Comment
          // entity. In this case we err on the side of caution and prevent edit
          // access.
          return AccessResult::forbidden();
        }
        /** @var \Drupal\comment\CommentInterface $entity */
        $entity = $items->getEntity();
        $commented_entity = $entity->getCommentedEntity();
        $anonymous_contact = $commented_entity->get($entity->getFieldName())->getFieldDefinition()->getSetting('anonymous');
        $admin_access = AccessResult::allowedIfHasPermission($account, 'administer comments');
        $anonymous_access = AccessResult::allowedIf($entity->isNew() && $account->isAnonymous() && $anonymous_contact != COMMENT_ANONYMOUS_MAYNOT_CONTACT && $account->hasPermission('post comments'))
          ->cachePerPermissions()
          ->cacheUntilEntityChanges($entity)
          ->cacheUntilEntityChanges($field_definition->getConfig($commented_entity->bundle()))
          ->cacheUntilEntityChanges($commented_entity);
        return $admin_access->orIf($anonymous_access);
      }
    }

    if ($operation == 'view') {
      $entity = $items ? $items->getEntity() : NULL;
      // Admins can view any fields except hostname, other users need both the
      // "access comments" permission and for the comment to be published. The
      // mail field is hidden from non-admins.
      $admin_access = AccessResult::allowedIf($account->hasPermission('administer comments') && $field_definition->getName() != 'hostname')
        ->cachePerPermissions();
      $anonymous_access = AccessResult::allowedIf($account->hasPermission('access comments') && (!$entity || $entity->isPublished()) && !in_array($field_definition->getName(), array('mail', 'hostname'), TRUE))
        ->cachePerPermissions();
      if ($entity) {
        $anonymous_access->cacheUntilEntityChanges($entity);
      }
      return $admin_access->orIf($anonymous_access);
    }
    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

}
