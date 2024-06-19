<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests deleting of user accounts.
 *
 * @group user
 */
class UserDeleteTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
  ];

  /**
   * Tests deleting multiple users.
   */
  public function testUserDeleteMultiple(): void {
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');

    // Create a few users with permissions, so roles will be created.
    $user_a = $this->createUser(['access user profiles']);
    $user_b = $this->createUser(['access user profiles']);
    $user_c = $this->createUser(['access user profiles']);

    $uids = [$user_a->id(), $user_b->id(), $user_c->id()];

    // These users should have a role
    $connection = Database::getConnection();
    $query = $connection->select('user__roles', 'r');
    $roles_created = $query
      ->fields('r', ['entity_id'])
      ->condition('entity_id', $uids, 'IN')
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertGreaterThan(0, $roles_created);
    // We should be able to load one of the users.
    $this->assertNotNull(User::load($user_a->id()));
    // Delete the users.
    $storage = $this->container->get('entity_type.manager')->getStorage('user');
    $users = $storage->loadMultiple($uids);
    $storage->delete($users);
    // Test if the roles assignments are deleted.
    $query = $connection->select('user__roles', 'r');
    $roles_after_deletion = $query
      ->fields('r', ['entity_id'])
      ->condition('entity_id', $uids, 'IN')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(0, $roles_after_deletion);
    // Test if the users are deleted, User::load() will return NULL.
    $this->assertNull(User::load($user_a->id()));
    $this->assertNull(User::load($user_b->id()));
    $this->assertNull(User::load($user_c->id()));
  }

}
