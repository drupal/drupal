<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests validating user modules' configuration.
 *
 * @group user
 */
class UserConfigValidationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('user');
  }

  /**
   * Data provider for testUserSettings().
   *
   * @return array
   */
  public static function providerTestUserSettings(): array {
    return [
      "Invalid register" => [
        'register',
        'somebody',
        'The value you selected is not a valid choice.',
      ],
      "Invalid cancel_method" => [
        'cancel_method',
        'somebody',
        'The value you selected is not a valid choice.',
      ],
      "Invalid password_reset_timeout" => [
        'password_reset_timeout',
        '0',
        'This value should be <em class="placeholder">1</em> or more.',
      ],
    ];
  }

  /**
   * Tests invalid values in 'user.settings' config properties.
   *
   * @dataProvider providerTestUserSettings
   */
  public function testUserSettings($property, $property_value, $expected_message): void {
    $config_name = 'user.settings';
    $config = $this->config($config_name);
    $violations = $this->container->get('config.typed')
      ->createFromNameAndData($config_name, $config->set($property, $property_value)->get())
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame($property, $violations[0]->getPropertyPath());
    $this->assertSame($expected_message, (string) $violations[0]->getMessage());
  }

}
