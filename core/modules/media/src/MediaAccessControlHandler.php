<?php

namespace Drupal\media;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an access control handler for media items.
 */
class MediaAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a MediaAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\media\MediaInterface $entity */
    // Allow admin permission to override all operations.
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $type = $entity->bundle();
    $is_owner = ($account->id() && $account->id() === $entity->getOwnerId());
    switch ($operation) {
      case 'view':
        if ($entity->isPublished()) {
          $access_result = AccessResult::allowedIf($account->hasPermission('view media'))
            ->cachePerPermissions()
            ->addCacheableDependency($entity);
          if (!$access_result->isAllowed()) {
            $access_result->setReason("The 'view media' permission is required when the media item is published.");
          }
        }
        elseif ($account->hasPermission('view own unpublished media')) {
          $access_result = AccessResult::allowedIf($is_owner)
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
          if (!$access_result->isAllowed()) {
            $access_result->setReason("The user must be the owner and the 'view own unpublished media' permission is required when the media item is unpublished.");
          }
        }
        else {
          $access_result = AccessResult::neutral()
            ->cachePerPermissions()
            ->addCacheableDependency($entity)
            ->setReason("The user must be the owner and the 'view own unpublished media' permission is required when the media item is unpublished.");
        }
        return $access_result;

      case 'update':
        if ($account->hasPermission('edit any ' . $type . ' media')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission('edit own ' . $type . ' media') && $is_owner) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        }
        // @todo Deprecate this permission in
        // https://www.drupal.org/project/drupal/issues/2925459.
        if ($account->hasPermission('update any media')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission('update media') && $is_owner) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        }
        return AccessResult::neutral("The following permissions are required: 'update any media' OR 'update own media' OR '$type: edit any media' OR '$type: edit own media'.")->cachePerPermissions();

      case 'delete':
        if ($account->hasPermission('delete any ' . $type . ' media')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission('delete own ' . $type . ' media') && $is_owner) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        }
        // @todo Deprecate this permission in
        // https://www.drupal.org/project/drupal/issues/2925459.
        if ($account->hasPermission('delete any media')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission('delete media') && $is_owner) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        }
        return AccessResult::neutral("The following permissions are required: 'delete any media' OR 'delete own media' OR '$type: delete any media' OR '$type: delete own media'.")->cachePerPermissions();

      case 'view all revisions':
      case 'view revision':
        if ($account->hasPermission('view any ' . $type . ' media revisions') || $account->hasPermission("view all media revisions")) {
          // Check the access to this revision and if the media passed in is not
          // the default revision then access to that too.
          $entity_access = $entity->access('view', $account, TRUE);
          if (!$entity->isDefaultRevision()) {
            $media_storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
            $entity_access->andIf($this->access($media_storage->load($entity->id()), 'view', $account, TRUE));
          }

          return AccessResult::allowed()->cachePerPermissions()->andIf($entity_access);
        }
        return AccessResult::neutral()->cachePerPermissions();

      case 'revert':
        return AccessResult::allowedIfHasPermission($account, 'revert any ' . $type . ' media revisions')
          ->cachePerPermissions()->addCacheableDependency($entity);

      case 'delete revision':
        return AccessResult::allowedIfHasPermission($account, 'delete any ' . $type . ' media revisions')
          ->cachePerPermissions()->addCacheableDependency($entity);

      default:
        return AccessResult::neutral()->cachePerPermissions();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $permissions = [
      'administer media',
      'create media',
      'create ' . $entity_bundle . ' media',
    ];
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

}
