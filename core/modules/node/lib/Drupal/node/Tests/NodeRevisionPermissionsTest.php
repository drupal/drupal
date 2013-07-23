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
    'view' => 'view all revisions',
    'update' => 'revert all revisions',
    'delete' => 'delete all revisions',
  );

  // Map revision permission names to node type revision access ops.
  protected $type_map = array(
    'view' => 'view page revisions',
    'update' => 'revert page revisions',
    'delete' => 'delete page revisions',
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

    $types = array('page', 'article');

    foreach ($types as $type) {
      // Create a node with several revisions.
      $nodes[$type] = $this->drupalCreateNode(array('type' => $type));
      $this->node_revisions[$type][] = $nodes[$type];

      for ($i = 0; $i < 3; $i++) {
        // Create a revision for the same nid and settings with a random log.
        $revision = clone $nodes[$type];
        $revision->setNewRevision();
        $revision->log = $this->randomName(32);
        $revision->save();
        $this->node_revisions[$type][] = $revision;
      }
    }
  }

  /**
   * Tests general revision access permissions.
   */
  function testNodeRevisionAccessAnyType() {
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
    $accounts['admin'] = $admin_account;

    // Create a normal account (returns FALSE for all revision permissions).
    $normal_account = $this->drupalCreateUser();
    $normal_account->op = FALSE;
    $this->accounts[] = $normal_account;
    $accounts[] = $normal_account;
    $revision = $this->node_revisions['page'][1];

    $parameters = array(
      'op' => array_keys($this->map),
      'account' => $this->accounts,
    );

    $permutations = $this->generatePermutations($parameters);

    foreach ($permutations as $case) {
      // Skip this test if there are no revisions for the node.
      if (!($revision->isDefaultRevision() && (db_query('SELECT COUNT(vid) FROM {node_field_revision} WHERE nid = :nid', array(':nid' => $revision->id()))->fetchField() == 1 || $case['op'] == 'update' || $case['op'] == 'delete'))) {
        if (!empty($case['account']->is_admin) || user_access($this->map[$case['op']], $case['account'])) {
          $this->assertTrue(_node_revision_access($revision, $case['op'], $case['account']), "{$this->map[$case['op']]} granted.");
        }
        else {
          $this->assertFalse(_node_revision_access($revision, $case['op'], $case['account']), "{$this->map[$case['op']]} not granted.");
        }
      }
    }

    // Test that access is FALSE for a node administrator with an invalid $node
    // or $op parameters.
    $admin_account = $accounts['admin'];
    $this->assertFalse(_node_revision_access($revision, 'invalid-op', $admin_account), '_node_revision_access() returns FALSE with an invalid op.');
  }

  /**
   * Tests revision access permissions for a specific content type.
   */
  function testNodeRevisionAccessPerType() {
    // Create three users, one with each revision permission.
    foreach ($this->type_map as $op => $permission) {
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
      $accounts[] = $account;
    }

    $parameters = array(
      'op' => array_keys($this->type_map),
      'account' => $accounts,
    );

    // Test that the accounts have access to the correspoding page revision permissions.
    $revision = $this->node_revisions['page'][1];

    $permutations = $this->generatePermutations($parameters);
    foreach ($permutations as $case) {
      // Skip this test if there are no revisions for the node.
      if (!($revision->isDefaultRevision() && (db_query('SELECT COUNT(vid) FROM {node_field_revision} WHERE nid = :nid', array(':nid' => $revision->id()))->fetchField() == 1 || $case['op'] == 'update' || $case['op'] == 'delete'))) {
        if (!empty($case['account']->is_admin) || user_access($this->type_map[$case['op']], $case['account'])) {
          $this->assertTrue(_node_revision_access($revision, $case['op'], $case['account']), "{$this->type_map[$case['op']]} granted.");
        }
        else {
          $this->assertFalse(_node_revision_access($revision, $case['op'], $case['account']), "{$this->type_map[$case['op']]} not granted.");
        }
      }
    }

    // Test that the accounts have no access to the article revisions.
    $revision = $this->node_revisions['article'][1];

    foreach ($permutations as $case) {
      $this->assertFalse(_node_revision_access($revision, $case['op'], $case['account']), "{$this->type_map[$case['op']]} did not grant revision permission for articles.");
    }
  }
}
