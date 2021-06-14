<?php

namespace Drupal\KernelTests\Core\Batch;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests batch functionality.
 *
 * @group Batch
 */
class BatchKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    require_once $this->root . '/core/includes/batch.inc';
  }

  /**
   * Tests _batch_needs_update().
   */
  public function testNeedsUpdate() {
    // Before ever being called, the return value should be FALSE.
    $this->assertEquals(FALSE, _batch_needs_update());

    // Set the value to TRUE.
    $this->assertEquals(TRUE, _batch_needs_update(TRUE));
    // Check that without a parameter TRUE is returned.
    $this->assertEquals(TRUE, _batch_needs_update());

    // Set the value to FALSE.
    $this->assertEquals(FALSE, _batch_needs_update(FALSE));
    $this->assertEquals(FALSE, _batch_needs_update());
  }

}
