<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Migrate\d6\MigrateFieldInstanceTest.
 */

namespace Drupal\field\Tests\Migrate\d6;

use Drupal\field\Entity\FieldConfig;
use Drupal\link\LinkItemInterface;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;
use Drupal\node\Entity\Node;

/**
 * Migrate field instances.
 *
 * @group migrate_drupal_6
 */
class MigrateFieldInstanceTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateFields();
  }

  /**
   * Tests migration of file variables to file.settings.yml.
   */
  public function testFieldInstanceSettings() {
    $entity = Node::create(['type' => 'story']);
    // Test a text field.
    $field = FieldConfig::load('node.story.field_test');
    $this->assertIdentical('Text Field', $field->label());
    $expected = array('max_length' => 255);
    $this->assertIdentical($expected, $field->getSettings());
    $this->assertIdentical('text for default value', $entity->field_test->value);

    // Test a number field.
    $field = FieldConfig::load('node.story.field_test_two');
    $this->assertIdentical('Integer Field', $field->label());
    $expected = array(
      'min' => 10,
      'max' => 100,
      'prefix' => 'pref',
      'suffix' => 'suf',
      'unsigned' => FALSE,
      'size' => 'normal',
    );
    $this->assertIdentical($expected, $field->getSettings());

    $field = FieldConfig::load('node.story.field_test_four');
    $this->assertIdentical('Float Field', $field->label());
    $expected = array(
      'min' => 100.0,
      'max' => 200.0,
      'prefix' => 'id-',
      'suffix' => '',
    );
    $this->assertIdentical($expected, $field->getSettings());

    // Test email field.
    $field = FieldConfig::load('node.story.field_test_email');
    $this->assertIdentical('Email Field', $field->label());
    $this->assertIdentical('benjy@example.com', $entity->field_test_email->value);

    // Test a filefield.
    $field = FieldConfig::load('node.story.field_test_filefield');
    $this->assertIdentical('File Field', $field->label());
    $expected = array(
      'file_extensions' => 'txt pdf doc',
      'file_directory' => 'images',
      'description_field' => TRUE,
      'max_filesize' => '200KB',
      'target_type' => 'file',
      'display_field' => FALSE,
      'display_default' => FALSE,
      'uri_scheme' => 'public',
      'handler' => 'default:file',
      'handler_settings' => array(),
    );
    $field_settings = $field->getSettings();
    ksort($expected);
    ksort($field_settings);
    // This is the only way to compare arrays.
    $this->assertIdentical($expected, $field_settings);

    // Test a link field.
    $field = FieldConfig::load('node.story.field_test_link');
    $this->assertIdentical('Link Field', $field->label());
    $expected = array('title' => 2, 'link_type' => LinkItemInterface::LINK_GENERIC);
    $this->assertIdentical($expected, $field->getSettings());
    $this->assertIdentical('default link title', $entity->field_test_link->title, 'Field field_test_link default title is correct.');
    $this->assertIdentical('https://www.drupal.org', $entity->field_test_link->url, 'Field field_test_link default title is correct.');
    $this->assertIdentical([], $entity->field_test_link->options['attributes']);
  }

}
