<?php

/**
 * @file
 * Contains \Drupal\content_translation\Access\ContentTranslationOverviewAccess.
 */

namespace Drupal\content_translation\Access;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
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
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    $entity_type = $request->attributes->get('_entity_type_id');
    if ($entity = $request->attributes->get($entity_type)) {
      // Get entity base info.
      $bundle = $entity->bundle();

      // Get entity access callback.
      $definition = $this->entityManager->getDefinition($entity_type);
      $translation = $definition->get('translation');
      $access_callback = $translation['content_translation']['access_callback'];
      if (call_user_func($access_callback, $entity)) {
        return static::ALLOW;
      }

      // Check per entity permission.
      $permission = "translate {$entity_type}";
      if ($definition->getPermissionGranularity() == 'bundle') {
        $permission = "translate {$bundle} {$entity_type}";
      }
      if ($account->hasPermission($permission)) {
        return static::ALLOW;
      }
    }

    return static::DENY;
  }
}
