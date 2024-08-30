<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Database\Database;
use Drupal\user\Entity\User;

/**
 * Tests the node access grants cache context service.
 *
 * @group node
 * @group Cache
 */
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
      1 => $this->adminUser,
      2 => $this->accessUser,
      3 => $this->noAccessUser,
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
      }
      $this->assertSame($context, $this->container->get('cache_context.user.node_grants')->getContext('view'));
    }
    $this->drupalLogout();
  }

  /**
   * Tests NodeAccessGrantsCacheContext::getContext().
   */
  public function testCacheContext(): void {
    $this->assertUserCacheContext([
      0 => 'view.all:0;node_access_test_author:0;node_access_all:0',
      1 => 'all',
      2 => 'view.all:0;node_access_test_author:2;node_access_test:8888,8889',
      3 => 'view.all:0;node_access_test_author:3',
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
    drupal_static_reset('node_access_view_all_nodes');
    $this->assertUserCacheContext([
      0 => 'view.all',
      1 => 'all',
      2 => 'view.all:0;node_access_test_author:2;node_access_test:8888,8889',
      3 => 'view.all:0;node_access_test_author:3',
    ]);

    // Put user accessUser (uid 2) in the realm.
    \Drupal::state()->set('node_access_test.no_access_uid', $this->accessUser->id());
    drupal_static_reset('node_access_view_all_nodes');
    $this->assertUserCacheContext([
      0 => 'view.all:0;node_access_test_author:0',
      1 => 'all',
      2 => 'view.all',
      3 => 'view.all:0;node_access_test_author:3',
    ]);

    // Put user noAccessUser (uid 3) in the realm.
    \Drupal::state()->set('node_access_test.no_access_uid', $this->noAccessUser->id());
    drupal_static_reset('node_access_view_all_nodes');
    $this->assertUserCacheContext([
      0 => 'view.all:0;node_access_test_author:0',
      1 => 'all',
      2 => 'view.all:0;node_access_test_author:2;node_access_test:8888,8889',
      3 => 'view.all',
    ]);

    // Uninstall the node_access_test module
    $this->container->get('module_installer')->uninstall(['node_access_test']);
    drupal_static_reset('node_access_view_all_nodes');
    $this->assertUserCacheContext([
      0 => 'view.all',
      1 => 'all',
      2 => 'view.all',
      3 => 'view.all',
    ]);
  }

}
