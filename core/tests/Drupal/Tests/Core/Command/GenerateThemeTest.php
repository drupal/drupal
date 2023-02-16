<?php

namespace Drupal\Tests\Core\Command;

use Drupal\BuildTests\QuickStart\QuickStartTestBase;
use Drupal\Core\Serialization\Yaml;
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
  public function setUp(): void {
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
   * Generates PHP process to generate a theme from core's starterkit theme.
   *
   * @return \Symfony\Component\Process\Process
   *   The PHP process
   */
  private function generateThemeFromStarterkit($env = NULL) : Process {
    $install_command = [
      $this->php,
      'core/scripts/drupal',
      'generate-theme',
      'test_custom_theme',
      '--name="Test custom starterkit theme"',
      '--description="Custom theme generated from a starterkit theme"',
    ];
    $process = new Process($install_command, NULL, $env);
    $process->setTimeout(60);
    return $process;
  }

  /**
   * Asserts the theme exists. Returns the parsed *.info.yml file.
   *
   * @param string $theme_path_relative
   *   The core-relative path to the theme.
   *
   * @return array
   *   The parsed *.info.yml file.
   */
  private function assertThemeExists(string $theme_path_relative): array {
    $theme_path_absolute = $this->getWorkspaceDirectory() . "/$theme_path_relative";
    $theme_name = basename($theme_path_relative);
    $info_yml_filename = "$theme_name.info.yml";
    $this->assertFileExists($theme_path_absolute . '/' . $info_yml_filename);
    $info = Yaml::decode(file_get_contents($theme_path_absolute . '/' . $info_yml_filename));
    return $info;
  }

  /**
   * Tests the generate-theme command.
   */
  public function test() {
    // Do not rely on \Drupal::VERSION: change the version to a concrete version
    // number, to simulate using a tagged core release.
    $starterkit_info_yml = $this->getWorkspaceDirectory() . '/core/themes/starterkit_theme/starterkit_theme.info.yml';
    $info = Yaml::decode(file_get_contents($starterkit_info_yml));
    $info['version'] = '9.4.0';
    file_put_contents($starterkit_info_yml, Yaml::encode($info));

    $process = $this->generateThemeFromStarterkit();
    $result = $process->run();
    $this->assertEquals('Theme generated successfully to themes/test_custom_theme', trim($process->getOutput()), $process->getErrorOutput());
    $this->assertSame(0, $result);

    $theme_path_relative = 'themes/test_custom_theme';
    $info = $this->assertThemeExists($theme_path_relative);
    self::assertArrayNotHasKey('hidden', $info);
    self::assertArrayHasKey('generator', $info);
    self::assertEquals('starterkit_theme:9.4.0', $info['generator']);

    // Confirm readme is rewritten.
    $readme_file = $this->getWorkspaceDirectory() . "/$theme_path_relative/README.md";
    $this->assertSame('test_custom_theme theme, generated from starterkit_theme. Additional information on generating themes can be found in the [Starterkit documentation](https://www.drupal.org/docs/core-modules-and-themes/core-themes/starterkit-theme).', file_get_contents($readme_file));

    // Ensure that the generated theme can be installed.
    $this->installQuickStart('minimal');
    $this->formLogin($this->adminUsername, $this->adminPassword);
    $this->visit('/admin/appearance');
    $this->getMink()->assertSession()->pageTextContains('Test custom starterkit');
    $this->getMink()->assertSession()->pageTextContains('Custom theme generated from a starterkit theme');
    $this->getMink()->getSession()->getPage()->clickLink('Install "Test custom starterkit theme" theme');
    $this->getMink()->assertSession()->pageTextContains('The "Test custom starterkit theme" theme has been installed.');

    // Ensure that a new theme cannot be generated when the destination
    // directory already exists.
    $theme_path_absolute = $this->getWorkspaceDirectory() . "/$theme_path_relative";
    $this->assertFileExists($theme_path_absolute . '/test_custom_theme.theme');
    unlink($theme_path_absolute . '/test_custom_theme.theme');
    $process = $this->generateThemeFromStarterkit();
    $result = $process->run();
    $this->assertStringContainsString('Theme could not be generated because the destination directory', $process->getErrorOutput());
    $this->assertStringContainsString($theme_path_relative, $process->getErrorOutput());
    $this->assertSame(1, $result);
    $this->assertFileDoesNotExist($theme_path_absolute . '/test_custom_theme.theme');
  }

  /**
   * Tests generating a theme from another Starterkit enabled theme.
   */
  public function testGeneratingFromAnotherTheme() {
    // Do not rely on \Drupal::VERSION: change the version to a concrete version
    // number, to simulate using a tagged core release.
    $starterkit_info_yml = $this->getWorkspaceDirectory() . '/core/themes/starterkit_theme/starterkit_theme.info.yml';
    $info = Yaml::decode(file_get_contents($starterkit_info_yml));
    $info['version'] = '9.4.0';
    file_put_contents($starterkit_info_yml, Yaml::encode($info));

    $process = $this->generateThemeFromStarterkit();
    $exit_code = $process->run();
    $this->assertSame('Theme generated successfully to themes/test_custom_theme', trim($process->getOutput()), $process->getErrorOutput());
    $this->assertSame(0, $exit_code);
    $install_command = [
      $this->php,
      'core/scripts/drupal',
      'generate-theme',
      'generated_from_another_theme',
      '--name="Generated from another theme"',
      '--description="Custom theme generated from a theme other than starterkit_theme"',
      '--starterkit=test_custom_theme',
    ];
    $process = new Process($install_command);
    $exit_code = $process->run();
    $this->assertSame('Theme generated successfully to themes/generated_from_another_theme', trim($process->getOutput()), $process->getErrorOutput());
    $this->assertSame(0, $exit_code);

    // Confirm readme is rewritten.
    $readme_file = $this->getWorkspaceDirectory() . '/themes/generated_from_another_theme/README.md';
    $this->assertSame('generated_from_another_theme theme, generated from test_custom_theme. Additional information on generating themes can be found in the [Starterkit documentation](https://www.drupal.org/docs/core-modules-and-themes/core-themes/starterkit-theme).', file_get_contents($readme_file));
  }

  /**
   * Tests the generate-theme command on a dev snapshot of Drupal core.
   */
  public function testDevSnapshot() {
    // Do not rely on \Drupal::VERSION: change the version to a development
    // snapshot version number, to simulate using a branch snapshot of core.
    $starterkit_info_yml = $this->getWorkspaceDirectory() . '/core/themes/starterkit_theme/starterkit_theme.info.yml';
    $info = Yaml::decode(file_get_contents($starterkit_info_yml));
    $info['version'] = '9.4.0-dev';
    file_put_contents($starterkit_info_yml, Yaml::encode($info));

    $process = $this->generateThemeFromStarterkit();
    $result = $process->run();
    $this->assertEquals('Theme generated successfully to themes/test_custom_theme', trim($process->getOutput()), $process->getErrorOutput());
    $this->assertSame(0, $result);

    $theme_path_relative = 'themes/test_custom_theme';
    $info = $this->assertThemeExists($theme_path_relative);
    self::assertArrayNotHasKey('hidden', $info);
    self::assertArrayHasKey('generator', $info);
    self::assertMatchesRegularExpression('/^starterkit_theme\:9.4.0-dev#[0-9a-f]+$/', $info['generator']);
  }

  /**
   * Tests the generate-theme command on a theme with a release version number.
   */
  public function testContribStarterkit(): void {
    // Change the version to a concrete version number, to simulate using a
    // contrib theme as the starterkit.
    $starterkit_info_yml = $this->getWorkspaceDirectory() . '/core/themes/starterkit_theme/starterkit_theme.info.yml';
    $info = Yaml::decode(file_get_contents($starterkit_info_yml));
    $info['version'] = '1.20';
    file_put_contents($starterkit_info_yml, Yaml::encode($info));

    $process = $this->generateThemeFromStarterkit();
    $result = $process->run();
    $this->assertEquals('Theme generated successfully to themes/test_custom_theme', trim($process->getOutput()), $process->getErrorOutput());
    $this->assertSame(0, $result);
    $info = $this->assertThemeExists('themes/test_custom_theme');
    self::assertArrayNotHasKey('hidden', $info);
    self::assertArrayHasKey('generator', $info);
    self::assertEquals('starterkit_theme:1.20', $info['generator']);
  }

  /**
   * Tests the generate-theme command on a theme with a dev version number.
   */
  public function testContribStarterkitDevSnapshot(): void {
    // Change the version to a development snapshot version number, to simulate
    // using a contrib theme as the starterkit.
    $starterkit_info_yml = $this->getWorkspaceDirectory() . '/core/themes/starterkit_theme/starterkit_theme.info.yml';
    $info = Yaml::decode(file_get_contents($starterkit_info_yml));
    $info['core_version_requirement'] = '*';
    $info['version'] = '7.x-dev';
    file_put_contents($starterkit_info_yml, Yaml::encode($info));

    // Avoid the core git commit from being considered the source theme's: move
    // it out of core.
    Process::fromShellCommandline('mv core/themes/starterkit_theme themes/', $this->getWorkspaceDirectory())->run();

    $process = $this->generateThemeFromStarterkit();
    $result = $process->run();
    $this->assertEquals("The source theme starterkit_theme has a development version number (7.x-dev). Because it is not a git checkout, a specific commit could not be identified. This makes tracking changes in the source theme difficult. Are you sure you want to continue? (yes/no) [yes]:\n > Theme generated successfully to themes/test_custom_theme", trim($process->getOutput()), $process->getErrorOutput());
    $this->assertSame(0, $result);
    $info = $this->assertThemeExists('themes/test_custom_theme');
    self::assertArrayNotHasKey('hidden', $info);
    self::assertArrayHasKey('generator', $info);
    self::assertEquals('starterkit_theme:7.x-dev#unknown-commit', $info['generator']);
  }

  /**
   * Tests the generate-theme command on a theme with a dev version without git.
   */
  public function testContribStarterkitDevSnapshotWithGitNotInstalled(): void {
    // Change the version to a development snapshot version number, to simulate
    // using a contrib theme as the starterkit.
    $starterkit_info_yml = $this->getWorkspaceDirectory() . '/core/themes/starterkit_theme/starterkit_theme.info.yml';
    $info = Yaml::decode(file_get_contents($starterkit_info_yml));
    $info['core_version_requirement'] = '*';
    $info['version'] = '7.x-dev';
    file_put_contents($starterkit_info_yml, Yaml::encode($info));

    // Avoid the core git commit from being considered the source theme's: move
    // it out of core.
    Process::fromShellCommandline('mv core/themes/starterkit_theme themes/', $this->getWorkspaceDirectory())->run();

    // Confirm that 'git' is available.
    $output = [];
    exec('git --help', $output, $status);
    $this->assertEquals(0, $status);
    // Modify our $PATH so that it begins with a path that contains an
    // executable script named 'git' that always exits with 127, as if git were
    // not found. Note that we run our tests using process isolation, so we do
    // not need to restore the PATH when we are done.
    $unavailableGitPath = $this->getWorkspaceDirectory() . '/bin';
    mkdir($unavailableGitPath);
    $bash = <<<SH
#!/bin/bash
exit 127

SH;
    file_put_contents($unavailableGitPath . '/git', $bash);
    chmod($unavailableGitPath . '/git', 0755);
    // Confirm that 'git' is no longer available.
    $env = [
      'PATH' => $unavailableGitPath . ':' . getenv('PATH'),
      'COLUMNS' => 80,
    ];
    $process = new Process([
      'git',
      '--help',
    ], NULL, $env);
    $process->run();
    $this->assertEquals(127, $process->getExitCode(), 'Fake git used by process.');

    $process = $this->generateThemeFromStarterkit($env);
    $result = $process->run();
    $this->assertEquals("[ERROR] The source theme starterkit_theme has a development version number     \n         (7.x-dev). Determining a specific commit is not possible because git is\n         not installed. Either install git or use a tagged release to generate a\n         theme.", trim($process->getOutput()), $process->getErrorOutput());
    $this->assertSame(1, $result);
    $this->assertFileDoesNotExist($this->getWorkspaceDirectory() . "/themes/test_custom_theme");
  }

  /**
   * Tests the generate-theme command on a theme without a version number.
   */
  public function testCustomStarterkit(): void {
    // Omit the version, to simulate using a custom theme as the starterkit.
    $starterkit_info_yml = $this->getWorkspaceDirectory() . '/core/themes/starterkit_theme/starterkit_theme.info.yml';
    $info = Yaml::decode(file_get_contents($starterkit_info_yml));
    unset($info['version']);
    file_put_contents($starterkit_info_yml, Yaml::encode($info));

    $process = $this->generateThemeFromStarterkit();
    $result = $process->run();
    $this->assertEquals("The source theme starterkit_theme does not have a version specified. This makes tracking changes in the source theme difficult. Are you sure you want to continue? (yes/no) [yes]:\n > Theme generated successfully to themes/test_custom_theme", trim($process->getOutput()), $process->getErrorOutput());
    $this->assertSame(0, $result);
    $info = $this->assertThemeExists('themes/test_custom_theme');
    self::assertArrayNotHasKey('hidden', $info);
    self::assertArrayHasKey('generator', $info);
    self::assertEquals('starterkit_theme:unknown-version', $info['generator']);
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
      'olivero',
    ];
    $process = new Process($install_command, NULL);
    $process->setTimeout(60);
    $result = $process->run();
    $this->assertStringContainsString('Theme source theme olivero is not a valid starter kit.', trim($process->getErrorOutput()));
    $this->assertSame(1, $result);
  }

}
