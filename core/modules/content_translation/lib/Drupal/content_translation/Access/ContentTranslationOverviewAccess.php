<?php

/**
 * @file
 * Contains \Drupal\content_translation\Access\ContentTranslationOverviewAccess.
 */

namespace Drupal\content_translation\Access;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for entity translation overview.
 */
class ContentTranslationOverviewAccess implements StaticAccessCheckInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs a ContentTranslationOverviewAccess object.
   *
   * @param \Drupal\Core\Entity\EntityManager $manager
   *   The entity type manager.
   */
  public function __construct(EntityManager $manager) {
    $this->entityManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_access_content_translation_overview');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    $entity_type = $request->attributes->get('_entity_type');
    if ($entity = $request->attributes->get($entity_type)) {
      // Get entity base info.
      $bundle = $entity->bundle();

      // Get account details from request.
      $account = \Drupal::currentUser();

      // Get entity access callback.
      $definitions = $this->entityManager->getDefinitions();
      $access_callback = $definitions[$entity_type]['translation']['content_translation']['access_callback'];
      if (call_user_func($access_callback, $entity)) {
        return static::ALLOW;
      }

      // Check per entity permission.
      $permission = "translate {$entity_type}";
      if ($definitions[$entity_type]['permission_granularity'] == 'bundle') {
        $permission = "translate {$bundle} {$entity_type}";
      }
      if ($account->hasPermission($permission)) {
        return static::ALLOW;
      }
    }

    return static::DENY;
  }
}
