#!/usr/bin/env php
<?php

/**
 * @file
 * A script that updates the package_manager test 'fake_site' fixture.
 */

declare(strict_types=1);

use Composer\Json\JsonFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

if (PHP_SAPI !== 'cli') {
  return;
}
// Bootstrap.
require __DIR__ . '/../../autoload.php';

PackageManagerFixtureCreator::createFixture();
/**
 * Creates fixture at 'core/modules/package_manager/tests/fixtures/fake_site'.
 */
final class PackageManagerFixtureCreator {

  private const FIXTURE_PATH = __DIR__ . '/../modules/package_manager/tests/fixtures/fake_site';

  private const CORE_ROOT_PATH = __DIR__ . '/../..';

  /**
   * Creates the fixture.
   */
  public static function createFixture(): void {
    // Copy drupal scaffold file mapping from core/composer.json to
    // fixtures' core/composer.json.
    $core_composer_json = new JsonFile(static::CORE_ROOT_PATH . '/core/composer.json');
    $core_composer_data = $core_composer_json->read();
    $fixture_core_composer_file = new JsonFile(static::FIXTURE_PATH . "/../path_repos/drupal--core/composer.json");
    $fixture_core_composer_data = $fixture_core_composer_file->read();
    $fixture_core_composer_data['extra']['drupal-scaffold']['file-mapping'] = $core_composer_data['extra']['drupal-scaffold']['file-mapping'];
    $fixture_core_composer_file->write($fixture_core_composer_data);

    $fixture_packages_json = new JsonFile(static::FIXTURE_PATH . '/packages.json');
    $fixture_packages_data = $fixture_packages_json->read();
    foreach ($fixture_packages_data['packages']['drupal/core'] as &$release) {
      $release['extra']['drupal-scaffold']['file-mapping'] = $core_composer_data['extra']['drupal-scaffold']['file-mapping'];
    }
    $fixture_packages_json->write($fixture_packages_data);

    $fs = new Filesystem();
    $fs->remove(static::FIXTURE_PATH . "/composer.lock");
    // Remove all the vendor folders but leave our 2 test files.
    // @see \Drupal\Tests\package_manager\Kernel\PathExcluder\VendorHardeningExcluderTest
    self::removeAllExcept(static::FIXTURE_PATH . "/vendor", ['.htaccess', 'web.config']);

    self::runComposerCommand(['install']);
    static::removeAllExcept(static::FIXTURE_PATH . '/vendor/composer', ['installed.json', 'installed.php']);
    $fs->remove(static::FIXTURE_PATH . '/vendor/autoload.php');
    print "\nFixture updated.\nRunning phpcbf";

    $process = new Process(['composer', 'phpcbf', static::FIXTURE_PATH], static::CORE_ROOT_PATH);
    $process->run();
    print "\nFixture created 🎉.";
  }

  /**
   * Runs a Composer command at the fixture root.
   *
   * @param array $command
   *   The command to run as passed to
   *   \Symfony\Component\Process\Process::__construct.
   *
   * @return string
   *   The Composer command output.
   */
  private static function runComposerCommand(array $command): string {
    array_unshift($command, 'composer');
    $command[] = "--working-dir=" . static::FIXTURE_PATH;
    $process = new Process($command, env: [
      'COMPOSER_MIRROR_PATH_REPOS' => '1',
    ]);
    $process->run();
    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }
    return $process->getOutput();
  }

  /**
   * Removes all files in a directory except the ones specified.
   *
   * @param string $directory
   *   The directory path.
   * @param string[] $files_to_keep
   *   The files to not delete.
   */
  private static function removeAllExcept(string $directory, array $files_to_keep): void {
    if (!is_dir($directory)) {
      throw new \LogicException("Expected directory $directory");
    }
    $paths_to_remove = glob("$directory/*");
    $fs = new Filesystem();
    foreach ($paths_to_remove as $path_to_remove) {
      $base_name = basename($path_to_remove);
      if (!in_array($base_name, $files_to_keep, TRUE)) {
        $fs->remove($path_to_remove);
      }
    }
  }

}
