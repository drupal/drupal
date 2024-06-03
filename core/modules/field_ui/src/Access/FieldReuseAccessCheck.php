<?php

namespace Drupal\field_ui\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\FieldStorageConfigInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines an access check for the reuse existing fields form.
 */
class FieldReuseAccessCheck implements AccessInterface {

  /**
   * Creates a new FieldReuseAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $fieldTypePluginManager
   *   The field type plugin manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FieldTypePluginManagerInterface $fieldTypePluginManager,
    protected EntityFieldManagerInterface $entityFieldManager,
  ) {}

  /**
   * Checks access to the reuse existing fields form.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param string|null $bundle
   *   (optional) The bundle. Different entity types can have different names
   *   for their bundle key, so if not specified on the route via a {bundle}
   *   parameter, the access checker determines the appropriate key name, and
   *   gets the value from the corresponding request attribute. For example, for
   *   nodes, the bundle key is "node_type", so the value would be available via
   *   the {node_type} parameter rather than a {bundle} parameter.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, ?string $bundle = NULL): AccessResultInterface {
    $access = AccessResult::neutral();
    if ($entity_type_id = $route->getDefault('entity_type_id')) {
      if (empty($bundle)) {
        $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
        $bundle = $route_match->getRawParameter($entity_type->getBundleEntityType());
      }

      $field_types = $this->fieldTypePluginManager->getDefinitions();
      // Allows access if there are any existing fields and the user
      // correct permissions.
      foreach ($this->entityFieldManager->getFieldStorageDefinitions($entity_type_id) as $field_storage) {
        // Do not include fields with
        // - non-configurable field storages,
        // - locked field storages,
        // - field storages that should not be added via user interface,
        // - field storages that already have a field in the bundle.
        $field_type = $field_storage->getType();
        $access->addCacheableDependency($field_storage);
        if ($field_storage instanceof FieldStorageConfigInterface
          && !$field_storage->isLocked()
          && empty($field_types[$field_type]['no_ui'])
          && !in_array($bundle, $field_storage->getBundles(), TRUE)) {
          $permission = $route->getRequirement('_field_ui_field_reuse_access');
          $access = $access->orIf(AccessResult::allowedIfHasPermission($account, $permission));
        }
      }
      $access->addCacheableDependency($this->entityFieldManager);
    }
    return $access;
  }

}
