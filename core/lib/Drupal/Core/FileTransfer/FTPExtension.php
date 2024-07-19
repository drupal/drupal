<?php

namespace Drupal\Core\FileTransfer;

/**
 * Defines a file transfer class using the PHP FTP extension.
 */
class FTPExtension extends FTP implements ChmodInterface {

  /**
   * {@inheritdoc}
   */
  public function connect() {
    $this->connection = ftp_connect($this->hostname, $this->port);

    if (!$this->connection) {
      throw new FileTransferException("Cannot connect to FTP Server, check settings");
    }
    if (!ftp_login($this->connection, $this->username, $this->password)) {
      throw new FileTransferException("Cannot log in to FTP server. Check username and password");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFileJailed($source, $destination) {
    if (!@ftp_put($this->connection, $destination, $source, FTP_BINARY)) {
      throw new FileTransferException("Cannot move @source to @destination", 0, ["@source" => $source, "@destination" => $destination]);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createDirectoryJailed($directory) {
    if (!ftp_mkdir($this->connection, $directory)) {
      throw new FileTransferException("Cannot create directory @directory", 0, ["@directory" => $directory]);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function removeDirectoryJailed($directory) {
    $pwd = ftp_pwd($this->connection);
    if (!ftp_chdir($this->connection, $directory)) {
      throw new FileTransferException("Unable to change the current directory to @directory", 0, ['@directory' => $directory]);
    }
    $list = @ftp_nlist($this->connection, '.');
    if (!$list) {
      $list = [];
    }
    foreach ($list as $item) {
      if ($item == '.' || $item == '..') {
        continue;
      }
      if (@ftp_chdir($this->connection, $item)) {
        ftp_cdup($this->connection);
        $this->removeDirectory(ftp_pwd($this->connection) . '/' . $item);
      }
      else {
        $this->removeFile(ftp_pwd($this->connection) . '/' . $item);
      }
    }
    ftp_chdir($this->connection, $pwd);
    if (!ftp_rmdir($this->connection, $directory)) {
      throw new FileTransferException("Unable to remove the directory @directory", 0, ['@directory' => $directory]);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function removeFileJailed($destination) {
    if (!ftp_delete($this->connection, $destination)) {
      throw new FileTransferException("Unable to remove the file @file", 0, ['@file' => $destination]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isDirectory($path) {
    $result = FALSE;
    $curr = ftp_pwd($this->connection);
    if (@ftp_chdir($this->connection, $path)) {
      $result = TRUE;
    }
    ftp_chdir($this->connection, $curr);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function isFile($path) {
    return ftp_size($this->connection, $path) != -1;
  }

  /**
   * {@inheritdoc}
   */
  public function chmodJailed($path, $mode, $recursive) {
    if (!ftp_chmod($this->connection, $mode, $path)) {
      throw new FileTransferException("Unable to set permissions on %file", 0, ['%file' => $path]);
    }
    if ($this->isDirectory($path) && $recursive) {
      $file_list = @ftp_nlist($this->connection, $path);
      if (!$file_list) {
        // Empty directory - returns false
        return;
      }
      foreach ($file_list as $file) {
        $this->chmodJailed($file, $mode, $recursive);
      }
    }
  }

}
