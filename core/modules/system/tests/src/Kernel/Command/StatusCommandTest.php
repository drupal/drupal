<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Command;

use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Command\StatusCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Tests the 'system:status' command.
 */
#[Group('Console')]
#[RunTestsInSeparateProcesses]
#[CoversClass(StatusCommand::class)]
class StatusCommandTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
  ];

  /**
   * Tests variations of the 'DRUPAL_URI' context.
   */
  public function testDrupalUriContext(): void {
    $tester = $this->applicationTester();
    $this->assertEquals(Command::SUCCESS, $tester->run(['command' => 'system:status']));
    $this->assertStringContainsStringNoWhitespace('Site URL : http://localhost', $tester->getDisplay());
    $this->assertStringContainsStringNoWhitespace('Drupal version : ' . \Drupal::VERSION, $tester->getDisplay());

    // When the host is provided without a port, 80 is used, and this is omitted
    // from generated URLs.
    $tester = $this->applicationTester(['DRUPAL_URI' => 'https://example.com']);
    $this->assertEquals(Command::SUCCESS, $tester->run(['command' => 'system:status']));
    $this->assertStringContainsStringNoWhitespace('Site URL : https://example.com', $tester->getDisplay());

    // Test when a different port is present in generated URLs.
    $tester = $this->applicationTester(['DRUPAL_URI' => 'https://example.com:3333']);
    $this->assertEquals(Command::SUCCESS, $tester->run(['command' => 'system:status']));
    $this->assertStringContainsStringNoWhitespace('Site URL : https://example.com:3333', $tester->getDisplay());

    // Test base URL with a subdirectory.
    $tester = $this->applicationTester(['DRUPAL_URI' => 'https://example.com/drupal']);
    $this->assertEquals(Command::SUCCESS, $tester->run(['command' => 'system:status']));
    $this->assertStringContainsStringNoWhitespace('Site URL : https://example.com/drupal', $tester->getDisplay());

    // Test base URL with a port and a subdirectory.
    $tester = $this->applicationTester(['DRUPAL_URI' => 'https://example.com:3333/drupal']);
    $this->assertEquals(Command::SUCCESS, $tester->run(['command' => 'system:status']));
    $this->assertStringContainsStringNoWhitespace('Site URL : https://example.com:3333/drupal', $tester->getDisplay());
  }

  /**
   * Tests the example:command with the --url option forced into argv.
   */
  public function testWithUrl(): void {
    // ApplicationTester does not setup argv for us. So we force it here.
    $_SERVER['argv'] = [
      'dr',
      'system:status',
      '--url',
      'https://test.example.com',
    ];
    $tester = $this->applicationTester();
    $this->assertEquals(Command::SUCCESS, $tester->run(['command' => 'system:status']));
    $this->assertStringContainsStringNoWhitespace('Site URL : https://test.example.com', $tester->getDisplay());

    // Try again with a subdirectory in the base URL.
    $_SERVER['argv'] = [
      'dr',
      'example:command',
      '--url',
      'https://test.example.com/drupal',
    ];
    $tester = $this->applicationTester();
    $this->assertEquals(Command::SUCCESS, $tester->run(['command' => 'system:status']));
    $this->assertStringContainsStringNoWhitespace('Site URL : https://test.example.com/drupal', $tester->getDisplay());
  }

  /**
   * Asserts that a string contains another string ignoring whitespace.
   *
   * @param string $needle
   *   The string we're looking for.
   * @param string $haystack
   *   The string we're looking in.
   * @param string|null $message
   *   Optional custom assertion message.
   */
  protected function assertStringContainsStringNoWhitespace(string $needle, string $haystack, ?string $message = NULL): void {
    $normalized_needle = preg_replace('/\s+/', ' ', trim($needle));
    $normalized_haystack = preg_replace('/\s+/', ' ', trim($haystack));
    $this->assertStringContainsString($normalized_needle, $normalized_haystack, $message ?? '');
  }

  /**
   * Build our ApplicationTester.
   */
  private function applicationTester(array $context = []): ApplicationTester {
    $application = include __DIR__ . '/../../../../../../../vendor/bin/dr';
    $application = $application($context);
    $application->setAutoExit(FALSE);
    return new ApplicationTester($application);
  }

}
