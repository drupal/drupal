<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Database\Database;
use Drupal\user\Entity\User;

/**
 * Tests that node access queries are properly altered by the node module.
 *
 * @group node
 */
class NodeQueryAlterTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node_access_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User with permission to view content.
   */
  protected $accessUser;

  /**
   * User without permission to view content.
   */
  protected $noAccessUser;

  /**
   * User without permission to view content.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $noAccessUser2;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    node_access_rebuild();

    // Create some content.
    $this->drupalCreateNode();
    $this->drupalCreateNode();
    $this->drupalCreateNode();
    $this->drupalCreateNode();

    // Create user with simple node access permission. The 'node test view'
    // permission is implemented and granted by the node_access_test module.
    $this->accessUser = $this->drupalCreateUser([
      'access content overview',
      'access content',
      'node test view',
    ]);
    $this->noAccessUser = $this->drupalCreateUser([
      'access content overview',
      'access content',
    ]);
    $this->noAccessUser2 = $this->drupalCreateUser([
      'access content overview',
      'access content',
    ]);
  }

  /**
   * Tests 'node_access' query alter, for user with access.
   *
   * Verifies that a non-standard table alias can be used, and that a user with
   * node access can view the nodes.
   */
  public function testNodeQueryAlterLowLevelWithAccess() {
    // User with access should be able to view 4 nodes.
    try {
      $query = Database::getConnection()->select('node', 'n')
        ->fields('n');
      $query->addTag('node_access');
      $query->addMetaData('op', 'view');
      $query->addMetaData('account', $this->accessUser);

      $result = $query->execute()->fetchAll();
      $this->assertCount(4, $result, 'User with access can see correct nodes');
    }
    catch (\Exception $e) {
      $this->fail('Altered query is malformed');
    }
  }

  /**
   * Tests 'node_access' query alter with revision-enabled nodes.
   */
  public function testNodeQueryAlterWithRevisions() {
    // Execute a query that only deals with the 'node_revision' table.
    try {
      $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
      $result = $query
        ->accessCheck(TRUE)
        ->allRevisions()
        ->execute();

      $this->assertCount(4, $result, 'User with access can see correct nodes');
    }
    catch (\Exception $e) {
      $this->fail('Altered query is malformed');
    }
  }

  /**
   * Tests 'node_access' query alter, for user without access.
   *
   * Verifies that a non-standard table alias can be used, and that a user
   * without node access cannot view the nodes.
   */
  public function testNodeQueryAlterLowLevelNoAccess() {
    // User without access should be able to view 0 nodes.
    try {
      $query = Database::getConnection()->select('node', 'n')
        ->fields('n');
      $query->addTag('node_access');
      $query->addMetaData('op', 'view');
      $query->addMetaData('account', $this->noAccessUser);

      $result = $query->execute()->fetchAll();
      $this->assertCount(0, $result, 'User with no access cannot see nodes');
    }
    catch (\Exception $e) {
      $this->fail('Altered query is malformed');
    }
  }

  /**
   * Tests 'node_access' query alter, for edit access.
   *
   * Verifies that a non-standard table alias can be used, and that a user with
   * view-only node access cannot edit the nodes.
   */
  public function testNodeQueryAlterLowLevelEditAccess() {
    // User with view-only access should not be able to edit nodes.
    try {
      $query = Database::getConnection()->select('node', 'n')
        ->fields('n');
      $query->addTag('node_access');
      $query->addMetaData('op', 'update');
      $query->addMetaData('account', $this->accessUser);

      $result = $query->execute()->fetchAll();
      $this->assertCount(0, $result, 'User with view-only access cannot edit nodes');
    }
    catch (\Exception $e) {
      $this->fail($e->getMessage());
      $this->fail((string) $query);
      $this->fail('Altered query is malformed');
    }
  }

  /**
   * Tests 'node_access' query alter override.
   *
   * Verifies that node_access_view_all_nodes() is called from
   * node_query_node_access_alter(). We do this by checking that a user who
   * normally would not have view privileges is able to view the nodes when we
   * add a record to {node_access} paired with a corresponding privilege in
   * hook_node_grants().
   */
  public function testNodeQueryAlterOverride() {
    $record = [
      'nid' => 0,
      'gid' => 0,
      'realm' => 'node_access_all',
      'grant_view' => 1,
      'grant_update' => 0,
      'grant_delete' => 0,
    ];
    $connection = Database::getConnection();
    $connection->insert('node_access')->fields($record)->execute();

    // Test that the noAccessUser still doesn't have the 'view'
    // privilege after adding the node_access record.
    drupal_static_reset('node_access_view_all_nodes');
    try {
      $query = $connection->select('node', 'n')
        ->fields('n');
      $query->addTag('node_access');
      $query->addMetaData('op', 'view');
      $query->addMetaData('account', $this->noAccessUser);

      $result = $query->execute()->fetchAll();
      $this->assertCount(0, $result, 'User view privileges are not overridden');
    }
    catch (\Exception $e) {
      $this->fail('Altered query is malformed');
    }

    // Have node_test_node_grants return a node_access_all privilege,
    // to grant the noAccessUser 'view' access.  To verify that
    // node_access_view_all_nodes is properly checking the specified
    // $account instead of the current user, we will log in as
    // noAccessUser2.
    $this->drupalLogin($this->noAccessUser2);
    \Drupal::state()->set('node_access_test.no_access_uid', $this->noAccessUser->id());
    drupal_static_reset('node_access_view_all_nodes');
    try {
      $query = $connection->select('node', 'n')
        ->fields('n');
      $query->addTag('node_access');
      $query->addMetaData('op', 'view');
      $query->addMetaData('account', $this->noAccessUser);

      $result = $query->execute()->fetchAll();
      $this->assertCount(4, $result, 'User view privileges are overridden');
    }
    catch (\Exception $e) {
      $this->fail('Altered query is malformed');
    }
    \Drupal::state()->delete('node_access_test.no_access_uid');
  }

}
