<?php

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
    // Media entity permissions.
    'administer media',
    'administer media fields',
    'administer media form display',
    'administer media display',
    'administer media types',
    'view media',
    'create media',
    'update media',
    'update any media',
    'delete media',
    'delete any media',
    // Other permissions.
    'administer views',
    'access content overview',
    'view all revisions',
    'administer content types',
    'administer node fields',
    'administer node form display',
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
  protected function setUp() {
    parent::setUp();

    // Have two users ready to be used in tests.
    $this->adminUser = $this->drupalCreateUser(static::$adminUserPermissions);
    $this->nonAdminUser = $this->drupalCreateUser([]);
    // Start off logged in as admin.
    $this->drupalLogin($this->adminUser);

    $this->storage = $this->container->get('entity_type.manager')->getStorage('media');
  }

}
