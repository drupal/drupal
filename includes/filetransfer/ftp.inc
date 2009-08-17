<?php
// $Id$

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
  
  static function factory($jail, $settings) {
    $settings['hostname'] = empty($settings['hostname']) ? 'localhost' : $settings['hostname'];
    $settings['port'] = empty($settings['port']) ? 21 : $settings['port'];
    return new FileTransferFTPWrapper($jail, $settings['username'], $settings['password'], $settings['hostname'], $settings['port']);
  }

  function createDirectoryJailed($directory) {
    if (!@drupal_mkdir($directory)) {
      $exception = new FileTransferException('Cannot create directory @directory.', NULL, array('@directory' => $directory));
      throw $exception;
    }
  }

  function removeDirectoryJailed($directory) {
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
      if (!rmdir($directory)) {
        $exception = new FileTransferException('Cannot remove @directory.', NULL, array('@directory' => $directory));
        throw $exception;
      }
    }
  }

  function copyFileJailed($source, $destination) {
    if (!@copy($this->connection . '/' . $source, $this->connection . '/' . $destination)) {
      throw new FileTransferException('Cannot copy @source_file to @destination_file.', NULL, array('@source' => $source, '@destination' => $destination));
    }
  }

  function removeFileJailed($destination) {
    if (!@unlink($destination)) {
      throw new FileTransferException('Cannot remove @destination', NULL, array('@destination' => $destination));
    }
  }
  
  function isDirectory($path) {
    return is_dir($this->connection . '/' . $path);
  }
}

class FileTransferFTPExtension extends FileTransfer {
  public function connect() {
    $this->connection = ftp_connect($this->hostname, $this->port);

    if (!$this->connection) {
      throw new FileTransferException("Cannot connect to FTP Server, please check settings");
    }
    if (!ftp_login($this->connection, $this->username, $this->password)) {
      throw new FileTransferException("Cannot login to FTP server, please check username and password");
    }
  }
  
  static function factory($jail, $settings) {
    $settings['hostname'] = empty($settings['hostname']) ? 'localhost' : $settings['hostname'];
    $settings['port'] = empty($settings['port']) ? 21 : $settings['port'];
    return new FileTransferFTPExtension($jail, $settings['username'], $settings['password'], $settings['hostname'], $settings['port']);
  }

  protected function copyFileJailed($source, $destination) {
    if (!@ftp_put($this->connection,  $destination, $source, FTP_BINARY)) {
      throw new FileTransferException("Cannot move @source to @destination", NULL, array("@source" => $source, "@destination" => $destination));
    }
  }

  protected function createDirectoryJailed($directory) {
    if (!@ftp_mkdir($this->connection, $directory)) {
      throw new FileTransferException("Cannot create directory @directory", NULL, array("@directory" => $directory));
    }
  }

  protected function removeDirectoryJailed($directory) {
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
        ftp_cdup($this->connection);
        $this->removeDirectory(ftp_pwd($this->connection) . '/' . $item);
      }
      else {
        $this->removeFile(ftp_pwd($this->connection) . '/' . $item);
      }
    }
    ftp_chdir($this->connection, $pwd);
    if (!ftp_rmdir($this->connection, $directory)) {
      throw new FileTransferException("Unable to remove to directory @directory", NULL, array('@directory' => $directory));
    }
  }

  protected function removeFileJailed($destination) {
    if (!ftp_delete($this->connection, $destination)) {
      throw new FileTransferException("Unable to remove to file @file", NULL, array('@file' => $destination));
    }
  }
  
  public function isDirectory($path) {
    $result = FALSE;
    $curr = ftp_pwd($this->connection);
    if (ftp_chdir($this->connection, $path)) {
      $result = TRUE;
    }
    ftp_chdir($this->connection, $curr);
    return $result;
  }
}
