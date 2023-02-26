<?php

namespace Drupal\Tests\system\Unit\Batch;

use Drupal\Core\Batch\BatchStorageInterface;
use Drupal\system\Controller\BatchController;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests for the batch controller class.
 *
 * @coversDefaultClass \Drupal\system\Controller\BatchController
 * @runTestsInSeparateProcesses
 * @group system
 */
class BatchControllerTest extends UnitTestCase {

  /**
   * Tests title callback.
   *
   * @covers ::batchPageTitle
   */
  public function testBatchPageTitle() {
    $batch_storage = $this->createMock(BatchStorageInterface::class);
    $controller = new BatchController($this->root, $batch_storage);
    require_once $this->root . '/core/includes/form.inc';
    $this->assertSame('', $controller->batchPageTitle(new Request()));
    // Test no batch loaded from storage and batch loaded from storage cases.
    $batch = ['sets' => [['title' => 'foobar']], 'current_set' => 0];
    $batch_storage->method('load')->will($this->onConsecutiveCalls(FALSE, $batch));
    $this->assertSame('', $controller->batchPageTitle(new Request(['id' => 1234])));
    $this->assertSame('foobar', $controller->batchPageTitle(new Request(['id' => 1234])));
    // Test batch returned by &batch_get() call.
    $batch = &batch_get();
    $batch['sets']['0']['title'] = 'Updated title';
    $this->assertSame('Updated title', $controller->batchPageTitle(new Request(['id' => 1234])));
  }

}
