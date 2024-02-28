<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\FileTransfer;

/**
 * Mock connection object for test case.
 */
class MockTestConnection {

  protected $commandsRun = [];
  public $connectionString;

  public function run($cmd) {
    $this->commandsRun[] = $cmd;
  }

  public function flushCommands() {
    $out = $this->commandsRun;
    $this->commandsRun = [];
    return $out;
  }

}
