<?php

namespace Drupal\Tests\Traits\Core\Config;

use Drupal\Core\Config\Schema\SchemaIncompleteException;

/**
 * Adds a test for the configuration schema checker use in tests.
 */
trait SchemaConfigListenerTestTrait {

  /**
   * Tests \Drupal\Core\Config\Development\ConfigSchemaChecker.
   */
  public function testConfigSchemaChecker() {
    // Test a non-existing schema.
    $message = 'Expected SchemaIncompleteException thrown';
    try {
      $this->config('config_schema_test.schemaless')->set('foo', 'bar')->save();
      $this->fail($message);
    }
    catch (SchemaIncompleteException $e) {
      $this->pass($message);
      $this->assertEqual('No schema for config_schema_test.schemaless', $e->getMessage());
    }

    // Test a valid schema.
    $message = 'Unexpected SchemaIncompleteException thrown';
    $config = $this->config('config_test.types')->set('int', 10);
    try {
      $config->save();
      $this->pass($message);
    }
    catch (SchemaIncompleteException $e) {
      $this->fail($message);
    }

    // Test an invalid schema.
    $message = 'Expected SchemaIncompleteException thrown';
    $config = $this->config('config_test.types')
      ->set('foo', 'bar')
      ->set('array', 1);
    try {
      $config->save();
      $this->fail($message);
    }
    catch (SchemaIncompleteException $e) {
      $this->pass($message);
      $this->assertEqual('Schema errors for config_test.types with the following errors: config_test.types:array variable type is integer but applied schema class is Drupal\Core\Config\Schema\Sequence, config_test.types:foo missing schema', $e->getMessage());
    }

  }

}
