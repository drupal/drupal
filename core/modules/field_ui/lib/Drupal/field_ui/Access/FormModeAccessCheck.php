<?php

/**
 * @file
 * Contains \Drupal\field_ui\Access\FormModeAccessCheck.
 */

namespace Drupal\field_ui\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Allows access to routes to be controlled by an '_access' boolean parameter.
 */
class FormModeAccessCheck implements StaticAccessCheckInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new FormModeAccessCheck.
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
  public function appliesTo() {
    return array('_field_ui_form_mode_access');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    if ($entity_type = $route->getDefault('entity_type')) {
      $form_mode = $request->attributes->get('form_mode_name');

      if (!($bundle = $request->attributes->get('bundle'))) {
        $entity_info = $this->entityManager->getDefinition($entity_type);
        $bundle = $request->attributes->get('_raw_variables')->get($entity_info['bundle_entity_type']);
      }

      $visibility = FALSE;
      if (!$form_mode || $form_mode == 'default') {
        $visibility = TRUE;
      }
      elseif ($entity_display = $this->entityManager->getStorageController('entity_form_display')->load($entity_type . '.' . $bundle . '.' . $form_mode)) {
        $visibility = $entity_display->status();
      }

      if ($visibility) {
        $permission = $route->getRequirement('_field_ui_form_mode_access');
        return $account->hasPermission($permission) ? static::ALLOW : static::DENY;
      }
    }

    return static::DENY;
  }

}
