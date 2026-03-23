<?php

namespace Drupal\content_translation\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\CallableResolver;

/**
 * Access check for entity translation overview.
 */
class ContentTranslationOverviewAccess implements AccessInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ?CallableResolver $callableResolver = NULL,
  ) {
    if (!$callableResolver) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $callableResolver argument is deprecated in drupal:11.4.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/node/3567484', E_USER_DEPRECATED);
      $this->callableResolver = \Drupal::service('callable_resolver');
    }
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
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $route_match->getParameter($entity_type_id);
    if ($entity && $entity->isTranslatable()) {
      // Get entity base info.
      $bundle = $entity->bundle();

      // Get entity access callback.
      $definition = $this->entityTypeManager->getDefinition($entity_type_id);
      $translation = $definition->get('translation');
      $access_callback = $translation['content_translation']['access_callback'];
      $access = call_user_func($this->callableResolver->getCallableFromDefinition($access_callback), $entity);
      if ($access->isAllowed()) {
        return $access;
      }

      // Check "translate any entity" permission.
      if ($account->hasPermission('translate any entity')) {
        return AccessResult::allowed()->cachePerPermissions()->inheritCacheability($access);
      }

      // Check per entity permission.
      $permission = "translate {$entity_type_id}";
      if ($definition->getPermissionGranularity() == 'bundle') {
        $permission = "translate {$bundle} {$entity_type_id}";
      }
      return AccessResult::allowedIfHasPermission($account, $permission)->inheritCacheability($access);
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
