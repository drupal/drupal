<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\RelationshipRepresentativeNode.
 */

namespace Drupal\user\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the representative node relationship for users.
 */
class RelationshipRepresentativeNode extends UserTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_groupwise_user');

  public static function getInfo() {
    return array(
      'name' => 'User: Representative Node Relationship',
      'description' => 'Tests the representative node relationship for users.',
      'group' => 'Views module integration',
    );
  }

  /**
   * Tests the relationship.
   */
  public function testRelationship() {
    $view = Views::getView('test_groupwise_user');
    $this->executeView($view);
    $map = array('node_users_nid' => 'nid', 'uid' => 'uid');
    $expected_result = array(
      array(
        'uid' => $this->users[1]->id(),
        'nid' => $this->nodes[1]->id(),
      ),
      array(
        'uid' => $this->users[0]->id(),
        'nid' => $this->nodes[0]->id(),
      ),
    );
    $this->assertIdenticalResultset($view, $expected_result, $map);
  }

}
