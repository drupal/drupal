<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Tests\Traits\Core\GeneratePermutationsTrait;

/**
 * Tests user permissions for node revisions.
 *
 * @group node
 * @group legacy
 */
class NodeRevisionPermissionsTest extends NodeTestBase {

  use GeneratePermutationsTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The node revisions.
   *
   * @var array
   */
  protected $nodeRevisions = [];

  /**
   * The accounts.
   *
   * @var array
   */
  protected $accounts = [];

  /**
   * Map revision permission names to node revision access ops.
   *
   * @var array
   */
  protected $map = [
    'view' => 'view all revisions',
    'update' => 'revert all revisions',
    'delete' => 'delete all revisions',
  ];

  /**
   * Map revision permission names to node type revision access ops.
   *
   * @var array
   */
  protected $typeMap = [
    'view' => 'view page revisions',
    'update' => 'revert page revisions',
    'delete' => 'delete page revisions',
  ];

  protected function setUp(): void {
    parent::setUp();

    $types = ['page', 'article'];

    foreach ($types as $type) {
      // Create a node with several revisions.
      $nodes[$type] = $this->drupalCreateNode(['type' => $type]);
      $this->nodeRevisions[$type][] = $nodes[$type];

      for ($i = 0; $i < 3; $i++) {
        // Create a revision for the same nid and settings with a random log.
        $revision = clone $nodes[$type];
        $revision->setNewRevision();
        $revision->revision_log = $this->randomMachineName(32);
        $revision->save();
        $this->nodeRevisions[$type][] = $revision;
      }
    }
  }

  /**
   * Tests general revision access permissions.
   */
  public function testNodeRevisionAccessAnyType() {
    $this->expectDeprecation('NodeRevisionAccessCheck is deprecated in drupal:9.3.0 and will be removed before drupal:10.0.0. Use "_entity_access" requirement with relevant operation instead. See https://www.drupal.org/node/3161210');
    // Create three users, one with each revision permission.
    foreach ($this->map as $op => $permission) {
      // Create the user.
      $account = $this->drupalCreateUser(
        [
          'access content',
          'edit any page content',
          'delete any page content',
          $permission,
        ]
      );
      $account->op = $op;
      $this->accounts[] = $account;
    }

    // Create an admin account (returns TRUE for all revision permissions).
    $admin_account = $this->drupalCreateUser([
      'access content',
      'administer nodes',
    ]);
    $admin_account->is_admin = TRUE;
    $this->accounts['admin'] = $admin_account;
    $accounts['admin'] = $admin_account;

    // Create a normal account (returns FALSE for all revision permissions).
    $normal_account = $this->drupalCreateUser();
    $normal_account->op = FALSE;
    $this->accounts[] = $normal_account;
    $accounts[] = $normal_account;
    $revision = $this->nodeRevisions['page'][1];

    $parameters = [
      'op' => array_keys($this->map),
      'account' => $this->accounts,
    ];

    $permutations = $this->generatePermutations($parameters);

    $node_revision_access = \Drupal::service('access_check.node.revision');
    $vids = \Drupal::entityQuery('node')
      ->allRevisions()
      ->accessCheck(FALSE)
      ->condition('nid', $revision->id())
      ->execute();
    foreach ($permutations as $case) {
      // Skip this test if there are no revisions for the node.
      if (!($revision->isDefaultRevision() && (count($vids) == 1 || $case['op'] == 'update' || $case['op'] == 'delete'))) {
        if (!empty($case['account']->is_admin) || $case['account']->hasPermission($this->map[$case['op']])) {
          $this->assertTrue($node_revision_access->checkAccess($revision, $case['account'], $case['op']), "{$this->map[$case['op']]} granted.");
        }
        else {
          $this->assertFalse($node_revision_access->checkAccess($revision, $case['account'], $case['op']), "{$this->map[$case['op']]} not granted.");
        }
      }
    }

    // Test that access is FALSE for a node administrator with an invalid $node
    // or $op parameters.
    $admin_account = $accounts['admin'];
    $this->assertFalse($node_revision_access->checkAccess($revision, $admin_account, 'invalid-op'), 'NodeRevisionAccessCheck() returns FALSE with an invalid op.');
  }

  /**
   * Tests revision access permissions for a specific content type.
   */
  public function testNodeRevisionAccessPerType() {
    $this->expectDeprecation('NodeRevisionAccessCheck is deprecated in drupal:9.3.0 and will be removed before drupal:10.0.0. Use "_entity_access" requirement with relevant operation instead. See https://www.drupal.org/node/3161210');
    // Create three users, one with each revision permission.
    foreach ($this->typeMap as $op => $permission) {
      // Create the user.
      $account = $this->drupalCreateUser(
        [
          'access content',
          'edit any page content',
          'delete any page content',
          $permission,
        ]
      );
      $account->op = $op;
      $accounts[] = $account;
    }

    $parameters = [
      'op' => array_keys($this->typeMap),
      'account' => $accounts,
    ];

    // Test that the accounts have access to the corresponding page revision
    // permissions.
    $revision = $this->nodeRevisions['page'][1];

    $permutations = $this->generatePermutations($parameters);
    $node_revision_access = \Drupal::service('access_check.node.revision');
    $vids = \Drupal::entityQuery('node')
      ->allRevisions()
      ->accessCheck(FALSE)
      ->condition('nid', $revision->id())
      ->execute();
    foreach ($permutations as $case) {
      // Skip this test if there are no revisions for the node.
      if (!($revision->isDefaultRevision() && (count($vids) == 1 || $case['op'] == 'update' || $case['op'] == 'delete'))) {
        if (!empty($case['account']->is_admin) || $case['account']->hasPermission($this->typeMap[$case['op']])) {
          $this->assertTrue($node_revision_access->checkAccess($revision, $case['account'], $case['op']), "{$this->typeMap[$case['op']]} granted.");
        }
        else {
          $this->assertFalse($node_revision_access->checkAccess($revision, $case['account'], $case['op']), "{$this->typeMap[$case['op']]} not granted.");
        }
      }
    }

    // Test that the accounts have no access to the article revisions.
    $revision = $this->nodeRevisions['article'][1];

    foreach ($permutations as $case) {
      $this->assertFalse($node_revision_access->checkAccess($revision, $case['account'], $case['op']), "{$this->typeMap[$case['op']]} did not grant revision permission for articles.");
    }
  }

}
