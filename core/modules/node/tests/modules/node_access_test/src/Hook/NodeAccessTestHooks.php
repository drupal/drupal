<?php

declare(strict_types=1);

namespace Drupal\node_access_test\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for node_access_test.
 */
class NodeAccessTestHooks {

  /**
   * Implements hook_node_grants().
   *
   * Provides three grant realms:
   * - node_access_test_author: Grants users view, update, and delete privileges
   *   on nodes they have authored. Users receive a group ID matching their user
   *   ID on this realm.
   * - node_access_test: Grants users view privileges when they have the
   *   'node test view' permission. Users with this permission receive two group
   *   IDs for the realm, 8888 and 8889. Access for both realms is identical;
   *   the second group is added so that the interaction of multiple groups on
   *   a given grant realm can be tested in NodeAccessPagerTest.
   * - node_access_all: Provides grants for the user whose user ID matches the
   *   'node_access_test.no_access_uid' state variable. Access control on this
   *   realm is not provided in this module; instead,
   *   NodeQueryAlterTest::testNodeQueryAlterOverride() manually writes a node
   *   access record defining the access control for this realm.
   *
   * @see \Drupal\node\Tests\NodeQueryAlterTest::testNodeQueryAlterOverride()
   * @see \Drupal\node\Tests\NodeAccessPagerTest
   * @see node_access_test.permissions.yml
   * @see node_access_test_node_access_records()
   */
  #[Hook('node_grants')]
  public function nodeGrants($account, $operation) {
    $grants = [];
    $grants['node_access_test_author'] = [$account->id()];
    if ($operation == 'view' && $account->hasPermission('node test view')) {
      $grants['node_access_test'] = [8888, 8889];
    }
    $no_access_uid = \Drupal::state()->get('node_access_test.no_access_uid', 0);
    if ($operation == 'view' && $account->id() == $no_access_uid) {
      $grants['node_access_all'] = [0];
    }
    return $grants;
  }

  /**
   * Implements hook_node_access_records().
   *
   * By default, records are written for all nodes. When the
   * 'node_access_test.private' state variable is set to TRUE, records
   * are only written for nodes with a "private" property set, which causes the
   * Node module to write the default global view grant for nodes that are not
   * marked private.
   *
   * @see \Drupal\node\Tests\NodeAccessBaseTableTest::setUp()
   * @see node_access_test_node_grants()
   * @see node_access_test.permissions.yml
   */
  #[Hook('node_access_records')]
  public function nodeAccessRecords(NodeInterface $node) {
    $grants = [];
    // For NodeAccessBaseTableTestCase, only set records for private nodes.
    if (!\Drupal::state()->get('node_access_test.private') || isset($node->private) && $node->private->value) {
      // Groups 8888 and 8889 for the node_access_test realm both receive a view
      // grant for all controlled nodes. See node_access_test_node_grants().
      $grants[] = [
        'realm' => 'node_access_test',
        'gid' => 8888,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
      ];
      $grants[] = [
        'realm' => 'node_access_test',
        'gid' => 8889,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
      ];
      // For the author realm, the group ID is equivalent to a user ID, which
      // means there are many groups of just 1 user.
      $grants[] = [
        'realm' => 'node_access_test_author',
        'gid' => $node->getOwnerId(),
        'grant_view' => 1,
        'grant_update' => 1,
        'grant_delete' => 1,
      ];
    }
    return $grants;
  }

  /**
   * Implements hook_ENTITY_TYPE_access().
   */
  #[Hook('node_access')]
  public function nodeAccess(NodeInterface $node, $operation, AccountInterface $account) {
    $secret_catalan = \Drupal::state()->get('node_access_test_secret_catalan') ?: 0;
    if ($secret_catalan && $node->language()->getId() == 'ca') {
      // Make all Catalan content secret.
      return AccessResult::forbidden()->setCacheMaxAge(0);
    }
    // Grant access if a specific user is specified.
    if (\Drupal::state()->get('node_access_test.allow_uid') === $account->id()) {
      return AccessResult::allowed();
    }
    // No opinion.
    return AccessResult::neutral()->setCacheMaxAge(0);
  }

}
