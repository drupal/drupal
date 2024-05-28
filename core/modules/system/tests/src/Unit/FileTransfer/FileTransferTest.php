<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Unit\FileTransfer;

use Drupal\Tests\system\Functional\FileTransfer\MockTestConnection;
use Drupal\Tests\system\Functional\FileTransfer\TestFileTransfer;
use Drupal\Tests\UnitTestCase;

/**
 * @group FileTransfer
 */
class FileTransferTest extends UnitTestCase {

  /**
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

  public function testFileTransferMagicMethods() {
    // Test to ensure __get() preserves public access.
    $this->assertInstanceOf(MockTestConnection::class, $this->testConnection->connection);
  }

}
