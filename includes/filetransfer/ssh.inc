<?php
// $Id$

/**
 * The SSH connection class for the update module.
 */
class FileTransferSSH extends FileTransfer {

  function __construct($jail, $username, $password, $hostname = "localhost", $port = 22) {
    parent::__construct($jail, $username, $password, $hostname, $port);
  }

  function connect() {
    $this->connection = @ssh2_connect($this->hostname, $this->port);
    if (!$this->connection) {
      throw new FileTransferException('SSH Connection failed to @host:@port', NULL, array('@host' => $this->hostname, '@port' => 21));
    }
    if (!@ssh2_auth_password($this->connection, $this->username, $this->password)) {
      throw new FileTransferException('The supplied username/password combination was not accepted.');
    }
  }

  static function factory($jail, $settings) {
    $settings['hostname'] = empty($settings['hostname']) ? 'localhost' : $settings['hostname'];
    $settings['port'] = empty($settings['port']) ? 22 : $settings['port'];
    return new FileTransferSSH($jail, $settings['username'], $settings['password'], $settings['hostname'], $settings['port']);
  }

  protected function copyFileJailed($source, $destination) {
    if (!@ssh2_scp_send($this->connection, $source, $destination)) {
      throw new FileTransferException('Cannot copy @source_file to @destination_file.', NULL, array('@source' => $source, '@destination' => $destination));
    }
  }

  protected function copyDirectoryJailed($source, $destination) {
    if (@!ssh2_exec($this->connection, 'cp -Rp ' . escapeshellarg($source) . ' ' . escapeshellarg($destination))) {
      throw new FileTransferException('Cannot copy directory @directory.', NULL, array('@directory' => $source));
    }
  }

  protected function createDirectoryJailed($directory) {
    if (@!ssh2_exec($this->connection, 'mkdir ' . escapeshellarg($directory))) {
      throw new FileTransferException('Cannot create directory @directory.', NULL, array('@directory' => $directory));
    }
  }

  protected function removeDirectoryJailed($directory) {
    if (@!ssh2_exec($this->connection, 'rm -Rf ' . escapeshellarg($directory))) {
      throw new FileTransferException('Cannot remove @directory.', NULL, array('@directory' => $directory));
    }
  }

  protected function removeFileJailed($destination) {
    if (!@ssh2_exec($this->connection, 'rm ' . escapeshellarg($destination))) {
      throw new FileTransferException('Cannot remove @directory.', NULL, array('@directory' => $destination));
    }
  }
  
  /**
   * WARNING: This is untested.  It is not currently used, but should do the trick.
   */
  public function isDirectory($path) {
    $directory = escapeshellarg($path);
    $cmd = "[ -d {$directory} ] && echo 'yes'";
    if ($output = @ssh2_exec($this->connection, $cmd)) {
      if ($output == 'yes') {
        return TRUE;
      }
      return FALSE;
    } else {
      throw new FileTransferException('Cannot check @path.', NULL, array('@path' => $path));
    }
  }
}
