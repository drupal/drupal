<?php

namespace Drupal\Core\FileTransfer;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\File\FileSystemInterface;

/**
 * Defines the local connection class for copying files as the httpd user.
 */
class Local extends FileTransfer implements ChmodInterface {

  use DependencySerializationTrait;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct($jail, FileSystemInterface $file_system) {
    parent::__construct($jail);
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function connect() {
    // No-op
  }

  /**
   * {@inheritdoc}
   */
  public static function factory($jail, $settings) {
    return new Local($jail, \Drupal::service('file_system'));
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFileJailed($source, $destination) {
    if (@!copy($source, $destination)) {
      throw new FileTransferException('Cannot copy %source to %destination.', NULL, ['%source' => $source, '%destination' => $destination]);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createDirectoryJailed($directory) {
    if (!is_dir($directory) && @!mkdir($directory, 0777, TRUE)) {
      throw new FileTransferException('Cannot create directory %directory.', NULL, ['%directory' => $directory]);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function removeDirectoryJailed($directory) {
    if (!is_dir($directory)) {
      // Programmer error assertion, not something we expect users to see.
      throw new FileTransferException('removeDirectoryJailed() called with a path (%directory) that is not a directory.', NULL, ['%directory' => $directory]);
    }
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $filename => $file) {
      if ($file->isDir()) {
        if (@!$file_system->rmdir($filename)) {
          throw new FileTransferException('Cannot remove directory %directory.', NULL, ['%directory' => $filename]);
        }
      }
      elseif ($file->isFile()) {
        if (@!$this->fileSystem->unlink($filename)) {
          throw new FileTransferException('Cannot remove file %file.', NULL, ['%file' => $filename]);
        }
      }
    }
    if (@!$file_system->rmdir($directory)) {
      throw new FileTransferException('Cannot remove directory %directory.', NULL, ['%directory' => $directory]);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function removeFileJailed($file) {
    if (@!$this->fileSystem->unlink($file)) {
      throw new FileTransferException('Cannot remove file %file.', NULL, ['%file' => $file]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isDirectory($path) {
    return is_dir($path);
  }

  /**
   * {@inheritdoc}
   */
  public function isFile($path) {
    return is_file($path);
  }

  /**
   * {@inheritdoc}
   */
  public function chmodJailed($path, $mode, $recursive) {
    if ($recursive && is_dir($path)) {
      foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $filename => $file) {
        if (@!chmod($filename, $mode)) {
          throw new FileTransferException('Cannot chmod %path.', NULL, ['%path' => $filename]);
        }
      }
    }
    elseif (@!chmod($path, $mode)) {
      throw new FileTransferException('Cannot chmod %path.', NULL, ['%path' => $path]);
    }
  }

}
