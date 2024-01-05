<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\process\Concat;

/**
 * Tests the concat process plugin.
 *
 * @group migrate
 */
class ConcatTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->plugin = new TestConcat();
    parent::setUp();
  }

  /**
   * Tests concat works without a delimiter.
   */
  public function testConcatWithoutDelimiter() {
    $value = $this->plugin->transform(['foo', 'bar'], $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('foobar', $value);
  }

  /**
   * Tests concat fails properly on non-arrays.
   */
  public function testConcatWithNonArray() {
    $this->expectException(MigrateException::class);
    $this->plugin->transform('foo', $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Tests concat works without a delimiter.
   */
  public function testConcatWithDelimiter() {
    $this->plugin->setDelimiter('_');
    $value = $this->plugin->transform(['foo', 'bar'], $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('foo_bar', $value);
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
