<?php

namespace Drupal\Tests\migrate\Unit\process;
use Drupal\migrate\Plugin\migrate\process\Flatten;


/**
 * Tests the flatten plugin.
 *
 * @group migrate
 */
class FlattenTest extends MigrateProcessTestCase {

  /**
   * Test that various array flatten operations work properly.
   */
  public function testFlatten() {
    $plugin = new Flatten([], 'flatten', []);
    $flattened = $plugin->transform([1, 2, [3, 4, [5]], [], [7, 8]], $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($flattened, [1, 2, 3, 4, 5, 7, 8]);
  }

}
