<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\FileTransfer;

/**
 * Mock connection object for test case.
 */
class MockTestConnection {

  /**
   * The commands to run.
   */
  protected $commandsRun = [];

  /**
   * The database connection.
   */
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
