<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeRevisionPermissionsTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests user permissions for node revisions.
 */
class NodeRevisionPermissionsTest extends NodeTestBase {
  protected $node_revisions = array();
  protected $accounts = array();

  // Map revision permission names to node revision access ops.
  protected $map = array(
    'view' => 'view revisions',
    'update' => 'revert revisions',
    'delete' => 'delete revisions',
  );

  public static function getInfo() {
    return array(
      'name' => 'Node revision permissions',
      'description' => 'Tests user permissions for node revision operations.',
      'group' => 'Node',
    );
  }

  function setUp() {
    parent::setUp();

    // Create a node with several revisions.
    $node = $this->drupalCreateNode();
    $this->node_revisions[] = $node;

    for ($i = 0; $i < 3; $i++) {
      // Create a revision for the same nid and settings with a random log.
      $revision = clone $node;
      $revision->revision = 1;
      $revision->log = $this->randomName(32);
      node_save($revision);
      $this->node_revisions[] = $revision;
    }

    // Create three users, one with each revision permission.
    foreach ($this->map as $op => $permission) {
      // Create the user.
      $account = $this->drupalCreateUser(
        array(
          'access content',
          'edit any page content',
          'delete any page content',
          $permission,
        )
      );
      $account->op = $op;
      $this->accounts[] = $account;
    }

    // Create an admin account (returns TRUE for all revision permissions).
    $admin_account = $this->drupalCreateUser(array('access content', 'administer nodes'));
    $admin_account->is_admin = TRUE;
    $this->accounts['admin'] = $admin_account;

    // Create a normal account (returns FALSE for all revision permissions).
    $normal_account = $this->drupalCreateUser();
    $normal_account->op = FALSE;
    $this->accounts[] = $normal_account;
  }

  /**
   * Tests the _node_revision_access() function.
   */
  function testNodeRevisionAccess() {
    $revision = $this->node_revisions[1];

    $parameters = array(
      'op' => array_keys($this->map),
      'account' => $this->accounts,
    );

    $permutations = $this->generatePermutations($parameters);
    foreach ($permutations as $case) {
      if (!empty($case['account']->is_admin) || $case['op'] == $case['account']->op) {
        $this->assertTrue(_node_revision_access($revision, $case['op'], $case['account']), "{$this->map[$case['op']]} granted.");
      }
      else {
        $this->assertFalse(_node_revision_access($revision, $case['op'], $case['account']), "{$this->map[$case['op']]} not granted.");
      }
    }

    // Test that access is FALSE for a node administrator with an invalid $node
    // or $op parameters.
    $admin_account = $this->accounts['admin'];
    $this->assertFalse(_node_revision_access($revision, 'invalid-op', $admin_account), '_node_revision_access() returns FALSE with an invalid op.');

    // Test that the $account parameter defaults to the "logged in" user.
    $original_user = $GLOBALS['user'];
    $GLOBALS['user'] = $admin_account;
    $this->assertTrue(_node_revision_access($revision, 'view'), '_node_revision_access() returns TRUE when used with global user.');
    $GLOBALS['user'] = $original_user;
  }
}
