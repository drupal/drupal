<?php

namespace Drupal\system\Tests\FileTransfer;

/**
 * Mock connection object for test case.
 */
class MockTestConnection {

  protected $commandsRun = array();
  public $connectionString;

  function run($cmd) {
    $this->commandsRun[] = $cmd;
  }

  function flushCommands() {
    $out = $this->commandsRun;
    $this->commandsRun = array();
    return $out;
  }
}
