<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that user.mail default settings are parsed correctly.
 */
#[Group('user')]
#[RunTestsInSeparateProcesses]
class UserMailDefaultsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['user']);
  }

  /**
   * Tests that each user mail contains blank lines.
   */
  #[DataProvider('userMailsProvider')]
  public function testMailDefaults($key): void {
    $body = $this->config('user.mail')->get("$key.body");
    $this->assertStringContainsString("\n\n", $body);
  }

  /**
   * Data provider for user mail testing.
   *
   * @return array
   *   Array of arrays containing the set of user mail configuration keys.
   */
  public static function userMailsProvider() {
    return [
      ['cancel_confirm'],
      ['password_reset'],
      ['status_activated'],
      ['status_blocked'],
      ['status_canceled'],
      ['register_admin_created'],
      ['register_no_approval_required'],
      ['register_pending_approval'],
      ['register_pending_approval_admin'],
    ];
  }

}
