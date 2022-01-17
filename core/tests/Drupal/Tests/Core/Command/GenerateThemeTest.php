<?php

namespace Drupal\Tests\Core\Command;

use Drupal\BuildTests\QuickStart\QuickStartTestBase;
use Drupal\sqlite\Driver\Database\sqlite\Install\Tasks;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Tests the generate-theme commands.
 *
 * @requires extension pdo_sqlite
 *
 * @group Command
 */
class GenerateThemeTest extends QuickStartTestBase {

  /**
   * The PHP executable path.
   *
   * @var string
   */
  protected $php;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    if (version_compare(\SQLite3::version()['versionString'], Tasks::SQLITE_MINIMUM_VERSION) < 0) {
      $this->markTestSkipped();
    }
    parent::setUp();
    $php_executable_finder = new PhpExecutableFinder();
    $this->php = $php_executable_finder->find();
    $this->copyCodebase();
    $this->executeCommand('COMPOSER_DISCARD_CHANGES=true composer install --no-dev --no-interaction');
    chdir($this->getWorkingPath());
  }

  /**
   * Tests the generate-theme command.
   */
  public function test() {
    $install_command = [
      $this->php,
      'core/scripts/drupal',
      'generate-theme',
      'test_custom_theme',
      '--name="Test custom starterkit theme"',
      '--description="Custom theme generated from a starterkit theme"',
    ];
    $process = new Process($install_command, NULL);
    $process->setTimeout(60);
    $result = $process->run();
    $this->assertEquals('Theme generated successfully to themes/test_custom_theme', trim($process->getOutput()));
    $this->assertSame(0, $result);

    $theme_path_relative = 'themes/test_custom_theme';
    $theme_path_absolute = $this->getWorkspaceDirectory() . "/$theme_path_relative";
    $this->assertFileExists($theme_path_absolute . '/test_custom_theme.info.yml');

    // Ensure that the generated theme can be installed.
    $this->installQuickStart('minimal');
    $this->formLogin($this->adminUsername, $this->adminPassword);
    $this->visit('/admin/appearance');
    $this->getMink()->assertSession()->pageTextContains('Test custom starterkit');
    $this->getMink()->assertSession()->pageTextContains('Custom theme generated from a starterkit theme');
    $this->getMink()->getSession()->getPage()->clickLink('Install "Test custom starterkit theme" theme');
    $this->getMink()->assertSession()->pageTextContains('The "Test custom starterkit theme" theme has been installed.');

    $this->assertFileExists($theme_path_absolute . '/test_custom_theme.theme');
    unlink($theme_path_absolute . '/test_custom_theme.theme');
    $process = new Process($install_command, NULL);
    $process->setTimeout(60);
    $result = $process->run();
    $this->assertStringContainsString('Theme could not be generated because the destination directory', $process->getErrorOutput());
    $this->assertStringContainsString($theme_path_relative, $process->getErrorOutput());
    $this->assertSame(1, $result);
    $this->assertFileDoesNotExist($theme_path_absolute . '/test_custom_theme.theme');
  }

  /**
   * Tests themes that do not exist return an error.
   */
  public function testThemeDoesNotExist(): void {
    $install_command = [
      $this->php,
      'core/scripts/drupal',
      'generate-theme',
      'test_custom_theme',
      '--name="Test custom starterkit theme"',
      '--description="Custom theme generated from a starterkit theme"',
      '--starterkit',
      'foobarbaz',
    ];
    $process = new Process($install_command, NULL);
    $process->setTimeout(60);
    $result = $process->run();
    $this->assertStringContainsString('Theme source theme foobarbaz cannot be found.', trim($process->getErrorOutput()));
    $this->assertSame(1, $result);
  }

  /**
   * Tests that only themes with `starterkit` flag can be used.
   */
  public function testStarterKitFlag(): void {
    // Explicitly not a starter theme.
    $install_command = [
      $this->php,
      'core/scripts/drupal',
      'generate-theme',
      'test_custom_theme',
      '--name="Test custom starterkit theme"',
      '--description="Custom theme generated from a starterkit theme"',
      '--starterkit',
      'stark',
    ];
    $process = new Process($install_command, NULL);
    $process->setTimeout(60);
    $result = $process->run();
    $this->assertStringContainsString('Theme source theme stark is not a valid starter kit.', trim($process->getErrorOutput()));
    $this->assertSame(1, $result);

    // Has not defined `starterkit`.
    $install_command = [
      $this->php,
      'core/scripts/drupal',
      'generate-theme',
      'test_custom_theme',
      '--name="Test custom starterkit theme"',
      '--description="Custom theme generated from a starterkit theme"',
      '--starterkit',
      'bartik',
    ];
    $process = new Process($install_command, NULL);
    $process->setTimeout(60);
    $result = $process->run();
    $this->assertStringContainsString('Theme source theme bartik is not a valid starter kit.', trim($process->getErrorOutput()));
    $this->assertSame(1, $result);
  }

}
