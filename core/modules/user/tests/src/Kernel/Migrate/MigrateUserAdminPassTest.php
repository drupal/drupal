<?php

namespace Drupal\Tests\user\Kernel\Migrate;

use Drupal\Tests\migrate\Kernel\MigrateTestBase;
use Drupal\user\Entity\User;

/**
 * Tests preservation of root account password.
 *
 * @group user
 */
class MigrateUserAdminPassTest extends MigrateTestBase {

  /**
   * The passwords as retrieved from the account entities before migration.
   *
   * @var array
   */
  protected $originalPasswords = [];

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = ['user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Make sure the admin user and a regular user are created.
    $this->container->get('module_handler')->loadInclude('user', 'install');
    $this->installEntitySchema('user');
    user_install();
    /** @var \Drupal\user\Entity\User $admin_account */
    $admin_account = User::load(1);
    $admin_account->setPassword('original');
    $admin_account->save();
    $this->originalPasswords[1] = $admin_account->getPassword();

    /** @var \Drupal\user\Entity\User $user_account */
    $user_account = User::create([
      'uid' => 2,
      'name' => 'original_username',
      'mail' => 'original_email@example.com',
      'pass' => 'original_password',
    ]);
    $user_account->save();
    $this->originalPasswords[2] = $user_account->getPassword();
  }

  /**
   * Tests preserving the admin user's password.
   */
  public function testAdminPasswordPreserved() {
    $user_data_rows = [
      [
        'id' => '1',
        'username' => 'site_admin',
        'password' => 'new_password',
        'email' => 'site_admin@example.com',
      ],
      [
        'id' => '2',
        'username' => 'random_user',
        'password' => 'random_password',
        'email' => 'random_user@example.com',
      ],
    ];
    $ids = ['id' => ['type' => 'integer']];
    $definition = [
      'id' => 'users',
      'migration_tags' => ['Admin password test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => $user_data_rows,
        'ids' => $ids,
      ],
      'process' => [
        'uid' => 'id',
        'name' => 'username',
        'mail' => 'email',
        'pass' => 'password',
      ],
      'destination' => ['plugin' => 'entity:user'],
    ];
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);
    $this->executeMigration($migration);

    // Verify that admin username and email were changed, but password was not.
    /** @var \Drupal\user\Entity\User $admin_account */
    $admin_account = User::load(1);
    $this->assertIdentical($admin_account->getUsername(), 'site_admin');
    $this->assertIdentical($admin_account->getEmail(), 'site_admin@example.com');
    $this->assertIdentical($admin_account->getPassword(), $this->originalPasswords[1]);

    // Verify that everything changed for the regular user.
    /** @var \Drupal\user\Entity\User $user_account */
    $user_account = User::load(2);
    $this->assertIdentical($user_account->getUsername(), 'random_user');
    $this->assertIdentical($user_account->getEmail(), 'random_user@example.com');
    $this->assertNotIdentical($user_account->getPassword(), $this->originalPasswords[2]);
  }

}
