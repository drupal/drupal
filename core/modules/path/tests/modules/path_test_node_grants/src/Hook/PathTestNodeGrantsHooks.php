<?php

declare(strict_types=1);

namespace Drupal\path_test_node_grants\Hook;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for path_test_node_grants.
 */
class PathTestNodeGrantsHooks {

  /**
   * Implements hook_node_grants().
   */
  #[Hook('node_grants')]
  public function nodeGrants(AccountInterface $account, $operation) : array {
    $grants = [];
    return $grants;
  }

}
