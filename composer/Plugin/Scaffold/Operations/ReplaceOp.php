<?php

namespace Drupal\Composer\Plugin\Scaffold\Operations;

use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath;
use Drupal\Composer\Plugin\Scaffold\ScaffoldOptions;

/**
 * Scaffold operation to copy or symlink from source to destination.
 *
 * @internal
 */
class ReplaceOp extends AbstractOperation {

  /**
   * Identifies Replace operations.
   */
  const ID = 'replace';

  /**
   * The relative path to the source file.
   *
   * @var \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath
   */
  protected $source;

  /**
   * Whether to overwrite existing files.
   *
   * @var bool
   */
  protected $overwrite;

  /**
   * Constructs a ReplaceOp.
   *
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath $sourcePath
   *   The relative path to the source file.
   * @param bool $overwrite
   *   Whether to allow this scaffold file to overwrite files already at
   *   the destination. Defaults to TRUE.
   */
  public function __construct(ScaffoldFilePath $sourcePath, $overwrite = TRUE) {
    $this->source = $sourcePath;
    $this->overwrite = $overwrite;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateContents() {
    return file_get_contents($this->source->fullPath());
  }

  /**
   * {@inheritdoc}
   */
  public function process(ScaffoldFilePath $destination, IOInterface $io, ScaffoldOptions $options) {
    $fs = new Filesystem();
    $destination_path = $destination->fullPath();
    $interpolator = $destination->getInterpolator();
    // Do nothing if overwrite is 'false' and a file already exists at the
    // destination.
    if ($this->overwrite === FALSE && file_exists($destination_path)) {
      $io->write($interpolator->interpolate("  - Skip <info>[dest-rel-path]</info> because it already exists and overwrite is <comment>false</comment>."));
      return new ScaffoldResult($destination, FALSE);
    }

    $destination_directory = dirname($destination_path);
    $reset_directory_perms = FALSE;
    // Capture perms before any changes. NULL means the path does not exist yet.
    $original_directory_perms = file_exists($destination_directory) ? (fileperms($destination_directory) ?: NULL) : NULL;
    $original_file_perms = file_exists($destination_path) ? (fileperms($destination_path) ?: NULL) : NULL;

    if ($original_directory_perms !== NULL && !is_writable($destination_directory)) {
      if (!$this->makeWritable($destination_directory)) {
        throw new \RuntimeException($interpolator->interpolate("Failed to make the directory containing <info>[dest-rel-path]</info> writable."));
      }
      $reset_directory_perms = TRUE;
    }

    try {
      // Remove the destination if it exists,
      // and ensure the destination directory exists.
      $fs->remove($destination_path);
      $fs->ensureDirectoryExists($destination_directory);

      if ($options->symlink()) {
        return $this->symlinkScaffold($destination, $io);
      }

      return $this->copyScaffold($destination, $io);
    }
    finally {
      if ($reset_directory_perms && $original_directory_perms !== NULL) {
        @chmod($destination_directory, $original_directory_perms);
      }
      if ($original_file_perms !== NULL && !$options->symlink() && file_exists($destination_path)) {
        @chmod($destination_path, $original_file_perms);
      }
    }
  }

  /**
   * Copies the scaffold file.
   *
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath $destination
   *   Scaffold file to process.
   * @param \Composer\IO\IOInterface $io
   *   IOInterface to writing to.
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Operations\ScaffoldResult
   *   The scaffold result.
   */
  protected function copyScaffold(ScaffoldFilePath $destination, IOInterface $io) {
    $interpolator = $destination->getInterpolator();
    $this->source->addInterpolationData($interpolator);
    if (file_put_contents($destination->fullPath(), $this->contents()) === FALSE) {
      throw new \RuntimeException($interpolator->interpolate("Could not copy source file <info>[src-rel-path]</info> to <info>[dest-rel-path]</info>!"));
    }
    $io->write($interpolator->interpolate("  - Copy <info>[dest-rel-path]</info> from <info>[src-rel-path]</info>"));
    return new ScaffoldResult($destination, $this->overwrite);
  }

  /**
   * Symlinks the scaffold file.
   *
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath $destination
   *   Scaffold file to process.
   * @param \Composer\IO\IOInterface $io
   *   IOInterface to writing to.
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Operations\ScaffoldResult
   *   The scaffold result.
   */
  protected function symlinkScaffold(ScaffoldFilePath $destination, IOInterface $io) {
    $interpolator = $destination->getInterpolator();
    try {
      $fs = new Filesystem();
      $fs->relativeSymlink($this->source->fullPath(), $destination->fullPath());
    }
    catch (\Exception $e) {
      throw new \RuntimeException($interpolator->interpolate("Could not symlink source file <info>[src-rel-path]</info> to <info>[dest-rel-path]</info>!"), [], $e);
    }
    $io->write($interpolator->interpolate("  - Link <info>[dest-rel-path]</info> from <info>[src-rel-path]</info>"));
    return new ScaffoldResult($destination, $this->overwrite);
  }

}
