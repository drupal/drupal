<?php

namespace Drupal\content_translation\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access check for entity translation overview.
 */
class ContentTranslationOverviewAccess implements AccessInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ContentTranslationOverviewAccess object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the translation overview for the entity and bundle.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account, $entity_type_id) {
    /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $route_match->getParameter($entity_type_id);
    if ($entity && $entity->isTranslatable()) {
      // @todo Remove when this module's access layer receives a proper overhaul
      //    that introduces better permission names and alterable return values.
      //    For now the idea is that you only need to have the 'translate any',
      //    entity type or bundle permission to access the overview, even if you
      //    can't do anything there. A better solution would be to introduce an
      //    'access translation overview' permission.
      if ($account->hasPermission('translate any entity')) {
        return AccessResult::allowed()->cachePerPermissions();
      }
      $entity_type_access_permission = $entity->getEntityType()->getPermissionGranularity() == 'bundle'
        ? "translate {$entity->bundle()} $entity_type_id"
        : "translate $entity_type_id";
      if ($account->hasPermission($entity_type_access_permission)) {
        return AccessResult::allowed()->cachePerPermissions();
      }

      // Get entity access callback.
      $definition = $this->entityTypeManager->getDefinition($entity_type_id);
      $translation = $definition->get('translation');
      $access_callback = $translation['content_translation']['access_callback'];
      return call_user_func($access_callback, $entity);
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
