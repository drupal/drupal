<?php
/**
 * @file
 * Contains \Drupal\menu_link_content\MenuLinkContentAccessControlHandler.
 */

namespace Drupal\menu_link_content;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the user entity type.
 */
class MenuLinkContentAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The access manager to check routes by name.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * Creates a new MenuLinkContentAccessControlHandler.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager to check routes by name.
   */
  public function __construct(EntityTypeInterface $entity_type, AccessManagerInterface $access_manager) {
    parent::__construct($entity_type);

    $this->accessManager = $access_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static($entity_type, $container->get('access_manager'));
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        // There is no direct view.
        return AccessResult::neutral();

      case 'update':
        if (!$account->hasPermission('administer menu')) {
          return AccessResult::neutral()->cachePerRole();
        }
        else {
          // If there is a URL, this is an external link so always accessible.
          $access = AccessResult::allowed()->cachePerRole()->cacheUntilEntityChanges($entity);
          if (!$entity->getUrl()) {
            // We allow access, but only if the link is accessible as well.
            $link_access = $this->accessManager->checkNamedRoute($entity->getRouteName(), $entity->getRouteParameters(), $account, TRUE);
            return $access->andIf($link_access);
          }
          return $access;
        }

      case 'delete':
        return AccessResult::allowedIf(!$entity->isNew() && $account->hasPermission('administer menu'))->cachePerRole()->cacheUntilEntityChanges($entity);
    }
  }

}
