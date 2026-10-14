<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Extension\Command;

use Drupal\Core\Command\Exception\UserAbortException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Extension\Command\ExtensionUninstallCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests the 'ex:uninstall' command.
 */
#[Group('Console')]
#[RunTestsInSeparateProcesses]
#[CoversClass(ExtensionUninstallCommand::class)]
class ExtensionUninstallCommandTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // hook_modules_uninstalled() in the User module cleans up users_data.
    $this->installSchema('user', ['users_data']);
    // console_test depends on autowire_test, and test_subtheme on
    // test_base_theme, so uninstalling the dependency pulls in the dependent.
    $this->container->get('module_installer')->install(['console_test']);
    $this->container->get('theme_installer')->install(['test_subtheme']);
  }

  /**
   * Builds a command tester for the 'ex:uninstall' command.
   */
  private function commandTester(): CommandTester {
    $command = new ExtensionUninstallCommand(
      $this->container->get('module_installer'),
      $this->container->get('theme_installer'),
      $this->container->get('extension.list.module'),
      $this->container->get('extension.list.theme'),
      $this->container->get('messenger'),
    );
    return new CommandTester($command);
  }

  /**
   * Whether the given module is installed.
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
   * Tests that --dry-run lists the operations but uninstalls nothing.
   */
  public function testDryRunUninstallsNothing(): void {
    $tester = $this->commandTester();
    $code = $tester->execute(['extensions' => 'autowire_test', '--dry-run' => TRUE]);
    $display = $tester->getDisplay();

    $this->assertEquals(Command::SUCCESS, $code, $tester->getDisplay());
    // The requested module and its dependent are reported.
    $this->assertStringContainsString('autowire_test', $display);
    $this->assertStringContainsString('console_test', $display);
    $this->assertStringContainsString('Dry run', $display);
    // Nothing was actually uninstalled.
    $this->assertTrue($this->moduleInstalled('autowire_test'));
    $this->assertTrue($this->moduleInstalled('console_test'));
  }

  /**
   * Tests confirming the prompt uninstalls the module and its dependent.
   */
  public function testUninstallWithConfirmedDependent(): void {
    $tester = $this->commandTester();
    $tester->setInputs(['yes']);
    $code = $tester->execute(['extensions' => 'autowire_test'], ['interactive' => TRUE]);

    $this->assertEquals(Command::SUCCESS, $code, $tester->getDisplay());
    $this->assertFalse($this->moduleInstalled('autowire_test'));
    $this->assertFalse($this->moduleInstalled('console_test'));
  }

  /**
   * Tests that declining the prompt throws a UserAbortException.
   */
  public function testDeclinedPromptAborts(): void {
    $tester = $this->commandTester();
    $tester->setInputs(['no']);

    try {
      $tester->execute(['extensions' => 'autowire_test'], ['interactive' => TRUE]);
      $this->fail('Expected a UserAbortException to be thrown.');
    }
    catch (UserAbortException) {
      // Expected.
    }

    $this->assertTrue($this->moduleInstalled('autowire_test'));
    $this->assertTrue($this->moduleInstalled('console_test'));
  }

  /**
   * Tests that --yes skips the confirmation prompt.
   */
  public function testYesOptionSkipsPrompt(): void {
    $tester = $this->commandTester();
    // No inputs are provided; with --yes the prompt must not be reached.
    $code = $tester->execute(['extensions' => 'autowire_test', '--yes' => TRUE]);

    $this->assertEquals(Command::SUCCESS, $code, $tester->getDisplay());
    $this->assertFalse($this->moduleInstalled('autowire_test'));
    $this->assertFalse($this->moduleInstalled('console_test'));
  }

  /**
   * Tests that a non-interactive run aborts unless --yes is supplied.
   */
  public function testNonInteractiveAbortsWithoutYes(): void {
    // Dependents must be confirmed with --yes when there is no interactive
    // prompt to answer; the command aborts before uninstalling anything.
    $this->expectException(UserAbortException::class);
    $this->commandTester()->execute(['extensions' => 'autowire_test'], ['interactive' => FALSE]);
  }

  /**
   * Tests uninstalling a module without additional dependents.
   */
  public function testUninstallWithoutDependents(): void {
    $tester = $this->commandTester();
    $code = $tester->execute(['extensions' => 'console_test']);

    $this->assertEquals(Command::SUCCESS, $code, $tester->getDisplay());
    $this->assertFalse($this->moduleInstalled('console_test'));
    $this->assertTrue($this->moduleInstalled('autowire_test'));
  }

  /**
   * Tests that an already uninstalled module is a no-op.
   */
  public function testAlreadyUninstalled(): void {
    $tester = $this->commandTester();
    $code = $tester->execute(['extensions' => 'help']);

    $this->assertEquals(Command::SUCCESS, $code, $tester->getDisplay());
    $this->assertStringContainsString('Nothing to do', $tester->getDisplay());
  }

  /**
   * Tests uninstalling a base theme and its installed sub-theme.
   */
  public function testThemeUninstall(): void {
    $tester = $this->commandTester();
    $code = $tester->execute(['extensions' => 'test_base_theme', '--yes' => TRUE]);
    $display = $tester->getDisplay();

    $this->assertEquals(Command::SUCCESS, $code, $tester->getDisplay());
    $this->assertStringContainsString('test_subtheme', $display);
    $this->assertFalse($this->themeInstalled('test_subtheme'));
    $this->assertFalse($this->themeInstalled('test_base_theme'));
  }

  /**
   * Tests that the default theme cannot be uninstalled.
   */
  public function testDefaultThemeFails(): void {
    $this->config('system.theme')->set('default', 'test_subtheme')->save();
    $tester = $this->commandTester();
    $code = $tester->execute(['extensions' => 'test_subtheme']);

    $this->assertEquals(Command::FAILURE, $code);
    $this->assertStringContainsString('cannot be uninstalled', $tester->getDisplay());
    $this->assertTrue($this->themeInstalled('test_subtheme'));
  }

  /**
   * Tests that a module blocked by an uninstall validator is reported.
   */
  public function testValidatorBlocksUninstall(): void {
    $this->container->get('module_installer')->install([
      'test_module_required_by_theme',
      'test_another_module_required_by_theme',
    ]);
    $this->container->get('theme_installer')->install(['test_theme_depending_on_modules']);

    $tester = $this->commandTester();
    $code = $tester->execute(['extensions' => 'test_module_required_by_theme', '--dry-run' => TRUE]);
    $display = $tester->getDisplay();

    $this->assertEquals(Command::FAILURE, $code, $display);
    $this->assertStringContainsString('Required by the theme', $display);
    $this->assertTrue($this->moduleInstalled('test_module_required_by_theme'));
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
   * Tests that modules and themes cannot be uninstalled in the same invocation.
   */
  public function testCannotMixModulesAndThemes(): void {
    $tester = $this->commandTester();
    $code = $tester->execute(['extensions' => 'console_test,test_subtheme']);

    $this->assertEquals(Command::FAILURE, $code);
    $this->assertStringContainsString('Cannot uninstall modules', $tester->getDisplay());
    $this->assertTrue($this->moduleInstalled('console_test'));
    $this->assertTrue($this->themeInstalled('test_subtheme'));
  }

}
