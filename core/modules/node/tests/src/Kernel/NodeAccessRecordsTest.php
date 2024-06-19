<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel;

use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;

/**
 * Tests hook_node_access_records when acquiring grants.
 *
 * @group node
 */
class NodeAccessRecordsTest extends NodeAccessTestBase {

  /**
   * Enable a module that implements node access API hooks and alter hook.
   *
   * @var array
   */
  protected static $modules = ['node_test'];

  /**
   * Creates a node and tests the creation of node access rules.
   */
  public function testNodeAccessRecords(): void {
    // Create an article node.
    $node1 = $this->drupalCreateNode(['type' => 'article']);
    $this->assertNotEmpty(Node::load($node1->id()), 'Article node created.');

    // Check to see if grants added by node_test_node_access_records made it in.
    $connection = Database::getConnection();
    $records = $connection->select('node_access', 'na')
      ->fields('na', ['realm', 'gid'])
      ->condition('nid', $node1->id())
      ->execute()
      ->fetchAll();
    $this->assertCount(1, $records, 'Returned the correct number of rows.');
    $this->assertEquals('test_article_realm', $records[0]->realm, 'Grant with article_realm acquired for node without alteration.');
    $this->assertEquals(1, $records[0]->gid, 'Grant with gid = 1 acquired for node without alteration.');

    // Create an un-promoted "Basic page" node.
    $node2 = $this->drupalCreateNode(['type' => 'page', 'promote' => 0]);
    $this->assertNotEmpty(Node::load($node2->id()), 'Un-promoted basic page node created.');

    // Check to see if grants added by node_test_node_access_records made it in.
    $records = $connection->select('node_access', 'na')
      ->fields('na', ['realm', 'gid'])
      ->condition('nid', $node2->id())
      ->execute()
      ->fetchAll();
    $this->assertCount(1, $records, 'Returned the correct number of rows.');
    $this->assertEquals('test_page_realm', $records[0]->realm, 'Grant with page_realm acquired for node without alteration.');
    $this->assertEquals(1, $records[0]->gid, 'Grant with gid = 1 acquired for node without alteration.');

    // Create an un-promoted, unpublished "Basic page" node.
    $node3 = $this->drupalCreateNode(['type' => 'page', 'promote' => 0, 'status' => 0]);
    $this->assertNotEmpty(Node::load($node3->id()), 'Un-promoted, unpublished basic page node created.');

    // Check to see if grants added by node_test_node_access_records made it in.
    $records = $connection->select('node_access', 'na')
      ->fields('na', ['realm', 'gid'])
      ->condition('nid', $node3->id())
      ->execute()
      ->fetchAll();
    $this->assertCount(1, $records, 'Returned the correct number of rows.');
    $this->assertEquals('test_page_realm', $records[0]->realm, 'Grant with page_realm acquired for node without alteration.');
    $this->assertEquals(1, $records[0]->gid, 'Grant with gid = 1 acquired for node without alteration.');

    // Create a promoted "Basic page" node.
    $node4 = $this->drupalCreateNode(['type' => 'page', 'promote' => 1]);
    $this->assertNotEmpty(Node::load($node4->id()), 'Promoted basic page node created.');

    // Check to see if grant added by node_test_node_access_records was altered
    // by node_test_node_access_records_alter.
    $records = $connection->select('node_access', 'na')
      ->fields('na', ['realm', 'gid'])
      ->condition('nid', $node4->id())
      ->execute()
      ->fetchAll();
    $this->assertCount(1, $records, 'Returned the correct number of rows.');
    $this->assertEquals('test_alter_realm', $records[0]->realm, 'Altered grant with alter_realm acquired for node.');
    $this->assertEquals(2, $records[0]->gid, 'Altered grant with gid = 2 acquired for node.');

    // Check to see if we can alter grants with hook_node_grants_alter().
    $operations = ['view', 'update', 'delete'];
    // Create a user that is allowed to access content.
    $web_user = $this->drupalCreateUser(['access content']);
    foreach ($operations as $op) {
      $grants = node_test_node_grants($web_user, $op);
      $altered_grants = $grants;
      \Drupal::moduleHandler()->alter('node_grants', $altered_grants, $web_user, $op);
      $this->assertNotEquals($grants, $altered_grants, "Altered the $op grant for a user.");
    }

    // Check that core does not grant access to an unpublished node when an
    // empty $grants array is returned.
    $node6 = $this->drupalCreateNode(['status' => 0, 'disable_node_access' => TRUE]);
    $records = $connection->select('node_access', 'na')
      ->fields('na', ['realm', 'gid'])
      ->condition('nid', $node6->id())
      ->execute()
      ->fetchAll();
    $this->assertCount(0, $records, 'Returned no records for unpublished node.');
  }

}
