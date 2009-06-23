<?php
// $Id$

/**
 * The SSH connection class for the update module.
 */
class FileTransferSSH extends FileTransfer {

  function __construct($settings) {
    // This is the default, if $settings contains a port, this will be overridden.
    $this->port = 22;
    parent::__construct($settings);
  }

  function connect() {
    $this->connection = @ssh2_connect($setings['hostname'], $this->port);
    if (!$this->connection) {
      throw new FileTransferException('SSH Connection failed.');
    }
    if (!@ssh2_auth_password($this->connection, $this->username, $this->password)) {
      throw new FileTransferException('The supplied username/password combination was not accepted.');
    }
  }

  function copyFile($source, $destination) {
    if (!@ssh2_scp_send($this->connection, $source, $destination)) {
      throw new FileTransferException('Cannot copy @source_file to @destination_file.', NULL, array('@source' => $source, '@destination' => $destination));
    }
  }

  function copyDirectory($source, $destination) {
    if (!@ssh2_exec($this->connection, 'cp -Rp ' . escapeshellarg($source) . ' ' . escapeshellarg($destination))) {
      throw new FileTransferException('Cannot copy directory @directory.', NULL, array('@directory' => $source));
    }
  }

  function createDirectory($directory) {
    if (!@ssh2_exec($this->connection, 'mkdir ' . escapeshellarg($directory))) {
      throw new FileTransferException('Cannot create directory @directory.', NULL, array('@directory' => $directory));
    }
  }

  function removeDirectory($directory) {
    if (realpath(substr($directory, 0, strlen(DRUPAL_ROOT))) !== DRUPAL_ROOT) {
      throw new FileTransferException('@directory is outside of the Drupal root.', NULL, array('@directory' => $directory));
    }
    if (!@ssh2_exec($this->connection, 'rm -Rf ' . escapeshellarg($directory))) {
      throw new FileTransferException('Cannot remove @directory.', NULL, array('@directory' => $directory));
    }
  }
  
  function removeFile($destination) {
    if (!@ssh2_exec($this->connection, 'rm ' . escapeshellarg($destination))) {
      throw new FileTransferException('Cannot remove @directory.', NULL, array('@directory' => $destination));
    }
  }
}
