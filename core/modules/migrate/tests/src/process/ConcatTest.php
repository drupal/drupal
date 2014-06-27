<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\process\ConcatTest.
 */

namespace Drupal\migrate\Tests\process;

use Drupal\migrate\Plugin\migrate\process\Concat;

/**
 * Tests the concat process plugin.
 *
 * @group migrate
 * @group Drupal
 */
class ConcatTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Concat process plugin',
      'description' => 'Tests the concat process plugin.',
      'group' => 'Migrate',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->plugin = new TestConcat();
    parent::setUp();
  }

  /**
   * Test concat works without a delimiter.
   */
  public function testConcatWithoutDelimiter() {
    $value = $this->plugin->transform(array('foo', 'bar'), $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, 'foobar');
  }

  /**
   * Test concat fails properly on non-arrays.
   *
   * @expectedException \Drupal\migrate\MigrateException
   */
  public function testConcatWithNonArray() {
    $this->plugin->transform('foo', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Test concat works without a delimiter.
   */
  public function testConcatWithDelimiter() {
    $this->plugin->setDelimiter('_');
    $value = $this->plugin->transform(array('foo', 'bar'), $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, 'foo_bar');
  }
}

class TestConcat extends Concat {
  public function __construct() {
  }

  /**
   * Set the delimiter.
   *
   * @param string $delimiter
   *   The new delimiter.
   */
  public function setDelimiter($delimiter) {
    $this->configuration['delimiter'] = $delimiter;
  }

}
