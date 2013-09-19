<?php

/**
 * @file
 * Contains \Drupal\node\Access\NodeAddAccessCheck.
 */

namespace Drupal\node\Access;

use Drupal\Core\Entity\EntityCreateAccessCheck;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determines access to for node add pages.
 */
class NodeAddAccessCheck extends EntityCreateAccessCheck {

  /**
   * {@inheritdoc}
   */
  protected $requirementsKey = '_node_add_access';

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    $access_controller = $this->entityManager->getAccessController('node');
    // If a node type is set on the request, just check that.
    if ($request->attributes->has('node_type')) {
      return $access_controller->createAccess($request->attributes->get('node_type')->type) ? static::ALLOW : static::DENY;
    }
    foreach (node_permissions_get_configured_types() as $type) {
      if ($access_controller->createAccess($type->type)) {
        // Allow access if at least one type is permitted.
        return static::ALLOW;
      }
    }
    return static::DENY;
  }

}
