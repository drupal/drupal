<?php

namespace Drupal\Composer\Plugin\Scaffold\Operations;

use Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath;

/**
 * Provides default behaviors for operations.
 *
 * @internal
 */
abstract class AbstractOperation implements OperationInterface {

  /**
   * Cached contents of scaffold file to be written to disk.
   *
   * @var string
   */
  protected $contents;

  /**
   * {@inheritdoc}
   */
  final public function contents() {
    if (!isset($this->contents)) {
      $this->contents = $this->generateContents();
    }
    return $this->contents;
  }

  /**
   * Load the scaffold contents or otherwise generate what is needed.
   *
   * @return string
   *   The contents of the scaffold file.
   */
  abstract protected function generateContents();

  /**
   * {@inheritdoc}
   */
  public function scaffoldOverExistingTarget(OperationInterface $existing_target) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function scaffoldAtNewLocation(ScaffoldFilePath $destination) {
    return $this;
  }

  /**
   * Adds the owner write permission bit to the path.
   *
   * @param string $filepath
   *   Path to the file or directory.
   *
   * @return bool
   *   TRUE on success, FALSE if the path does not exist or chmod failed.
   */
  protected function makeWritable(string $filepath): bool {
    if (!file_exists($filepath)) {
      return FALSE;
    }
    $mod = fileperms($filepath);
    if ($mod === FALSE) {
      return FALSE;
    }
    return @chmod($filepath, ($mod & 0777) | 0200);
  }

}
