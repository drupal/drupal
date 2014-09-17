<?php

/**
 * @file
 * Contains \Drupal\content_translation\Access\ContentTranslationOverviewAccess.
 */

namespace Drupal\content_translation\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for entity translation overview.
 */
class ContentTranslationOverviewAccess implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a ContentTranslationOverviewAccess object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity type manager.
   */
  public function __construct(EntityManagerInterface $manager) {
    $this->entityManager = $manager;
  }

  /**
   * Checks access to the translation overview for the entity and bundle.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Request $request, AccountInterface $account) {
    $entity_type = $request->attributes->get('entity_type_id');
    $entity = $request->attributes->get($entity_type);
    if ($entity && $entity->isTranslatable()) {
      // Get entity base info.
      $bundle = $entity->bundle();

      // Get entity access callback.
      $definition = $this->entityManager->getDefinition($entity_type);
      $translation = $definition->get('translation');
      $access_callback = $translation['content_translation']['access_callback'];
      $access = call_user_func($access_callback, $entity);
      if ($access->isAllowed()) {
        return $access;
      }

      // Check "translate any entity" permission.
      if ($account->hasPermission('translate any entity')) {
        return $access->allow()->cachePerRole();
      }

      // Check per entity permission.
      $permission = "translate {$entity_type}";
      if ($definition->getPermissionGranularity() == 'bundle') {
        $permission = "translate {$bundle} {$entity_type}";
      }
      return $access->allowIfHasPermission($account, $permission);
    }

    // No opinion.
    return AccessResult::create();
  }
}
