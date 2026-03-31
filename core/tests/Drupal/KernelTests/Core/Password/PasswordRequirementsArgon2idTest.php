<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Password;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\system\Hook\SystemRequirementsHooks;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Password System Requirements.
 */
#[Group('Password')]
#[RunTestsInSeparateProcesses]
#[CoversMethod(SystemRequirementsHooks::class, 'checkPasswordHashing')]
class PasswordRequirementsArgon2idTest extends PasswordTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected ?string $passwordAlgorithm = PASSWORD_ARGON2ID;

  /**
   * Tests that password hashing generates an info requirement by default.
   */
  public function testRequirementsWithBcrypt(): void {
    $requirements = $this->checkSystemRequirements();
    $this->assertArrayHasKey('password_hashing', $requirements);
    $this->assertSame(RequirementSeverity::Info, $requirements['password_hashing']['severity']);
    $this->assertEquals(
      'Passwords are hashed with the <em class="placeholder">argon2id</em> algorithm.',
      (string) $requirements['password_hashing']['value']
    );
  }

}
