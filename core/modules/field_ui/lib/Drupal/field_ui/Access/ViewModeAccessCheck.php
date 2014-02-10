<?php

/**
 * @file
 * Contains \Drupal\field_ui\Access\ViewModeAccessCheck.
 */

namespace Drupal\field_ui\Access;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Allows access to routes to be controlled by an '_access' boolean parameter.
 */
class ViewModeAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new ViewModeAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    if ($entity_type_id = $route->getDefault('entity_type_id')) {
      $view_mode = $request->attributes->get('view_mode_name');

      if (!($bundle = $request->attributes->get('bundle'))) {
        $entity_type = $this->entityManager->getDefinition($entity_type_id);
        $bundle = $request->attributes->get('_raw_variables')->get($entity_type->getBundleEntityType());
      }

      $visibility = FALSE;
      if (!$view_mode || $view_mode == 'default') {
        $visibility = TRUE;
      }
      elseif ($entity_display = $this->entityManager->getStorageController('entity_view_display')->load($entity_type_id . '.' . $bundle . '.' . $view_mode)) {
        $visibility = $entity_display->status();
      }

      if ($visibility) {
        $permission = $route->getRequirement('_field_ui_view_mode_access');
        return $account->hasPermission($permission) ? static::ALLOW : static::DENY;
      }
    }

    return static::DENY;
  }

}
