<?php

declare(strict_types=1);

namespace Drupal\Tests\path_alias\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the path_alias storage schema.
 *
 * @coversDefaultClass \Drupal\path_alias\PathAliasStorageSchema
 *
 * @group path_alias
 */
class PathAliasStorageSchemaTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['path_alias'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('path_alias');
  }

  /**
   * Tests that the path_alias__status index is removed.
   *
   * @covers ::getEntitySchema
   */
  public function testPathAliasStatusIndexRemoved(): void {
    $schema = \Drupal::database()->schema();
    $table_name = 'path_alias';
    $this->assertTrue($schema->indexExists($table_name, 'path_alias__alias_langcode_id_status'));
    $this->assertTrue($schema->indexExists($table_name, 'path_alias__path_langcode_id_status'));
    $this->assertFalse($schema->indexExists($table_name, 'path_alias__status'));
  }

}
