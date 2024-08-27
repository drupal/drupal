<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests user_install().
 *
 * @group user
 */
class UserInstallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('module_handler')->loadInclude('user', 'install');
    $this->installEntitySchema('user');
    user_install();
  }

  /**
   * Tests that the initial users have correct values.
   */
  public function testUserInstall(): void {
    $user_ids = \Drupal::entityQuery('user')->sort('uid')->accessCheck(FALSE)->execute();
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($user_ids);
    $anon = $users[0];
    $admin = $users[1];
    $this->assertNotEmpty($anon->uuid(), 'Anon user has a UUID');
    $this->assertNotEmpty($admin->uuid(), 'Admin user has a UUID');

    // Test that the anonymous and administrators languages are equal to the
    // site's default language.
    $this->assertEquals('en', $anon->language()->getId());
    $this->assertEquals('en', $admin->language()->getId());

    // Test that the administrator is active.
    $this->assertTrue($admin->isActive());
    // Test that the anonymous user is blocked.
    $this->assertTrue($anon->isBlocked());
  }

}
