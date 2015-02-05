<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateFieldFormatterSettingsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade field formatter settings to entity.display.*.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateFieldFormatterSettingsTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field', 'datetime', 'image', 'text', 'link', 'file', 'telephone');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    entity_create('node_type', array('type' => 'test_page'))->save();
    entity_create('node_type', array('type' => 'story'))->save();
    // Create the node preview view mode.
    EntityViewMode::create(array('id' => 'node.preview', 'targetEntityType' => 'node'))->save();

    // Add some id mappings for the dependant migrations.
    $id_mappings = array(
      'd6_view_modes' => array(
        array(array(1), array('node', 'preview')),
        array(array(4), array('node', 'rss')),
        array(array('teaser'), array('node', 'teaser')),
        array(array('full'), array('node', 'full')),
      ),
      'd6_field_instance' => array(
        array(array('fieldname', 'page'), array('node', 'fieldname', 'page')),
      ),
      'd6_field' => array(
        array(array('field_test'), array('node', 'field_test')),
        array(array('field_test_two'), array('node', 'field_test_two')),
        array(array('field_test_three'), array('node', 'field_test_three')),
        array(array('field_test_email'), array('node', 'field_test_email')),
        array(array('field_test_link'), array('node', 'field_test_link')),
        array(array('field_test_filefield'), array('node', 'field_test_filefield')),
        array(array('field_test_imagefield'), array('node', 'field_test_imagefield')),
        array(array('field_test_phone'), array('node', 'field_test_phone')),
        array(array('field_test_date'), array('node', 'field_test_date')),
        array(array('field_test_datestamp'), array('node', 'field_test_datestamp')),
        array(array('field_test_datetime'), array('node', 'field_test_datetime')),
        array(array('field_test_exclude_unset'), array('node', 'field_test_exclude_unset')),
      ),
    );
    $this->prepareMigrations($id_mappings);

    $migration = entity_load('migration', 'd6_field_formatter_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/ContentNodeFieldInstance.php',
      $this->getDumpDirectory() . '/ContentNodeField.php',
      $this->getDumpDirectory() . '/ContentFieldTest.php',
      $this->getDumpDirectory() . '/ContentFieldTestTwo.php',
      $this->getDumpDirectory() . '/ContentFieldMultivalue.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Test that migrated entity display settings can be loaded using D8 API's.
   */
  public function testEntityDisplaySettings() {
    // Run tests.
    $field_name = "field_test";
    $expected = array(
      'label' => 'above',
      'weight' => 1,
      'type' => 'text_trimmed',
      'settings' => array('trim_length' => 600),
      'third_party_settings' => array(),
    );

    // Can we load any entity display.
    $display = entity_load('entity_view_display', 'node.story.teaser');
    $this->assertIdentical($display->getComponent($field_name), $expected);

    // Test migrate worked with multiple bundles.
    $display = entity_load('entity_view_display', 'node.test_page.teaser');
    $expected['weight'] = 35;
    $this->assertIdentical($display->getComponent($field_name), $expected);

    // Test RSS because that has been converted from 4 to rss.
    $display = entity_load('entity_view_display', 'node.story.rss');
    $expected['weight'] = 1;
    $this->assertIdentical($display->getComponent($field_name), $expected);

    // Test the default format with text_default which comes from a static map.
    $expected['type'] = 'text_default';
    $expected['settings'] = array();
    $display = entity_load('entity_view_display', 'node.story.default');
    $this->assertIdentical($display->getComponent($field_name), $expected);

    // Check that we can migrate multiple fields.
    $content = $display->get('content');
    $this->assertTrue(isset($content['field_test']), 'Settings for field_test exist.');
    $this->assertTrue(isset($content['field_test_two']), "Settings for field_test_two exist.");

    // Check that we can migrate a field where exclude is not set.
    $this->assertTrue(isset($content['field_test_exclude_unset']), "Settings for field_test_exclude_unset exist.");

    // Test the number field formatter settings are correct.
    $expected['weight'] = 1;
    $expected['type'] = 'number_integer';
    $expected['settings'] = array(
      'thousand_separator' => ',',
      'prefix_suffix' => TRUE,
    );
    $component = $display->getComponent('field_test_two');
    $this->assertIdentical($component, $expected);
    $expected['weight'] = 2;
    $expected['type'] = 'number_decimal';
    $expected['settings'] = array(
       'scale' => 2,
       'decimal_separator' => '.',
       'thousand_separator' => ',',
       'prefix_suffix' => TRUE,
    );
    $component = $display->getComponent('field_test_three');
    $this->assertIdentical($component, $expected);

    // Test the email field formatter settings are correct.
    $expected['weight'] = 6;
    $expected['type'] = 'email_mailto';
    $expected['settings'] = array();
    $component = $display->getComponent('field_test_email');
    $this->assertIdentical($component, $expected);

    // Test the link field formatter settings.
    $expected['weight'] = 7;
    $expected['type'] = 'link';
    $expected['settings'] = array(
      'trim_length' => 80,
      'url_only' => TRUE,
      'url_plain' => TRUE,
      'rel' => '0',
      'target' => '0',
    );
    $component = $display->getComponent('field_test_link');
    $this->assertIdentical($component, $expected);
    $expected['settings']['url_only'] = FALSE;
    $expected['settings']['url_plain'] = FALSE;
    $display = entity_load('entity_view_display', 'node.story.teaser');
    $component = $display->getComponent('field_test_link');
    $this->assertIdentical($component, $expected);

    // Test the file field formatter settings.
    $expected['weight'] = 8;
    $expected['type'] = 'file_default';
    $expected['settings'] = array();
    $component = $display->getComponent('field_test_filefield');
    $this->assertIdentical($component, $expected);
    $display = entity_load('entity_view_display', 'node.story.default');
    $expected['type'] = 'file_url_plain';
    $component = $display->getComponent('field_test_filefield');
    $this->assertIdentical($component, $expected);

    // Test the image field formatter settings.
    $expected['weight'] = 9;
    $expected['type'] = 'image';
    $expected['settings'] = array('image_style' => '', 'image_link' => '');
    $component = $display->getComponent('field_test_imagefield');
    $this->assertIdentical($component, $expected);
    $display = entity_load('entity_view_display', 'node.story.teaser');
    $expected['settings']['image_link'] = 'file';
    $component = $display->getComponent('field_test_imagefield');
    $this->assertIdentical($component, $expected);

    // Test phone field.
    $expected['weight'] = 13;
    $expected['type'] = 'basic_string';
    $expected['settings'] = array();
    $component = $display->getComponent('field_test_phone');
    $this->assertIdentical($component, $expected);

    // Test date field.
    $expected['weight'] = 10;
    $expected['type'] = 'datetime_default';
    $expected['settings'] = array('format_type' => 'fallback');
    $component = $display->getComponent('field_test_date');
    $this->assertIdentical($component, $expected);
    $display = entity_load('entity_view_display', 'node.story.default');
    $expected['settings']['format_type'] = 'long';
    $component = $display->getComponent('field_test_date');
    $this->assertIdentical($component, $expected);

    // Test date stamp field.
    $expected['weight'] = 11;
    $expected['settings']['format_type'] = 'fallback';
    $component = $display->getComponent('field_test_datestamp');
    $this->assertIdentical($component, $expected);
    $display = entity_load('entity_view_display', 'node.story.teaser');
    $expected['settings'] = array('format_type' => 'medium');
    $component = $display->getComponent('field_test_datestamp');
    $this->assertIdentical($component, $expected);

    // Test datetime field.
    $expected['weight'] = 12;
    $expected['settings'] = array('format_type' => 'short');
    $component = $display->getComponent('field_test_datetime');
    $this->assertIdentical($component, $expected);
    $display = entity_load('entity_view_display', 'node.story.default');
    $expected['settings']['format_type'] = 'fallback';
    $component = $display->getComponent('field_test_datetime');
    $this->assertIdentical($component, $expected);

    // Test a date field with a random format which should be mapped
    // to datetime_default.
    $display = entity_load('entity_view_display', 'node.story.rss');
    $expected['settings']['format_type'] = 'fallback';
    $component = $display->getComponent('field_test_datetime');
    $this->assertIdentical($component, $expected);
    // Test that our Id map has the correct data.
    $this->assertIdentical(array('node', 'story', 'teaser', 'field_test'), entity_load('migration', 'd6_field_formatter_settings')->getIdMap()->lookupDestinationID(array('story', 'teaser', 'node', 'field_test')));
  }

}
