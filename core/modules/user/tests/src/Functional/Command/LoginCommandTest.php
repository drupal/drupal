<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional\Command;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Command\LoginCommand;
use Drupal\user\OneTimeAuthentication;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests user:login runs as a console command and generates a working link.
 *
 * All of the failure modes are tested in a Kernel test, since it's faster.
 *
 * @see \Drupal\Tests\user\Kernel\Command\LoginCommandTest
 */
#[Group('user')]
#[RunTestsInSeparateProcesses]
#[CoversClass(LoginCommand::class)]
class LoginCommandTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests logins.
   */
  public function testLogin(): void {
    $adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access site reports',
    ]);

    $command = new LoginCommand(
      $this->container->get('entity_type.manager'),
      $this->container->get(OneTimeAuthentication::class),
    );
    $tester = new CommandTester($command);

    // Test path argument
    $code = $tester->execute(['path' => 'admin']);
    $this->assertStringContainsString('destination=admin', $tester->getDisplay());

    // Test fallback to uid=1
    $code = $tester->execute([]);
    $this->assertStringContainsString('user/reset/1', $tester->getDisplay());
    $this->assertEquals(Command::SUCCESS, $code);

    // Test account selection options for a valid user.
    $code = $tester->execute(['--uid' => '2']);
    $this->assertStringContainsString('user/reset/2', $tester->getDisplay());
    $code = $tester->execute(['--name' => $adminUser->getAccountName()]);
    $this->assertStringContainsString('user/reset/2', $tester->getDisplay());
    $code = $tester->execute(['--mail' => $adminUser->getEmail()]);
    $this->assertStringContainsString('user/reset/2', $tester->getDisplay());

    // Actually verify that link.
    $this->drupalGet(trim($tester->getDisplay()));
    $this->assertSession()->statusCodeEquals(200);
    // @see \Drupal\Tests\UiHelperTrait::drupalLogin
    $adminUser->sessionId = $this->getSession()->getCookie(\Drupal::service('session_configuration')->getOptions(\Drupal::request())['name']);
    $this->assertTrue($this->drupalUserIsLoggedIn($adminUser), "User {$adminUser->getAccountName()} should have successfully logged in.");
  }

}
