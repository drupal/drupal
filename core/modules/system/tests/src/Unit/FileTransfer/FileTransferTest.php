<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Unit\FileTransfer;

use Drupal\Tests\system\Functional\FileTransfer\MockTestConnection;
use Drupal\Tests\system\Functional\FileTransfer\TestFileTransfer;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for the file transfer system.
 *
 * @group FileTransfer
 * @group legacy
 */
class FileTransferTest extends UnitTestCase {

  /**
   * The test file transfer object.
   *
   * @var \Drupal\Tests\system\Functional\FileTransfer\TestFileTransfer
   */
  protected TestFileTransfer $testConnection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->testConnection = TestFileTransfer::factory($this->root, []);
  }

  /**
   * Tests the value returned by __get().
   */
  public function testFileTransferMagicMethods(): void {
    // Test to ensure __get() preserves public access.
    $this->assertInstanceOf(MockTestConnection::class, $this->testConnection->connection);
  }

}
