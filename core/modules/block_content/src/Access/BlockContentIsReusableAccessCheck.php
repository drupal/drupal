<?php

declare(strict_types=1);

namespace Drupal\block_content\Access;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an access check for non-reusable block content entities.
 */
class BlockContentIsReusableAccessCheck implements AccessInterface {

  /**
   * Checks a block_content entity is reusable.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account): AccessResultInterface {
    $parameters = $route_match->getParameters();
    if ($parameters->has('block_content')) {
      $entity = $parameters->get('block_content');
      if ($entity instanceof BlockContentInterface) {
        return AccessResult::allowedIf($entity->isReusable());
      }
    }
    return AccessResult::neutral();
  }

}
