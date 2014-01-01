<?php

/**
 * @file
 * Contains \Drupal\node\Access\NodeAddAccessCheck.
 */

namespace Drupal\node\Access;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determines access to for node add pages.
 */
class NodeAddAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
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
    $access_controller = $this->entityManager->getAccessController('node');
    // If a node type is set on the request, just check that.
    if ($request->attributes->has('node_type')) {
      return $access_controller->createAccess($request->attributes->get('node_type')->type, $account) ? static::ALLOW : static::DENY;
    }
    foreach (node_permissions_get_configured_types() as $type) {
      if ($access_controller->createAccess($type->type, $account)) {
        // Allow access if at least one type is permitted.
        return static::ALLOW;
      }
    }
    return static::DENY;
  }

}
