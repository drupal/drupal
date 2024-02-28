<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Functional;

/**
 * Trait with helpers for Media functional tests.
 */
trait MediaFunctionalTestTrait {

  /**
   * Permissions for the admin user that will be logged-in for test.
   *
   * @var array
   */
  protected static $adminUserPermissions = [
    // Media module permissions.
    'access media overview',
    'administer media',
    'administer media fields',
    'administer media form display',
    'administer media display',
    'administer media types',
    'view media',
    // Other permissions.
    'administer views',
    'access content overview',
    'view all revisions',
    'administer content types',
    'administer node fields',
    'administer node form display',
    'administer node display',
    'bypass node access',
  ];

  /**
   * An admin test user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A non-admin test user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $nonAdminUser;

  /**
   * The storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Have two users ready to be used in tests.
    $this->adminUser = $this->drupalCreateUser(static::$adminUserPermissions);
    $this->nonAdminUser = $this->drupalCreateUser([]);
    // Start off logged in as admin.
    $this->drupalLogin($this->adminUser);

    $this->storage = $this->container->get('entity_type.manager')->getStorage('media');
  }

}
