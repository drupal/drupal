<?php

namespace Drupal\layout_builder\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an access check for the Layout Builder UI.
 *
 * @internal
 */
class LayoutSectionAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new LayoutSectionAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks routing access to layout for the entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    // Attempt to retrieve the generic 'entity' parameter, otherwise look up the
    // specific entity via the entity type ID.
    $entity = $route_match->getParameter('entity') ?: $route_match->getParameter($route_match->getParameter('entity_type_id'));

    // If we don't have an entity, forbid access.
    if (empty($entity)) {
      return AccessResult::forbidden()->addCacheContexts(['route']);
    }

    // If the entity isn't fieldable, forbid access.
    if (!$entity instanceof FieldableEntityInterface || !$entity->hasField('layout_builder__layout')) {
      $access = AccessResult::forbidden();
    }
    else {
      $access = AccessResult::allowedIfHasPermission($account, 'configure any layout');
    }

    return $access->addCacheableDependency($entity);
  }

}
