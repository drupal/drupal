<?php

namespace Drupal\shortcut;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the shortcut set entity type.
 *
 * @see \Drupal\shortcut\Entity\ShortcutSet
 */
class ShortcutSetAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * Constructs a ShortcutSetAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, protected EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($entity_type);
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
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'access shortcuts');

      case 'update':
        if ($account->hasPermission('administer shortcuts')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if (!$account->hasPermission('access shortcuts')) {
          return AccessResult::neutral()->cachePerPermissions();
        }

        $shortcut_set_storage = $this->entityTypeManager->getStorage('shortcut_set');
        return AccessResult::allowedIf($account->hasPermission('customize shortcut links') && $entity == $shortcut_set_storage->getDisplayedToUser($account))->cachePerPermissions()->addCacheableDependency($entity);

      case 'delete':
        return AccessResult::allowedIf($account->hasPermission('administer shortcuts') && $entity->id() != 'default')->cachePerPermissions();

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer shortcuts')->orIf(AccessResult::allowedIfHasPermissions($account, ['access shortcuts', 'customize shortcut links'], 'AND'));
  }

}
