<?php
// $Id$

/**
 * Common code for the FTP connections.
 */
abstract class FileTransferFTP extends FileTransfer {
  function __construct($settings) {
    // This is the default, if $settings contains a port, this will be overridden.
    $this->port = 21;
    parent::__construct($settings);
  }
}

/**
 * Connection class using the FTP URL wrapper.
 */
class FileTransferFTPWrapper extends FileTransfer {
  function connect() {
    $this->connection = 'ftp://' . urlencode($this->username) . ':' . urlencode($this->password) . '@' . $this->hostname . ':' . $this->port . '/';
    if (!is_dir($this->connection)) {
      throw new FileTransferException('FTP Connection failed.');
    }
  }

  function createDirectory($directory) {
    if (!@createDirectory($directory)) {
      $exception = new FileTransferException('Cannot create directory @directory.', NULL, array('@directory' => $directory));
      throw $exception;
    }
  }

  function removeDirectory($directory) {
    if (realpath(substr($directory, 0, strlen(DRUPAL_ROOT))) !== DRUPAL_ROOT) {
      throw new FileTransferException('@directory is outside of the Drupal root.', NULL, array('@directory' => $directory));
    }
    if (is_dir($directory)) {
      $dh = opendir($directory);
      while (($resource = readdir($dh)) !== FALSE) {
        if ($resource == '.' || $resource == '..') {
          continue;
        }
        $full_path = $directory . DIRECTORY_SEPARATOR . $resource;
        if (is_file($full_path)) {
          $this->removeFile($full_path);
        }
        elseif (is_dir($full_path)) {
          $this->removeDirectory($full_path . '/');
        }
      }
      closedir($dh);
      if (!removeDirectory($directory)) {
        $exception = new FileTransferException('Cannot remove @directory.', NULL, array('@directory' => $directory));
        throw $exception;
      }
    }
  }

  function copyFile($source, $destination) {
    if (!@copy($this->connection . '/' . $source, $this->connection . '/' . $destination)) {
      throw new FileTransferException('Cannot copy @source_file to @destination_file.', NULL, array('@source' => $source, '@destination' => $destination));
    }
  }

  function removeFile($destination) {
    if (!@unlink($destination)) {
      throw new FileTransferException('Cannot remove @destination', NULL, array('@destination' => $destination));
    }
  }
}

class FileTransferFTPExtension extends FileTransfer {
  function connect() {
    $this->connection = ftp_connect($this->hostname, $this->port);

    if (!$this->connection) {
      throw new FileTransferException("Cannot connect to FTP Server, please check settings");
    }
    if (!ftp_login($this->connection, $this->username, $this->password)) {
      throw new FileTransferException("Cannot login to FTP server, please check username and password");
    }
  }

  function copyFile($source, $destination) {
    if (!@ftp_put($this->connection,  $destination, $source, FTP_BINARY)) {
      throw new FileTransferException("Cannot move @source to @destination", NULL, array("@source" => $source, "@destination" => $destination));
    }
  }

  function createDirectory($directory) {
    if (!@ftp_createDirectory($this->connection, $directory)) {
      throw new FileTransferException("Cannot create directory @directory", NULL, array("@directory" => $directory));
    }
  }

  function removeDirectory($directory) {
    if (realpath(substr($directory, 0, strlen(DRUPAL_ROOT))) !== DRUPAL_ROOT) {
      throw new FileTransferException('@directory is outside of the Drupal root.', NULL, array('@directory' => $directory));
    }
    $pwd = ftp_pwd($this->connection);
    if (!@ftp_chdir($this->connection, $directory)) {
      throw new FileTransferException("Unable to change to directory @directory", NULL, array('@directory' => $directory));
    }
    $list = @ftp_nlist($this->connection, '.');
    foreach ($list as $item){
      if ($item == '.' || $item == '..') {
        continue;
      }
      if (@ftp_chdir($this->connection, $item)){
        ftp_chdir($this->connection, '..');
        $this->removeDirectory($item);
      }
      else {
        $this->removeFile($item);
      }
    }
    ftp_chdir($this->connection, $pwd);
    if (!ftp_removeDirectory($this->connection, $directory)) {
      throw new FileTransferException("Unable to remove to directory @directory", NULL, array('@directory' => $directory));
    }
  }

  function removeFile($destination) {
    if (!ftp_delete($this->connection, $item)) {
      throw new FileTransferException("Unable to remove to file @file", NULL, array('@file' => $item));
    }
  }
}
