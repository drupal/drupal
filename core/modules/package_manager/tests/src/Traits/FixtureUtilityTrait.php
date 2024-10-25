<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Traits;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;

/**
 * A utility for all things fixtures.
 *
 * @internal
 */
trait FixtureUtilityTrait {

  /**
   * Mirrors a fixture directory to the given path.
   *
   * Files not in the source fixture directory will not be deleted from
   * destination directory. After copying the files to the destination directory
   * the files and folders will be converted so that can be used in the tests.
   * The conversion includes:
   * - Renaming '_git' directories to '.git'
   * - Renaming files ending in '.info.yml.hide' to remove '.hide'.
   *
   * @param string $source_path
   *   The source path.
   * @param string $destination_path
   *   The path to which the fixture files should be mirrored.
   */
  protected static function copyFixtureFilesTo(string $source_path, string $destination_path): void {
    (new Filesystem())->mirror($source_path, $destination_path, NULL, [
      'override' => TRUE,
      'delete' => FALSE,
    ]);
    static::renameInfoYmlFiles($destination_path);
    static::renameGitDirectories($destination_path);
  }

  /**
   * Renames all files that end with .info.yml.hide.
   *
   * @param string $dir
   *   The directory to be iterated through.
   */
  protected static function renameInfoYmlFiles(string $dir): void {
    // Construct the iterator.
    $it = new RecursiveDirectoryIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);

    // Loop through files and rename them.
    foreach (new \RecursiveIteratorIterator($it) as $file) {
      if ($file->getExtension() == 'hide') {
        rename($file->getPathname(), $dir . DIRECTORY_SEPARATOR .
          $file->getRelativePath() . DIRECTORY_SEPARATOR . str_replace(".hide", "", $file->getFilename()));
      }
    }
  }

  /**
   * Renames _git directories to .git.
   *
   * @param string $dir
   *   The directory to be iterated through.
   */
  private static function renameGitDirectories(string $dir): void {
    $iter = new \RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::SELF_FIRST,
      \RecursiveIteratorIterator::CATCH_GET_CHILD
    );
    /** @var \Symfony\Component\Finder\SplFileInfo $file */
    foreach ($iter as $file) {
      if ($file->isDir() && $file->getFilename() === '_git' && $file->getRelativePathname()) {
        rename(
          $file->getPathname(),
          $file->getPath() . DIRECTORY_SEPARATOR . '.git'
        );
      }
    }
  }

}
