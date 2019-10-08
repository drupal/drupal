<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests deprecated features of the migration lookup plugin.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\MigrationLookup
 *
 * @group legacy
 */
class LegacyMigrationLookupTest extends MigrationLookupTestCase {

  /**
   * Tests ::createStubRow()
   *
   * @covers ::createStubRow
   *
   * @expectedDeprecation Drupal\migrate\Plugin\migrate\process\MigrationLookup::createStubRow is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the migrate.stub service to create stubs. See https://www.drupal.org/node/3047268
   */
  public function testCreateStubRow() {
    $this->prepareContainer();
    $lookup = MigrationLookup::create($this->prepareContainer(), [], '', [], $this->prophesize(MigrationInterface::class)
      ->reveal());
    $method = new \ReflectionMethod($lookup, 'createStubRow');
    $method->setAccessible(TRUE);
    /** @var \Drupal\migrate\Row $row */
    $row = $method->invoke($lookup, [
      'id' => 1,
      'value' => 'test',
    ], ['id' => ['type' => 'integer']]);
    $this->assertTrue($row->isStub());
    $this->assertSame('test', $row->get('value'));

  }

}
