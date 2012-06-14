<?php

/**
 * @file
 * Definition of Drupal\system\Tests\FileTransfer\MockTestConnection.
 */

namespace Drupal\system\Tests\FileTransfer;

/**
 * Mock connection object for test case.
 */
class MockTestConnection {

  var $commandsRun = array();
  var $connectionString;

  function run($cmd) {
    $this->commandsRun[] = $cmd;
  }

  function flushCommands() {
    $out = $this->commandsRun;
    $this->commandsRun = array();
    return $out;
  }
}
