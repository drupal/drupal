<?php

namespace Drupal\Tests\config\Functional;

use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the functionality of ConfigSchemaChecker in WebTestBase tests.
 *
 * @group config
 */
class SchemaConfigListenerWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests \Drupal\Core\Config\Development\ConfigSchemaChecker.
   */
  public function testConfigSchemaChecker() {
    $this->drupalLogin($this->drupalCreateUser(['administer site configuration']));

    // Test a non-existing schema.
    $msg = 'Expected SchemaIncompleteException thrown';
    try {
      $this->config('config_schema_test.schemaless')->set('foo', 'bar')->save();
      $this->fail($msg);
    }
    catch (SchemaIncompleteException $e) {
      $this->pass($msg);
      $this->assertEquals('No schema for config_schema_test.schemaless', $e->getMessage());
    }

    // Test a valid schema.
    $msg = 'Unexpected SchemaIncompleteException thrown';
    $config = $this->config('config_test.types')->set('int', 10);
    try {
      $config->save();
      $this->pass($msg);
    }
    catch (SchemaIncompleteException $e) {
      $this->fail($msg);
    }

    // Test an invalid schema.
    $msg = 'Expected SchemaIncompleteException thrown';
    $config = $this->config('config_test.types')
      ->set('foo', 'bar')
      ->set('array', 1);
    try {
      $config->save();
      $this->fail($msg);
    }
    catch (SchemaIncompleteException $e) {
      $this->pass($msg);
      $this->assertEquals('Schema errors for config_test.types with the following errors: config_test.types:array variable type is integer but applied schema class is Drupal\Core\Config\Schema\Sequence, config_test.types:foo missing schema', $e->getMessage());
    }

    // Test that the config event listener is working in the child site.
    $this->drupalGet('config_test/schema_listener');
    $this->assertText('No schema for config_schema_test.schemaless');
  }

}
