<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\TemplateTest
 */

namespace Drupal\migrate\Tests;

/**
 * Test the migration template functionality.
 *
 * @group migrate
 */
class TemplateTest extends MigrateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('template_test');

  /**
   * Test different connection types.
   */
  public function testTemplates() {
    $migration_templates = \Drupal::service('migrate.template_storage')->findTemplatesByTag("Template Test");
    $expected_url = [
        'id' => 'url_template',
        'label' => 'Template test - url',
        'migration_tags' => ['Template Test'],
        'source' => ['plugin' => 'empty'],
        'process' => ['src' => 'foobar'],
        'destination' => ['plugin' => 'url_alias'],
    ];
    $expected_node = [
        'id' => 'node_template',
        'label' => 'Template test - node',
        'migration_tags' => ['Template Test'],
        'source' => ['plugin' => 'empty'],
        'process' => ['src' => 'barfoo'],
        'destination' => ['plugin' => 'entity:node'],
    ];
    $this->assertIdentical($migration_templates['migrate.migration.url_template'], $expected_url);
    $this->assertIdentical($migration_templates['migrate.migration.node_template'], $expected_node);
    $this->assertFalse(isset($migration_templates['migrate.migration.other_template']));
  }

  /**
   * Tests retrieving a template by name.
   */
  public function testGetTemplateByName() {
    /** @var \Drupal\migrate\MigrateTemplateStorage $template_storage */
    $template_storage = \Drupal::service('migrate.template_storage');

    $expected_url = [
        'id' => 'url_template',
        'label' => 'Template test - url',
        'migration_tags' => ['Template Test'],
        'source' => ['plugin' => 'empty'],
        'process' => ['src' => 'foobar'],
        'destination' => ['plugin' => 'url_alias'],
    ];
    $expected_node = [
        'id' => 'node_template',
        'label' => 'Template test - node',
        'migration_tags' => ['Template Test'],
        'source' => ['plugin' => 'empty'],
        'process' => ['src' => 'barfoo'],
        'destination' => ['plugin' => 'entity:node'],
    ];
    $this->assertIdentical($template_storage->getTemplateByName('migrate.migration.url_template'), $expected_url);
    $this->assertIdentical($template_storage->getTemplateByName('migrate.migration.node_template'), $expected_node);
    $this->assertNull($template_storage->getTemplateByName('migrate.migration.dne'));
  }

}
