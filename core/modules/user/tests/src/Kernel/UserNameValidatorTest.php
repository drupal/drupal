<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\UserInterface;
use Drupal\user\UserNameValidator;

/**
 * Verify that user validity checks behave as designed.
 *
 * @group user
 */
class UserNameValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * The user validator under test.
   */
  protected UserNameValidator $userValidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->userValidator = $this->container->get('user.name_validator');
  }

  /**
   * Tests valid user name validation.
   *
   * @dataProvider validUsernameProvider
   */
  public function testValidUsernames($name): void {
    $violations = $this->userValidator->validateName($name);
    $this->assertEmpty($violations);
  }

  /**
   * Tests invalid user name validation.
   *
   * @dataProvider invalidUserNameProvider
   */
  public function testInvalidUsernames($name, $expectedMessage): void {
    $violations = $this->userValidator->validateName($name);
    $this->assertNotEmpty($violations);
    $this->assertEquals($expectedMessage, $violations[0]->getMessage());
  }

  /**
   * Provides valid user names.
   */
  public static function validUsernameProvider(): array {
    // cSpell:disable
    return [
      'lowercase' => ['foo'],
      'uppercase' => ['FOO'],
      'contains space' => ['Foo O\'Bar'],
      'contains @' => ['foo@bar'],
      'allow email' => ['foo@example.com'],
      'allow invalid domain' => ['foo@-example.com'],
      'allow special chars' => ['þòøÇßªř€'],
      'allow plus' => ['foo+bar'],
      'utf8 runes' => ['ᚠᛇᚻ᛫ᛒᛦᚦ'],
    ];
    // cSpell:enable
  }

  /**
   * Provides invalid user names.
   */
  public static function invalidUserNameProvider(): array {
    return [
      'starts with space' => [' foo', 'The username cannot begin with a space.'],
      'ends with space' => ['foo ', 'The username cannot end with a space.'],
      'contains 2 spaces' => ['foo  bar', 'The username cannot contain multiple spaces in a row.'],
      'empty string' => ['', 'You must enter a username.'],
      'invalid chars' => ['foo/', 'The username contains an illegal character.'],
      // NULL.
      'contains chr(0)' => ['foo' . chr(0) . 'bar', 'The username contains an illegal character.'],
      // CR.
      'contains chr(13)' => ['foo' . chr(13) . 'bar', 'The username contains an illegal character.'],
      'excessively long' => [str_repeat('x', UserInterface::USERNAME_MAX_LENGTH + 1),
        'The username xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx is too long: it must be 60 characters or less.',
      ],
    ];
  }

}
