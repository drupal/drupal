<?php

/**
 * @file
 * Contains \Drupal\Core\FileTransfer\SSH.
 */

namespace Drupal\Core\FileTransfer;

/**
 * The SSH connection class for the update module.
 */
class SSH extends FileTransfer implements ChmodInterface {

  /**
   * Overrides Drupal\Core\FileTransfer\FileTransfer::__construct().
   */
  function __construct($jail, $username, $password, $hostname = "localhost", $port = 22) {
    $this->username = $username;
    $this->password = $password;
    $this->hostname = $hostname;
    $this->port = $port;
    parent::__construct($jail);
  }

  /**
   * Implements Drupal\Core\FileTransfer\FileTransfer::connect().
   */
  public function connect() {
    $this->connection = @ssh2_connect($this->hostname, $this->port);
    if (!$this->connection) {
      throw new FileTransferException('SSH Connection failed to @host:@port', NULL, array('@host' => $this->hostname, '@port' => $this->port));
    }
    if (!@ssh2_auth_password($this->connection, $this->username, $this->password)) {
      throw new FileTransferException('The supplied username/password combination was not accepted.');
    }
  }

  /**
   * Overrides Drupal\Core\FileTransfer\FileTransfer::factory().
   */
  static function factory($jail, $settings) {
    $username = empty($settings['username']) ? '' : $settings['username'];
    $password = empty($settings['password']) ? '' : $settings['password'];
    $hostname = empty($settings['advanced']['hostname']) ? 'localhost' : $settings['advanced']['hostname'];
    $port = empty($settings['advanced']['port']) ? 22 : $settings['advanced']['port'];
    return new SSH($jail, $username, $password, $hostname, $port);
  }

  /**
   * Implements Drupal\Core\FileTransfer\FileTransfer::copyFileJailed().
   */
  protected function copyFileJailed($source, $destination) {
    if (!@ssh2_scp_send($this->connection, $source, $destination)) {
      throw new FileTransferException('Cannot copy @source_file to @destination_file.', NULL, array('@source' => $source, '@destination' => $destination));
    }
  }

  /**
   * Implements Drupal\Core\FileTransfer\FileTransfer::copyDirectoryJailed().
   */
  protected function copyDirectoryJailed($source, $destination) {
    if (@!ssh2_exec($this->connection, 'cp -Rp ' . escapeshellarg($source) . ' ' . escapeshellarg($destination))) {
      throw new FileTransferException('Cannot copy directory @directory.', NULL, array('@directory' => $source));
    }
  }

  /**
   * Implements Drupal\Core\FileTransfer\FileTransfer::createDirectoryJailed().
   */
  protected function createDirectoryJailed($directory) {
    if (@!ssh2_exec($this->connection, 'mkdir ' . escapeshellarg($directory))) {
      throw new FileTransferException('Cannot create directory @directory.', NULL, array('@directory' => $directory));
    }
  }

  /**
   * Implements Drupal\Core\FileTransfer\FileTransfer::removeDirectoryJailed().
   */
  protected function removeDirectoryJailed($directory) {
    if (@!ssh2_exec($this->connection, 'rm -Rf ' . escapeshellarg($directory))) {
      throw new FileTransferException('Cannot remove @directory.', NULL, array('@directory' => $directory));
    }
  }

  /**
   * Implements Drupal\Core\FileTransfer\FileTransfer::removeFileJailed().
   */
  protected function removeFileJailed($destination) {
    if (!@ssh2_exec($this->connection, 'rm ' . escapeshellarg($destination))) {
      throw new FileTransferException('Cannot remove @directory.', NULL, array('@directory' => $destination));
    }
  }

  /**
   * Implements Drupal\Core\FileTransfer\FileTransfer::isDirectory().
   *
   * WARNING: This is untested. It is not currently used, but should do the
   * trick.
   */
  public function isDirectory($path) {
    $directory = escapeshellarg($path);
    $cmd = "[ -d {$directory} ] && echo 'yes'";
    if ($output = @ssh2_exec($this->connection, $cmd)) {
      if ($output == 'yes') {
        return TRUE;
      }
      return FALSE;
    }
    else {
      throw new FileTransferException('Cannot check @path.', NULL, array('@path' => $path));
    }
  }

  /**
   * Implements Drupal\Core\FileTransfer\FileTransfer::isFile().
   */
  public function isFile($path) {
    $file = escapeshellarg($path);
    $cmd = "[ -f {$file} ] && echo 'yes'";
    if ($output = @ssh2_exec($this->connection, $cmd)) {
      if ($output == 'yes') {
        return TRUE;
      }
      return FALSE;
    }
    else {
      throw new FileTransferException('Cannot check @path.', NULL, array('@path' => $path));
    }
  }

  /**
   * Implements Drupal\Core\FileTransfer\ChmodInterface::chmodJailed().
   */
  function chmodJailed($path, $mode, $recursive) {
    $cmd = sprintf("chmod %s%o %s", $recursive ? '-R ' : '', $mode, escapeshellarg($path));
    if (@!ssh2_exec($this->connection, $cmd)) {
      throw new FileTransferException('Cannot change permissions of @path.', NULL, array('@path' => $path));
    }
  }

  /**
   * Overrides Drupal\Core\FileTransfer\FileTransfer::getSettingsForm().
   */
  public function getSettingsForm() {
    $form = parent::getSettingsForm();
    $form['advanced']['port']['#default_value'] = 22;
    return $form;
  }
}
