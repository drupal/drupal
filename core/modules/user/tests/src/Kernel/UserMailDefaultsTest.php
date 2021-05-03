<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\locale\Locale;

/**
 * Tests that user.mail default settings are parsed correctly.
 *
 * @group user
 */
class UserMailDefaultsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'system'];

  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['user']);
  }

  /**
   * Tests that each user mail contains blank lines.
   *
   * @dataProvider userMailsProvider
   */
  public function testMailDefaults($key) {
    $body = $this->config('user.mail')->get("$key.body");
    $this->assertStringContainsString("\n\n", $body);
  }

  /**
   * Data provider for user mail testing.
   *
   * @return array
   */
  public function userMailsProvider() {
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
