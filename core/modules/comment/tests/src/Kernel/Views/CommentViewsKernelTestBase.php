<?php

namespace Drupal\Tests\comment\Kernel\Views;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\views\Tests\ViewTestData;

/**
 * Provides a common test base for comment views tests.
 */
abstract class CommentViewsKernelTestBase extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['comment_test_views', 'user', 'comment'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The entity storage for comments.
   *
   * @var \Drupal\comment\CommentStorageInterface
   */
  protected $commentStorage;

  /**
   * The entity storage for users.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    ViewTestData::createTestViews(static::class, ['comment_test_views']);

    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installConfig(['user']);

    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->commentStorage = $entity_type_manager->getStorage('comment');
    $this->userStorage = $entity_type_manager->getStorage('user');

    // Insert a row for the anonymous user.
    $this->userStorage
      ->create([
        'uid' => 0,
        'name' => '',
        'status' => 0,
      ])
      ->save();

    // Create user 1 so that the user created later in the test has a different
    // user ID.
    // @todo Remove in https://www.drupal.org/node/540008.
    $this->userStorage->create(['uid' => 1, 'name' => 'user1'])->save();

    $admin_role = Role::create(['id' => 'admin', 'label' => 'Admin']);
    $admin_role->grantPermission('administer comments');
    $admin_role->grantPermission('access comments');
    $admin_role->grantPermission('post comments');
    $admin_role->grantPermission('view test entity');
    $admin_role->save();

    /** @var \Drupal\user\RoleInterface $anonymous_role */
    $anonymous_role = Role::load(Role::ANONYMOUS_ID);
    $anonymous_role->grantPermission('access comments');
    $anonymous_role->save();

    $this->adminUser = $this->userStorage->create(['name' => $this->randomMachineName()]);
    $this->adminUser->addRole('admin');
    $this->adminUser->save();
  }

}
