<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Command;

use Drupal\Core\Command\Exception\UserAbortException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Command\ExtensionInstallCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests the 'ex:install' command.
 */
#[Group('Console')]
#[RunTestsInSeparateProcesses]
#[CoversClass(ExtensionInstallCommand::class)]
class ExtensionInstallCommandTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'help', 'user'];

  /**
   * Builds a command tester for the 'ex:install' command.
   */
  private function commandTester(): CommandTester {
    $command = new ExtensionInstallCommand(
      $this->container->get('module_installer'),
      $this->container->get('theme_installer'),
      $this->container->get('extension.list.module'),
      $this->container->get('extension.list.theme'),
      $this->container->get('messenger'),
    );
    return new CommandTester($command);
  }

  /**
   * Whether the given module is installed, read fresh from active config.
   */
  private function moduleInstalled(string $module): bool {
    return $this->container->get('module_handler')->moduleExists($module);
  }

  /**
   * Whether the given theme is installed.
   */
  private function themeInstalled(string $theme): bool {
    return array_key_exists($theme, $this->container->get('theme_handler')->listInfo());
  }

  /**
   * Tests that --dry-run lists the operations but installs nothing.
   */
  public function testDryRunInstallsNothing(): void {
    $tester = $this->commandTester();
    $code = $tester->execute(['extensions' => 'console_test', '--dry-run' => TRUE]);
    $display = $tester->getDisplay();

    $this->assertEquals(Command::SUCCESS, $code);
    // The requested module and its dependency are reported.
    $this->assertStringContainsString('console_test', $display);
    $this->assertStringContainsString('autowire_test', $display);
    $this->assertStringContainsString('Dry run', $display);
    // Nothing was actually installed.
    $this->assertFalse($this->moduleInstalled('console_test'));
    $this->assertFalse($this->moduleInstalled('autowire_test'));
  }

  /**
   * Tests confirming the prompt installs the module and its dependency.
   */
  public function testInstallWithConfirmedDependency(): void {
    $tester = $this->commandTester();
    $tester->setInputs(['yes']);
    $code = $tester->execute(['extensions' => 'console_test'], ['interactive' => TRUE]);

    $this->assertEquals(Command::SUCCESS, $code);
    $this->assertTrue($this->moduleInstalled('console_test'));
    $this->assertTrue($this->moduleInstalled('autowire_test'));
  }

  /**
   * Tests that declining the prompt throws a UserAbortException.
   */
  public function testDeclinedPromptAborts(): void {
    $tester = $this->commandTester();
    $tester->setInputs(['no']);

    try {
      $tester->execute(['extensions' => 'console_test'], ['interactive' => TRUE]);
      $this->fail('Expected a UserAbortException to be thrown.');
    }
    catch (UserAbortException) {
      // Expected.
    }

    $this->assertFalse($this->moduleInstalled('console_test'));
    $this->assertFalse($this->moduleInstalled('autowire_test'));
  }

  /**
   * Tests that --yes skips the confirmation prompt.
   */
  public function testYesOptionSkipsPrompt(): void {
    $tester = $this->commandTester();
    // No inputs are provided; with --yes the prompt must not be reached.
    $code = $tester->execute(['extensions' => 'console_test', '--yes' => TRUE]);

    $this->assertEquals(Command::SUCCESS, $code);
    $this->assertTrue($this->moduleInstalled('console_test'));
    $this->assertTrue($this->moduleInstalled('autowire_test'));
  }

  /**
   * Tests that a non-interactive run aborts unless --yes is supplied.
   */
  public function testNonInteractiveAbortsWithoutYes(): void {
    // Dependencies must be confirmed with --yes when there is no interactive
    // prompt to answer; the command aborts before installing anything.
    $this->expectException(UserAbortException::class);
    $this->commandTester()->execute(['extensions' => 'console_test'], ['interactive' => FALSE]);
  }

  /**
   * Tests installing a module without additional dependencies.
   */
  public function testInstallWithoutDependencies(): void {
    $tester = $this->commandTester();
    $code = $tester->execute(['extensions' => 'autowire_test']);

    $this->assertEquals(Command::SUCCESS, $code);
    $this->assertTrue($this->moduleInstalled('autowire_test'));
  }

  /**
   * Tests installing a theme and its base theme dependency.
   */
  public function testThemeInstall(): void {
    $tester = $this->commandTester();
    $code = $tester->execute(['extensions' => 'test_subtheme', '--yes' => TRUE]);
    $display = $tester->getDisplay();

    $this->assertEquals(Command::SUCCESS, $code);
    $this->assertStringContainsString('test_base_theme', $display);
    $this->assertTrue($this->themeInstalled('test_subtheme'));
    $this->assertTrue($this->themeInstalled('test_base_theme'));
  }

  /**
   * Tests that an unknown extension produces an error.
   */
  public function testUnknownExtensionFails(): void {
    $tester = $this->commandTester();
    $code = $tester->execute(['extensions' => 'this_does_not_exist']);

    $this->assertEquals(Command::FAILURE, $code);
    $this->assertStringContainsString('could not be found', $tester->getDisplay());
  }

  /**
   * Tests that Help and Permissions links appear for console_test after install.
   */
  public function testExtensionLinksAppearInSuccessOutput(): void {
    $tester = $this->commandTester();
    $code = $tester->execute(['extensions' => 'console_test', '--yes' => TRUE]);
    $display = $tester->getDisplay();

    $this->assertEquals(Command::SUCCESS, $code);
    // console_test implements hook_help and declares a permission, so both
    // links should appear alongside it in the success listing.
    $this->assertStringContainsString('(console_test) — Help, Permissions', $display);
  }

  /**
   * Tests that modules and themes cannot be installed in the same invocation.
   */
  public function testCannotMixModulesAndThemes(): void {
    $tester = $this->commandTester();
    $code = $tester->execute(['extensions' => 'console_test,test_subtheme']);

    $this->assertEquals(Command::FAILURE, $code);
    $this->assertStringContainsString('Cannot install modules', $tester->getDisplay());
    $this->assertFalse($this->moduleInstalled('console_test'));
  }

}
