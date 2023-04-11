<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests unblocking the anonymous user account.
 *
 * @group user
 */
class UserAnonymousActivateTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
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
   * Tests that the anonymous user cannot be activated.
   */
  public function testAnonymousActivate() {
    $accountAnon = \Drupal::entityTypeManager()->getStorage('user')->load(0);

    // Test that the anonymous user is blocked.
    $this->assertTrue($accountAnon->isBlocked());

    // Test that the anonymous user cannot be activated.
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The anonymous user account should remain blocked at all times.');
    $accountAnon->activate();
  }

}
