<?php
// $Id: local.inc,v 1.4 2010/08/17 22:05:22 dries Exp $

/**
 * The local connection class for copying files as the httpd user.
 */
class FileTransferLocal extends FileTransfer implements FileTransferChmodInterface {

  function connect() {
    // No-op
  }

  static function factory($jail, $settings) {
    return new FileTransferLocal($jail);
  }

  protected function copyFileJailed($source, $destination) {
    if (@!copy($source, $destination)) {
      throw new FileTransferException('Cannot copy %source to %destination.', NULL, array('%source' => $source, '%destination' => $destination));
    }
  }

  protected function createDirectoryJailed($directory) {
    if (!is_dir($directory) && @!mkdir($directory, 0777, TRUE)) {
      throw new FileTransferException('Cannot create directory %directory.', NULL, array('%directory' => $directory));
    }
  }

  protected function removeDirectoryJailed($directory) {
    if (!is_dir($directory)) {
      // Programmer error assertion, not something we expect users to see.
      throw new FileTransferException('removeDirectoryJailed() called with a path (%directory) that is not a directory.', NULL, array('%directory' => $directory));
    }
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory), RecursiveIteratorIterator::CHILD_FIRST) as $filename => $file) {
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

  protected function removeFileJailed($file) {
    if (@!drupal_unlink($file)) {
      throw new FileTransferException('Cannot remove file %file.', NULL, array('%file' => $file));
    }
  }

  public function isDirectory($path) {
    return is_dir($path);
  }

  public function isFile($path) {
    return is_file($path);
  }

  public function chmodJailed($path, $mode, $recursive) {
    if ($recursive && is_dir($path)) {
      foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST) as $filename => $file) {
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
