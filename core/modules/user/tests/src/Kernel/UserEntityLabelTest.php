<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests the label callback.
 *
 * @group user
 */
class UserEntityLabelTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'user_hooks_test',
  ];

  /**
   * Tests label callback.
   */
  public function testLabelCallback() {
    $this->installEntitySchema('user');

    $account = $this->createUser();
    $anonymous = User::create(['uid' => 0]);

    $this->assertEquals($account->getAccountName(), $account->label());

    // Setup a random anonymous name to be sure the name is used.
    $name = $this->randomMachineName();
    $this->config('user.settings')->set('anonymous', $name)->save();
    $this->assertEquals($name, $anonymous->label());
    $this->assertEquals($name, $anonymous->getDisplayName());
    $this->assertEmpty($anonymous->getAccountName());

    // Set to test the altered username.
    \Drupal::state()->set('user_hooks_test_user_format_name_alter', TRUE);

    // The user display name should be altered.
    $this->assertEquals('<em>' . $account->id() . '</em>', $account->getDisplayName());
    // The user login name should not be altered.
    $this->assertEquals($account->name->value, $account->getAccountName());
  }

}
