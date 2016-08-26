<?php

namespace Drupal\Tests\dblog\Kernel;

use Drupal\dblog\Controller\DbLogController;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for the DbLogController class.
 *
 * @group dblog
 */
class DbLogControllerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['dblog', 'user'];

  /**
   * Tests corrupted log entries can still display available data.
   */
  public function testDbLogCorrupted() {
    $this->installEntitySchema('user');
    $dblog_controller = DbLogController::create($this->container);

    // Check message with properly serialized data.
    $message = (object) [
      'message' => 'Sample message with placeholder: @placeholder',
      'variables' => serialize(['@placeholder' => 'test placeholder']),
    ];

    $this->assertEquals('Sample message with placeholder: test placeholder', $dblog_controller->formatMessage($message));

    // Check that controller work with corrupted data.
    $message->variables = 'BAD SERIALIZED DATA';
    $formatted = $dblog_controller->formatMessage($message);
    $this->assertEquals('Log data is corrupted and cannot be unserialized: Sample message with placeholder: @placeholder', $formatted);
  }

}
