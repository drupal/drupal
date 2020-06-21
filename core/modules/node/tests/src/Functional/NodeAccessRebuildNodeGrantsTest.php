<?php

namespace Drupal\Tests\node\Functional;

use Drupal\node\Entity\NodeType;

/**
 * Ensures that node access rebuild functions work correctly even
 * when other modules implements hook_node_grants().
 *
 * @group node
 */
class NodeAccessRebuildNodeGrantsTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user to create nodes that only it has access to.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * A user to test the rebuild nodes feature which can't access the nodes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'access site reports',
    ]);
    $this->drupalLogin($this->adminUser);

    $this->webUser = $this->drupalCreateUser();
  }

  /**
   * Tests rebuilding the node access permissions table with content.
   */
  public function testNodeAccessRebuildNodeGrants() {
    \Drupal::service('module_installer')->install(['node_access_test']);
    \Drupal::state()->set('node_access_test.private', TRUE);
    node_access_test_add_field(NodeType::load('page'));
    $this->resetAll();

    // Create 30 nodes so that _node_access_rebuild_batch_operation() has to run
    // more than once.
    for ($i = 0; $i < 30; $i++) {
      $nodes[] = $this->drupalCreateNode([
        'uid' => $this->webUser->id(),
        'private' => [['value' => 1]],
      ]);
    }

    /** @var \Drupal\node\NodeGrantDatabaseStorageInterface $grant_storage */
    $grant_storage = \Drupal::service('node.grant_storage');
    // Default realm access and node records are present.
    foreach ($nodes as $node) {
      $this->assertNotEmpty($node->private->value);
      $this->assertTrue($grant_storage->access($node, 'view', $this->webUser)->isAllowed(), 'Prior to rebuilding node access the grant storage returns allowed for the node author.');
      $this->assertTrue($grant_storage->access($node, 'view', $this->adminUser)->isAllowed(), 'Prior to rebuilding node access the grant storage returns allowed for the admin user.');
    }

    $this->assertEqual(1, \Drupal::service('node.grant_storage')->checkAll($this->webUser), 'There is an all realm access record');
    $this->assertTrue(\Drupal::state()->get('node.node_access_needs_rebuild'), 'Node access permissions need to be rebuilt');

    // Rebuild permissions.
    $this->drupalGet('admin/reports/status');
    $this->clickLink(t('Rebuild permissions'));
    $this->drupalPostForm(NULL, [], t('Rebuild permissions'));
    $this->assertText(t('The content access permissions have been rebuilt.'));

    // Test if the rebuild by user that cannot bypass node access and does not
    // have access to the nodes has been successful.
    $this->assertFalse($this->adminUser->hasPermission('bypass node access'));
    $this->assertNull(\Drupal::state()->get('node.node_access_needs_rebuild'), 'Node access permissions have been rebuilt');
    foreach ($nodes as $node) {
      $this->assertTrue($grant_storage->access($node, 'view', $this->webUser)->isAllowed(), 'After rebuilding node access the grant storage returns allowed for the node author.');
      $this->assertFalse($grant_storage->access($node, 'view', $this->adminUser)->isForbidden(), 'After rebuilding node access the grant storage returns forbidden for the admin user.');
    }
    $this->assertEmpty(\Drupal::service('node.grant_storage')->checkAll($this->webUser), 'There is no all realm access record');

    // Test an anonymous node access rebuild from code.
    $this->drupalLogout();
    node_access_rebuild();
    foreach ($nodes as $node) {
      $this->assertTrue($grant_storage->access($node, 'view', $this->webUser)->isAllowed(), 'After rebuilding node access the grant storage returns allowed for the node author.');
      $this->assertFalse($grant_storage->access($node, 'view', $this->adminUser)->isForbidden(), 'After rebuilding node access the grant storage returns forbidden for the admin user.');
    }
    $this->assertEmpty(\Drupal::service('node.grant_storage')->checkAll($this->webUser), 'There is no all realm access record');
  }

  /**
   * Tests rebuilding the node access permissions table with no content.
   */
  public function testNodeAccessRebuildNoAccessModules() {
    // Default realm access is present.
    $this->assertEqual(1, \Drupal::service('node.grant_storage')->count(), 'There is an all realm access record');

    // No need to rebuild permissions.
    $this->assertNull(\Drupal::state()->get('node.node_access_needs_rebuild'), 'Node access permissions need to be rebuilt');

    // Rebuild permissions.
    $this->drupalGet('admin/reports/status');
    $this->clickLink(t('Rebuild permissions'));
    $this->drupalPostForm(NULL, [], t('Rebuild permissions'));
    $this->assertText(t('Content permissions have been rebuilt.'));
    $this->assertNull(\Drupal::state()->get('node.node_access_needs_rebuild'), 'Node access permissions have been rebuilt');

    // Default realm access is still present.
    $this->assertEqual(1, \Drupal::service('node.grant_storage')->count(), 'There is an all realm access record');
  }

}
