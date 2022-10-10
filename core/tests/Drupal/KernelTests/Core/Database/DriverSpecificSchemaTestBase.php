<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Schema;
use Drupal\Tests\Core\Database\SchemaIntrospectionTestTrait;

/**
 * Tests table creation and modification via the schema API.
 */
abstract class DriverSpecificSchemaTestBase extends DriverSpecificKernelTestBase {

  use SchemaIntrospectionTestTrait;

  /**
   * Database schema instance.
   */
  protected Schema $schema;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->schema = $this->connection->schema();
  }

}
