<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Password;

// cspell:ignore vogon

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
class PasswordRequirementsUnknownTest extends PasswordTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected ?string $passwordAlgorithm = 'vogon poetry';

  /**
   * {@inheritdoc}
   */
  protected ?array $passwordOptions = [
    'bureaucracy' => 'max',
    'temper' => 'very bad',
  ];

  /**
   * Tests that password hashing generates an info requirement by default.
   */
  public function testRequirementsWithUnknownAlgorithm(): void {
    $requirements = $this->checkSystemRequirements();
    $this->assertArrayHasKey('password_hashing', $requirements);
    $this->assertSame(RequirementSeverity::Error, $requirements['password_hashing']['severity']);
    $this->assertEquals(
      'The configured password hashing algorithm <em class="placeholder">vogon poetry</em> is not available in your PHP installation. Ensure that the <a href="https://www.php.net/manual/password.requirements.php">necessary PHP extensions</a> are installed and that the Drupal password hashing configuration is correct.',
      (string) $requirements['password_hashing']['value']
    );
  }

}
