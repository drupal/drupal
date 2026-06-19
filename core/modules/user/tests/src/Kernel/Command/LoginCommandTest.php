<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Command;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Command\LoginCommand;
use Drupal\user\OneTimeAuthentication;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests user:login console command failure modes (blocked, user not found).
 *
 * Since none of this is generating a valid link, it can all be done in a Kernel
 * test. The Functional version of this test verifies a successful invocation
 * and ensures the link really works to log in to the site.
 *
 * @see \Drupal\Tests\user\Functional\Command\LoginCommandTest
 */
#[Group('user')]
#[RunTestsInSeparateProcesses]
#[CoversClass(LoginCommand::class)]
class LoginCommandTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Tests trying to get a login link for a blocked user.
   */
  public function testBlockedUser(): void {
    $blockedUser = $this->createUser();
    $blockedUser->block()->save();

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("Account {$blockedUser->getAccountName()} is blocked and thus cannot login.");

    $tester = $this->buildLoginCommandTester();
    $tester->execute(['--uid' => $blockedUser->id()]);
  }

  /**
   * Tests invalid login attempts.
   *
   * Since we can only call expectException() once per test, we need each
   * invocation to be a separate test. Therefore, we use a DataProvider to run
   * each of these attempts individually.
   */
  #[DataProvider('invalidUserProvider')]
  public function testLoginInvalidUsers(string $property, string $value): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("Unable to load user by $property: $value");

    $tester = $this->buildLoginCommandTester();
    $tester->execute(['--' . $property => $value]);
  }

  /**
   * Data provider for testing invalid user login attempts.
   *
   * @return array
   *   Test cases.
   */
  public static function invalidUserProvider(): array {
    return [
      'uid' => [
        'property' => 'uid',
        'value' => '20',
      ],
      'name' => [
        'property' => 'name',
        'value' => 'not-a-user',
      ],
      'mail' => [
        'property' => 'mail',
        'value' => 'not-a-user@example.com',
      ],
    ];
  }

  /**
   * Builds a CommandTester to test the user:login command.
   */
  protected function buildLoginCommandTester(): CommandTester {
    $command = new LoginCommand(
      $this->container->get('entity_type.manager'),
      $this->container->get(OneTimeAuthentication::class),
    );
    return new CommandTester($command);
  }

}
