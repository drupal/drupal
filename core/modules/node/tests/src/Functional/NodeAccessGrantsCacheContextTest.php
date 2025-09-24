<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Database\Database;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the node access grants cache context service.
 */
#[Group('node')]
#[Group('Cache')]
#[RunTestsInSeparateProcesses]
class NodeAccessGrantsCacheContextTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node_access_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User with permission to view content.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $accessUser;

  /**
   * User without permission to view content.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $noAccessUser;

  /**
   * User without permission to view content.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $noAccessUser2;

  /**
   * User with permission to bypass node access.
   *
   * @var \Drupal\user\Entity\User|false
   *
   * @see \Drupal\Tests\user\Traits\UserCreationTrait::createUser
   */
  protected $adminUser;

  /**
   * @var array
   */
  protected array $userMapping;

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
    $this->adminUser = $this->drupalCreateUser([
      'bypass node access',
    ]);

    $this->userMapping = [
      $this->adminUser->id() => $this->adminUser,
      $this->accessUser->id() => $this->accessUser,
      $this->noAccessUser->id() => $this->noAccessUser,
    ];
  }

  /**
   * Asserts that for each given user, the expected cache context is returned.
   *
   * @param array $expected
   *   Expected values, keyed by user ID, expected cache contexts as values.
   *
   * @internal
   */
  protected function assertUserCacheContext(array $expected): void {
    foreach ($expected as $uid => $context) {
      if ($uid > 0) {
        $this->drupalLogin($this->userMapping[$uid]);
        // Also set the current user in the singleton service. ::drupalLogin
        // sets it using $this->container which isn't compatible with the other
        // ::service calls in this test.
        \Drupal::currentUser()->setAccount($this->userMapping[$uid]);
      }
      $this->assertSame($context, \Drupal::service('cache_context.user.node_grants')->getContext('view'));
    }
    $this->drupalLogout();
  }

  /**
   * Tests NodeAccessGrantsCacheContext::getContext().
   */
  public function testCacheContext(): void {
    $this->assertUserCacheContext([
      0 => 'view.all:0;node_access_test_author:0;node_access_all:0',
      $this->adminUser->id() => 'all',
      $this->accessUser->id() => 'view.all:0;node_access_test_author:2;node_access_test:8888,8889',
      $this->noAccessUser->id() => 'view.all:0;node_access_test_author:3',
    ]);

    // Grant view to all nodes (because nid = 0) for users in the
    // 'node_access_all' realm.
    $record = [
      'nid' => 0,
      'gid' => 0,
      'realm' => 'node_access_all',
      'grant_view' => 1,
      'grant_update' => 0,
      'grant_delete' => 0,
    ];
    Database::getConnection()->insert('node_access')->fields($record)->execute();

    // Put user accessUser (uid 0) in the realm.
    \Drupal::state()->set('node_access_test.no_access_uid', 0);
    \Drupal::service('node.view_all_nodes_memory_cache')->deleteAll();
    $this->assertUserCacheContext([
      0 => 'view.all',
      $this->adminUser->id() => 'all',
      $this->accessUser->id() => 'view.all:0;node_access_test_author:2;node_access_test:8888,8889',
      $this->noAccessUser->id() => 'view.all:0;node_access_test_author:3',
    ]);

    // Put user accessUser (uid 2) in the realm.
    \Drupal::state()->set('node_access_test.no_access_uid', $this->accessUser->id());
    \Drupal::service('node.view_all_nodes_memory_cache')->deleteAll();
    $this->assertUserCacheContext([
      0 => 'view.all:0;node_access_test_author:0',
      $this->adminUser->id() => 'all',
      $this->accessUser->id() => 'view.all',
      $this->noAccessUser->id() => 'view.all:0;node_access_test_author:3',
    ]);

    // Put user noAccessUser (uid 3) in the realm.
    \Drupal::state()->set('node_access_test.no_access_uid', $this->noAccessUser->id());
    \Drupal::service('node.view_all_nodes_memory_cache')->deleteAll();
    $this->assertUserCacheContext([
      0 => 'view.all:0;node_access_test_author:0',
      $this->adminUser->id() => 'all',
      $this->accessUser->id() => 'view.all:0;node_access_test_author:2;node_access_test:8888,8889',
      $this->noAccessUser->id() => 'view.all',
    ]);

    // Uninstall the node_access_test module.
    \Drupal::service('module_installer')->uninstall(['node_access_test']);
    $this->assertUserCacheContext([
      0 => 'view.all',
      $this->adminUser->id() => 'all',
      $this->accessUser->id() => 'view.all',
      $this->noAccessUser->id() => 'view.all',
    ]);
  }

}
