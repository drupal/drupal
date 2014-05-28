<?php
/**
 * @file
 * Contains \Drupal\migrate\Tests\process\FlattenTest.
 */

namespace Drupal\migrate\Tests\process;
use Drupal\migrate\Plugin\migrate\process\Flatten;


/**
 * Tests the flatten plugin.
 *
 * @group Drupal
 * @group migrate
 */
class FlattenTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Flatten process plugin',
      'description' => 'Tests the flatten process plugin.',
      'group' => 'Migrate',
    );
  }

  /**
   * Test that various array flatten operations work properly.
   */
  public function testFlatten() {
    $plugin = new Flatten(array(), 'flatten', array());
    $flattened = $plugin->transform(array(1, 2, array(3, 4, array(5)), array(), array(7, 8)), $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($flattened, array(1, 2, 3, 4, 5, 7, 8));
  }

}
