<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\FileTransfer;

/**
 * Mock connection object for test case.
 */
class MockTestConnection {

  /**
   * The commands to run.
   *
   * @var array
   */
  protected $commandsRun = [];

  /**
   * The database connection.
   *
   * @var string
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
