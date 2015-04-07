<?php

/**
 * @file
 * Definition of Drupal\Core\FileTransfer\Local.
 */

namespace Drupal\Core\FileTransfer;

/**
 * Defines the local connection class for copying files as the httpd user.
 */
class Local extends FileTransfer implements ChmodInterface {

  /**
   * Implements Drupal\Core\FileTransfer\FileTransfer::connect().
   */
  public function connect() {
    // No-op
  }

  /**
   * Overrides Drupal\Core\FileTransfer\FileTransfer::factory().
   */
  static function factory($jail, $settings) {
    return new Local($jail);
  }

  /**
   * Implements Drupal\Core\FileTransfer\FileTransfer::copyFileJailed().
   */
  protected function copyFileJailed($source, $destination) {
    if (@!copy($source, $destination)) {
      throw new FileTransferException('Cannot copy %source to %destination.', NULL, array('%source' => $source, '%destination' => $destination));
    }
  }

  /**
   * Implements Drupal\Core\FileTransfer\FileTransfer::createDirectoryJailed().
   */
  protected function createDirectoryJailed($directory) {
    if (!is_dir($directory) && @!mkdir($directory, 0777, TRUE)) {
      throw new FileTransferException('Cannot create directory %directory.', NULL, array('%directory' => $directory));
    }
  }

  /**
   * Implements Drupal\Core\FileTransfer\FileTransfer::removeDirectoryJailed().
   */
  protected function removeDirectoryJailed($directory) {
    if (!is_dir($directory)) {
      // Programmer error assertion, not something we expect users to see.
      throw new FileTransferException('removeDirectoryJailed() called with a path (%directory) that is not a directory.', NULL, array('%directory' => $directory));
    }
    foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $filename => $file) {
      if ($file->isDir()) {
        if (@!drupal_rmdir($filename)) {
          throw new FileTransferException('Cannot remove directory %directory.', NULL, array('%directory' => $filename));
        }
      }
      elseif ($file->isFile()) {
        if (@!drupal_unlink($filename)) {
          throw new FileTransferException('Cannot remove file %file.', NULL, array('%file' => $filename));
        }
      }
    }
    if (@!drupal_rmdir($directory)) {
      throw new FileTransferException('Cannot remove directory %directory.', NULL, array('%directory' => $directory));
    }
  }

  /**
   * Implements Drupal\Core\FileTransfer\FileTransfer::removeFileJailed().
   */
  protected function removeFileJailed($file) {
    if (@!drupal_unlink($file)) {
      throw new FileTransferException('Cannot remove file %file.', NULL, array('%file' => $file));
    }
  }

  /**
   * Implements Drupal\Core\FileTransfer\FileTransfer::isDirectory().
   */
  public function isDirectory($path) {
    return is_dir($path);
  }

  /**
   * Implements Drupal\Core\FileTransfer\FileTransfer::isFile().
   */
  public function isFile($path) {
    return is_file($path);
  }

  /**
   * Implements Drupal\Core\FileTransfer\ChmodInterface::chmodJailed().
   */
  public function chmodJailed($path, $mode, $recursive) {
    if ($recursive && is_dir($path)) {
      foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $filename => $file) {
        if (@!chmod($filename, $mode)) {
          throw new FileTransferException('Cannot chmod %path.', NULL, array('%path' => $filename));
        }
      }
    }
    elseif (@!chmod($path, $mode)) {
      throw new FileTransferException('Cannot chmod %path.', NULL, array('%path' => $path));
    }
  }
}
