<?php

declare(strict_types=1);

namespace Drupal\node;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines some helpers for the node access control system relating to grants.
 *
 * @ingroup node_access
 */
class NodeGrantsHelper {

  public function __construct(
    protected readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Fetches an array of permission IDs granted to the given user ID.
   *
   * The implementation here provides only the universal "all" grant. A node
   * access module should implement hook_node_grants() to provide a grant list
   * for the user.
   *
   * After the default grants have been loaded, we allow modules to alter the
   * grants array by reference. This hook allows for complex business logic to
   * be applied when integrating multiple node access modules.
   *
   * @param string $operation
   *   The operation that the user is trying to perform.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object for the user performing the operation.
   *
   * @return array
   *   An associative array in which the keys are realms, and the values are
   *   arrays of grants for those realms.
   */
  public function nodeAccessGrants(string $operation, AccountInterface $account): array {
    // Fetch node access grants from other modules.
    $grants = $this->moduleHandler->invokeAll('node_grants', [$account, $operation]);
    // Allow modules to alter the assigned grants.
    $this->moduleHandler->alter('node_grants', $grants, $account, $operation);

    return array_merge(['all' => [0]], $grants);
  }

}
