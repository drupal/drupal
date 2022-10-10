<?php

namespace Drupal\Tests\pgsql\Kernel\pgsql;

use Drupal\KernelTests\Core\Database\DriverSpecificSchemaTestBase;

/**
 * Tests schema API for the PostgreSQL driver.
 *
 * @group Database
 */
class SchemaTest extends DriverSpecificSchemaTestBase {

  /**
   * @covers \Drupal\Core\Database\Driver\pgsql\Schema::extensionExists
   */
  public function testPgsqlExtensionExists(): void {
    // Test the method for a non existing extension.
    $this->assertFalse($this->schema->extensionExists('non_existing_extension'));

    // Test the method for an existing extension.
    $this->assertTrue($this->schema->extensionExists('pg_trgm'));
  }

}
