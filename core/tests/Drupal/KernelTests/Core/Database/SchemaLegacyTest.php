<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\KernelTests\KernelTestBase;

/**
 * Deprecation tests cases for the schema API.
 *
 * @group legacy
 */
class SchemaLegacyTest extends KernelTestBase {

  /**
   * Tests deprecation of the drupal_schema_get_field_value() function.
   *
   * @expectedDeprecation drupal_schema_get_field_value() is deprecated in drupal:8.8.0. It will be removed from drupal:9.0.0. Use \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema::castValue($info, $value) instead. See https://www.drupal.org/node/3051983
   */
  public function testSchemaGetFieldValue() {
    $info = ['type' => 'int'];
    $value = 1.1;
    $this->assertEquals(SqlContentEntityStorageSchema::castValue($info, $value), drupal_schema_get_field_value($info, $value));
  }

}
