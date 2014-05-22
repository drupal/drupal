<?php

/**
 * @file
 * Contains \Drupal\field_ui\Access\FormModeAccessCheck.
 */

namespace Drupal\field_ui\Access;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an access check for entity form mode routes.
 *
 * @see \Drupal\entity\Entity\EntityFormMode
 */
class FormModeAccessCheck implements AccessInterface {

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
   * Checks access to the form mode.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param string $form_mode_name
   *   (optional) The form mode. Defaults to 'default'.
   * @param string $bundle
   *   (optional) The bundle. Different entity types can have different names
   *   for their bundle key, so if not specified on the route via a {bundle}
   *   parameter, the access checker determines the appropriate key name, and
   *   gets the value from the corresponding request attribute. For example,
   *   for nodes, the bundle key is "node_type", so the value would be
   *   available via the {node_type} parameter rather than a {bundle}
   *   parameter.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(Route $route, Request $request, AccountInterface $account, $form_mode_name = 'default', $bundle = NULL) {
    if ($entity_type_id = $route->getDefault('entity_type_id')) {
      if (!isset($bundle)) {
        $entity_type = $this->entityManager->getDefinition($entity_type_id);
        $bundle = $request->attributes->get('_raw_variables')->get($entity_type->getBundleEntityType());
      }

      $visibility = FALSE;
      if ($form_mode_name == 'default') {
        $visibility = TRUE;
      }
      elseif ($entity_display = $this->entityManager->getStorage('entity_form_display')->load($entity_type_id . '.' . $bundle . '.' . $form_mode_name)) {
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
