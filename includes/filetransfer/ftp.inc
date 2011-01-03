<?php
// $Id: ftp.inc,v 1.16 2011/01/03 02:20:45 webchick Exp $

/**
 * Base class for FTP implementations.
 */
abstract class FileTransferFTP extends FileTransfer {

  public function __construct($jail, $username, $password, $hostname, $port) {
    $this->username = $username;
    $this->password = $password;
    $this->hostname = $hostname;
    $this->port = $port;
    parent::__construct($jail);
  }

  /**
   * Return an object which can implement the FTP protocol.
   *
   * @param string $jail
   * @param array $settings
   * @return FileTransferFTP
   *   The appropriate FileTransferFTP subclass based on the available
   *   options. If the FTP PHP extension is available, use it.
   */
  static function factory($jail, $settings) {
    $username = empty($settings['username']) ? '' : $settings['username'];
    $password = empty($settings['password']) ? '' : $settings['password'];
    $hostname = empty($settings['advanced']['hostname']) ? 'localhost' : $settings['advanced']['hostname'];
    $port = empty($settings['advanced']['port']) ? 21 : $settings['advanced']['port'];

    if (function_exists('ftp_connect')) {
      $class = 'FileTransferFTPExtension';
    }
    else {
      throw new FileTransferException('No FTP backend available.');
    }

    return new $class($jail, $username, $password, $hostname, $port);
  }

  /**
   * Returns the form to configure the FileTransfer class for FTP.
   */
  public function getSettingsForm() {
    $form = parent::getSettingsForm();
    $form['advanced']['port']['#default_value'] = 21;
    return $form;
  }
}

class FileTransferFTPExtension extends FileTransferFTP implements FileTransferChmodInterface {

  public function connect() {
    $this->connection = ftp_connect($this->hostname, $this->port);

    if (!$this->connection) {
      throw new FileTransferException("Cannot connect to FTP Server, check settings");
    }
    if (!ftp_login($this->connection, $this->username, $this->password)) {
      throw new FileTransferException("Cannot log in to FTP server. Check username and password");
    }
  }

  protected function copyFileJailed($source, $destination) {
    if (!@ftp_put($this->connection,  $destination, $source, FTP_BINARY)) {
      throw new FileTransferException("Cannot move @source to @destination", NULL, array("@source" => $source, "@destination" => $destination));
    }
  }

  protected function createDirectoryJailed($directory) {
    if (!ftp_mkdir($this->connection, $directory)) {
      throw new FileTransferException("Cannot create directory @directory", NULL, array("@directory" => $directory));
    }
  }

  protected function removeDirectoryJailed($directory) {
    $pwd = ftp_pwd($this->connection);
    if (!ftp_chdir($this->connection, $directory)) {
      throw new FileTransferException("Unable to change to directory @directory", NULL, array('@directory' => $directory));
    }
    $list = @ftp_nlist($this->connection, '.');
    if (!$list) {
      $list = array();
    }
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
    if (@ftp_chdir($this->connection, $path)) {
      $result = TRUE;
    }
    ftp_chdir($this->connection, $curr);
    return $result;
  }

  public function isFile($path) {
    return ftp_size($this->connection, $path) != -1;
  }

  function chmodJailed($path, $mode, $recursive) {
    if (!ftp_chmod($this->connection, $mode, $path)) {
      throw new FileTransferException("Unable to set permissions on %file", NULL, array ('%file' => $path));
    }
    if ($this->isDirectory($path) && $recursive) {
      $filelist = @ftp_nlist($this->connection, $path);
      if (!$filelist) {
        //empty directory - returns false
        return;
      }
      foreach ($filelist as $file) {
        $this->chmodJailed($file, $mode, $recursive);
      }
    }
  }
}

if (!function_exists('ftp_chmod')) {
  function ftp_chmod($ftp_stream, $mode, $filename) {
    return ftp_site($ftp_stream, sprintf('CHMOD %o %s', $mode, $filename));
  }
}
