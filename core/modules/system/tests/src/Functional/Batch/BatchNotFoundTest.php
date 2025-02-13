<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Batch;

use Drupal\Core\Batch\BatchStorageInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests if a page not found error is returned when a batch ID does not exist.
 *
 * @group Batch
 */
class BatchNotFoundTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['batch_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests for page not found error if batch ID does not exist.
   */
  public function testBatchNotFound(): void {

    $edit = ['batch' => 'batch0'];
    $this->drupalGet('batch-test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);

    $batch_id = \Drupal::service(BatchStorageInterface::class)->getId();

    $this->drupalGet('batch', [
      'query' => [
        'op' => 'start',
        'id' => $batch_id,
      ],
    ]);

    $this->assertSession()->statusCodeEquals(404);
  }

}
