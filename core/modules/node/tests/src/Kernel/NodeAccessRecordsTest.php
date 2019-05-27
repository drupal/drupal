<?php

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
  public static $modules = ['node_test'];

  /**
   * Creates a node and tests the creation of node access rules.
   */
  public function testNodeAccessRecords() {
    // Create an article node.
    $node1 = $this->drupalCreateNode(['type' => 'article']);
    $this->assertTrue(Node::load($node1->id()), 'Article node created.');

    // Check to see if grants added by node_test_node_access_records made it in.
    $connection = Database::getConnection();
    $records = $connection->query('SELECT realm, gid FROM {node_access} WHERE nid = :nid', [':nid' => $node1->id()])->fetchAll();
    $this->assertEqual(count($records), 1, 'Returned the correct number of rows.');
    $this->assertEqual($records[0]->realm, 'test_article_realm', 'Grant with article_realm acquired for node without alteration.');
    $this->assertEqual($records[0]->gid, 1, 'Grant with gid = 1 acquired for node without alteration.');

    // Create an unpromoted "Basic page" node.
    $node2 = $this->drupalCreateNode(['type' => 'page', 'promote' => 0]);
    $this->assertTrue(Node::load($node2->id()), 'Unpromoted basic page node created.');

    // Check to see if grants added by node_test_node_access_records made it in.
    $records = $connection->query('SELECT realm, gid FROM {node_access} WHERE nid = :nid', [':nid' => $node2->id()])->fetchAll();
    $this->assertEqual(count($records), 1, 'Returned the correct number of rows.');
    $this->assertEqual($records[0]->realm, 'test_page_realm', 'Grant with page_realm acquired for node without alteration.');
    $this->assertEqual($records[0]->gid, 1, 'Grant with gid = 1 acquired for node without alteration.');

    // Create an unpromoted, unpublished "Basic page" node.
    $node3 = $this->drupalCreateNode(['type' => 'page', 'promote' => 0, 'status' => 0]);
    $this->assertTrue(Node::load($node3->id()), 'Unpromoted, unpublished basic page node created.');

    // Check to see if grants added by node_test_node_access_records made it in.
    $records = $connection->query('SELECT realm, gid FROM {node_access} WHERE nid = :nid', [':nid' => $node3->id()])->fetchAll();
    $this->assertEqual(count($records), 1, 'Returned the correct number of rows.');
    $this->assertEqual($records[0]->realm, 'test_page_realm', 'Grant with page_realm acquired for node without alteration.');
    $this->assertEqual($records[0]->gid, 1, 'Grant with gid = 1 acquired for node without alteration.');

    // Create a promoted "Basic page" node.
    $node4 = $this->drupalCreateNode(['type' => 'page', 'promote' => 1]);
    $this->assertTrue(Node::load($node4->id()), 'Promoted basic page node created.');

    // Check to see if grant added by node_test_node_access_records was altered
    // by node_test_node_access_records_alter.
    $records = $connection->query('SELECT realm, gid FROM {node_access} WHERE nid = :nid', [':nid' => $node4->id()])->fetchAll();
    $this->assertEqual(count($records), 1, 'Returned the correct number of rows.');
    $this->assertEqual($records[0]->realm, 'test_alter_realm', 'Altered grant with alter_realm acquired for node.');
    $this->assertEqual($records[0]->gid, 2, 'Altered grant with gid = 2 acquired for node.');

    // Check to see if we can alter grants with hook_node_grants_alter().
    $operations = ['view', 'update', 'delete'];
    // Create a user that is allowed to access content.
    $web_user = $this->drupalCreateUser(['access content']);
    foreach ($operations as $op) {
      $grants = node_test_node_grants($web_user, $op);
      $altered_grants = $grants;
      \Drupal::moduleHandler()->alter('node_grants', $altered_grants, $web_user, $op);
      $this->assertNotEqual($grants, $altered_grants, format_string('Altered the %op grant for a user.', ['%op' => $op]));
    }

    // Check that core does not grant access to an unpublished node when an
    // empty $grants array is returned.
    $node6 = $this->drupalCreateNode(['status' => 0, 'disable_node_access' => TRUE]);
    $records = $connection->query('SELECT realm, gid FROM {node_access} WHERE nid = :nid', [':nid' => $node6->id()])->fetchAll();
    $this->assertEqual(count($records), 0, 'Returned no records for unpublished node.');
  }

}
