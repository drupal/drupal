<?php

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
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        // There is no direct viewing of a menu link, but still for purposes of
        // content_translation we need a generic way to check access.
        return AccessResult::allowedIfHasPermission($account, 'administer menu');

      case 'update':
        if (!$account->hasPermission('administer menu')) {
          return AccessResult::neutral()->cachePerPermissions();
        }
        else {
          // If there is a URL, this is an external link so always accessible.
          $access = AccessResult::allowed()->cachePerPermissions()->addCacheableDependency($entity);
          /** @var \Drupal\menu_link_content\MenuLinkContentInterface $entity */
          // We allow access, but only if the link is accessible as well.
          if (($url_object = $entity->getUrlObject()) && $url_object->isRouted()) {
            $link_access = $this->accessManager->checkNamedRoute($url_object->getRouteName(), $url_object->getRouteParameters(), $account, TRUE);
            $access = $access->andIf($link_access);
          }
          return $access;
        }

      case 'delete':
        return AccessResult::allowedIf(!$entity->isNew() && $account->hasPermission('administer menu'))->cachePerPermissions()->addCacheableDependency($entity);
    }
  }

}
