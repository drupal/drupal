<?php

/**
 * @file
 * Definition of Drupal\views\Tests\User\RelationshipRepresentativeNode.
 */

namespace Drupal\views\Tests\User;

/**
 * Tests the representative node relationship for users.
 */
class RelationshipRepresentativeNode extends UserTestBase {

  public static function getInfo() {
    return array(
      'name' => 'User: Representative Node Relationship',
      'description' => 'Tests the representative node relationship for users.',
      'group' => 'Views Modules',
    );
  }

  /**
   * Tests the relationship.
   */
  public function testRelationship() {
    $view = views_get_view('test_groupwise_user');
    $this->executeView($view);
    $map = array('node_users_nid' => 'nid', 'uid' => 'uid');
    $expected_result = array(
      array(
        'uid' => $this->users[1]->uid,
        'nid' => $this->nodes[1]->nid,
      ),
      array(
        'uid' => $this->users[0]->uid,
        'nid' => $this->nodes[0]->nid,
      ),
    );
    $this->assertIdenticalResultset($view, $expected_result, $map);
  }

}
