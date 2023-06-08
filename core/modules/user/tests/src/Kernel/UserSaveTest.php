<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests account saving for arbitrary new uid.
 *
 * @group user
 */
class UserSaveTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
  ];

  /**
   * Ensures that an existing password is unset after the user was saved.
   */
  public function testExistingPasswordRemoval() {
    $this->installEntitySchema('user');

    /** @var \Drupal\user\Entity\User $user */
    $user = User::create(['name' => $this->randomMachineName()]);
    $user->save();
    $user->setExistingPassword('existing password');
    $this->assertNotNull($user->pass->existing);
    $user->save();
    $this->assertNull($user->pass->existing);
  }

}
