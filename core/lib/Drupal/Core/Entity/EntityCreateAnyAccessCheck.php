<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines an access checker for creating an entity of any bundle.
 */
class EntityCreateAnyAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The key used by the routing requirement.
   *
   * @var string
   */
  protected $requirementsKey = '_entity_create_any_access';

  /**
   * Constructs an EntityCreateAnyAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * Checks access to create an entity of any bundle for the given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parameterized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $entity_type_id = $route->getRequirement($this->requirementsKey);
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler($entity_type_id);

    // In case there is no "bundle" entity key, check create access with no
    // bundle specified.
    if (!$entity_type->hasKey('bundle')) {
      return $access_control_handler->createAccess(NULL, $account, [], TRUE);
    }

    $access = AccessResult::neutral();
    $bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($entity_type_id));

    // Include list cache tag as access might change if more bundles are added.
    if ($entity_type->getBundleEntityType()) {
      $access->addCacheTags($this->entityTypeManager->getDefinition($entity_type->getBundleEntityType())->getListCacheTags());

      if (empty($route->getOption('_ignore_create_bundle_access'))) {
        // Check if the user is allowed to create new bundles. If so, allow
        // access, so the add page can show a link to create one.
        // @see \Drupal\Core\Entity\Controller\EntityController::addPage()
        $bundle_access_control_handler = $this->entityTypeManager->getAccessControlHandler($entity_type->getBundleEntityType());
        $access = $access->orIf($bundle_access_control_handler->createAccess(NULL, $account, [], TRUE));
        if ($access->isAllowed()) {
          return $access;
        }
      }
    }

    // Check whether an entity of any bundle may be created.
    foreach ($bundles as $bundle) {
      $access = $access->orIf($access_control_handler->createAccess($bundle, $account, [], TRUE));
      // In case there is a least one bundle user can create entities for,
      // access is allowed.
      if ($access->isAllowed()) {
        break;
      }
    }

    return $access;
  }

}
