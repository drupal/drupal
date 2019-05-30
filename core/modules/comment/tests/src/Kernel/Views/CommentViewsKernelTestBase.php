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
  public static $modules = ['comment_test_views', 'user', 'comment'];

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

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    ViewTestData::createTestViews(get_class($this), ['comment_test_views']);

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

    $admin_role = Role::create(['id' => 'admin']);
    $admin_role->grantPermission('administer comments');
    $admin_role->save();

    /* @var \Drupal\user\RoleInterface $anonymous_role */
    $anonymous_role = Role::load(Role::ANONYMOUS_ID);
    $anonymous_role->grantPermission('access comments');
    $anonymous_role->save();

    $this->adminUser = $this->userStorage->create(['name' => $this->randomMachineName()]);
    $this->adminUser->addRole('admin');
    $this->adminUser->save();
  }

}
