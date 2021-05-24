<?php

namespace Drupal\Tests\node\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Test D7NodeDeriver.
 *
 * @group migrate_drupal_7
 */
class MigrateNodeDeriverTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * Tests node translation migrations with translation disabled.
   */
  public function testNoTranslations() {
    // Without content_translation, there should be no translation migrations.
    $migrations = $this->container->get('plugin.manager.migration')->createInstances('d7_node_translation');
    $this->assertEmpty($migrations);
  }

  /**
   * Tests node translation migrations with translation enabled.
   */
  public function testTranslations() {
    // With content_translation, there should be translation migrations for
    // each content type.
    $this->enableModules(['language', 'content_translation', 'filter']);
    $this->assertTrue($this->container->get('plugin.manager.migration')->hasDefinition('d7_node_translation:article'), "Node translation migrations exist after content_translation installed");
  }

  /**
   * Tests the d7_node node driver.
   *
   * @group node
   */
  public function testBuilder() {
    $process = $this->getMigration('d7_node:test_content_type')->getProcess();
    $this->assertSame('field_boolean', $process['field_boolean'][0]['source']);
    $this->assertSame('field_email', $process['field_email'][0]['source']);
    $this->assertSame('field_phone', $process['field_phone'][0]['source']);
    $this->assertSame('field_date', $process['field_date'][0]['source']);
    $this->assertSame('field_date_with_end_time', $process['field_date_with_end_time'][0]['source']);
    $this->assertSame('field_file', $process['field_file'][0]['source']);
    $this->assertSame('field_float', $process['field_float'][0]['source']);
    $this->assertSame('field_images', $process['field_images'][0]['source']);
    $this->assertSame('field_integer', $process['field_integer'][0]['source']);
    $this->assertSame('field_link', $process['field_link'][0]['source']);
    $this->assertSame('field_text_list', $process['field_text_list'][0]['source']);
    $this->assertSame('field_integer_list', $process['field_integer_list'][0]['source']);
    $this->assertSame('field_long_text', $process['field_long_text'][0]['source']);
    $this->assertSame('field_term_reference', $process['field_term_reference'][0]['source']);
    $this->assertSame('field_text', $process['field_text'][0]['source']);
  }

}
