<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Migrate\d6\MigrateFieldWidgetSettingsTest.
 */

namespace Drupal\field\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Migrate field widget settings.
 *
 * @group field
 */
class MigrateFieldWidgetSettingsTest extends MigrateDrupal6TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'field',
    'telephone',
    'link',
    'file',
    'image',
    'datetime',
    'node',
    'text',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    entity_create('node_type', array('type' => 'test_page'))->save();
    entity_create('node_type', array('type' => 'story'))->save();

    // Add some id mappings for the dependant migrations.
    $id_mappings = array(
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
      ),
    );
    $this->prepareMigrations($id_mappings);
    $this->loadDumps([
      'ContentNodeFieldInstance.php',
      'ContentNodeField.php',
      'ContentFieldTest.php',
      'ContentFieldTestTwo.php',
      'ContentFieldMultivalue.php',
    ]);
    $this->executeMigration('d6_field_instance_widget_settings');
  }

  /**
   * Test that migrated view modes can be loaded using D8 API's.
   */
  public function testWidgetSettings() {
    // Test the config can be loaded.
    $form_display = entity_load('entity_form_display', 'node.story.default');
    $this->assertIdentical(FALSE, is_null($form_display), "Form display node.story.default loaded with config.");

    // Text field.
    $component = $form_display->getComponent('field_test');
    $expected = array('weight' => 1, 'type' => 'text_textfield');
    $expected['settings'] = array('size' => 60, 'placeholder' => '');
    $expected['third_party_settings'] = array();
    $this->assertIdentical($expected, $component, 'Text field settings are correct.');

    // Integer field.
    $component = $form_display->getComponent('field_test_two');
    $expected['type'] = 'number';
    $expected['weight'] = 1;
    $expected['settings'] = array('placeholder' => '');
    $this->assertIdentical($expected, $component);

    // Float field.
    $component = $form_display->getComponent('field_test_three');
    $expected['weight'] = 2;
    $this->assertIdentical($expected, $component);

    // Email field.
    $component = $form_display->getComponent('field_test_email');
    $expected['type'] = 'email_default';
    $expected['weight'] = 6;
    $this->assertIdentical($expected, $component);

    // Link field.
    $component = $form_display->getComponent('field_test_link');
    $this->assertIdentical('link_default', $component['type']);
    $this->assertIdentical(7, $component['weight']);
    $this->assertFalse(array_filter($component['settings']));

    // File field.
    $component = $form_display->getComponent('field_test_filefield');
    $expected['type'] = 'file_generic';
    $expected['weight'] = 8;
    $expected['settings'] = array('progress_indicator' => 'bar');
    $this->assertIdentical($expected, $component);

    // Image field.
    $component = $form_display->getComponent('field_test_imagefield');
    $expected['type'] = 'image_image';
    $expected['weight'] = 9;
    $expected['settings'] = array('progress_indicator' => 'bar', 'preview_image_style' => 'thumbnail');
    $this->assertIdentical($expected, $component);

    // Phone field.
    $component = $form_display->getComponent('field_test_phone');
    $expected['type'] = 'telephone_default';
    $expected['weight'] = 13;
    $expected['settings'] = array('placeholder' => '');
    $this->assertIdentical($expected, $component);

    // Date fields.
    $component = $form_display->getComponent('field_test_date');
    $expected['type'] = 'datetime_default';
    $expected['weight'] = 10;
    $expected['settings'] = array();
    $this->assertIdentical($expected, $component);

    $component = $form_display->getComponent('field_test_datestamp');
    $expected['weight'] = 11;
    $this->assertIdentical($expected, $component);

    $component = $form_display->getComponent('field_test_datetime');
    $expected['weight'] = 12;
    $this->assertIdentical($expected, $component);

  }

}
