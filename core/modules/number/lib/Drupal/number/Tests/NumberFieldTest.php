<?php

/**
 * @file
 * Definition of Drupal\number\NumberFieldTest.
 */

namespace Drupal\number\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Tests for number field types.
 */
class NumberFieldTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field_test', 'number', 'field_ui');

  protected $field;
  protected $instance;
  protected $web_user;

  public static function getInfo() {
    return array(
      'name'  => 'Number field',
      'description'  => 'Test the creation of number fields.',
      'group' => 'Field types'
    );
  }

  function setUp() {
    parent::setUp();

    $this->web_user = $this->drupalCreateUser(array('access field_test content', 'administer field_test content', 'administer content types', 'administer node fields','administer node display'));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Test number_decimal field.
   */
  function testNumberDecimalField() {
    // Create a field with settings to validate.
    $this->field = array(
      'field_name' => drupal_strtolower($this->randomName()),
      'type' => 'number_decimal',
      'settings' => array(
        'precision' => 8, 'scale' => 4, 'decimal_separator' => '.',
      )
    );
    field_create_field($this->field);
    $this->instance = array(
      'field_name' => $this->field['field_name'],
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
    );
    field_create_instance($this->instance);

    entity_get_form_display('test_entity', 'test_bundle', 'default')
      ->setComponent($this->field['field_name'], array(
        'type' => 'number',
        'settings' => array(
          'placeholder' => '0.00'
        ),
      ))
      ->save();
    entity_get_display('test_entity', 'test_bundle', 'default')
      ->setComponent($this->field['field_name'], array(
        'type' => 'number_decimal',
      ))
      ->save();

    // Display creation form.
    $this->drupalGet('test-entity/add/test_bundle');
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $this->assertFieldByName("{$this->field['field_name']}[$langcode][0][value]", '', 'Widget is displayed');
    $this->assertRaw('placeholder="0.00"');

    // Submit a signed decimal value within the allowed precision and scale.
    $value = '-1234.5678';
    $edit = array(
      "{$this->field['field_name']}[$langcode][0][value]" => $value,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertRaw(t('test_entity @id has been created.', array('@id' => $id)), 'Entity was created');
    $this->assertRaw(round($value, 2), 'Value is displayed.');

    // Try to create entries with more than one decimal separator; assert fail.
    $wrong_entries = array(
      '3.14.159',
      '0..45469',
      '..4589',
      '6.459.52',
      '6.3..25',
    );

    foreach ($wrong_entries as $wrong_entry) {
      $this->drupalGet('test-entity/add/test_bundle');
      $edit = array(
        "{$this->field['field_name']}[$langcode][0][value]" => $wrong_entry,
      );
      $this->drupalPost(NULL, $edit, t('Save'));
      $this->assertRaw(t('%name must be a number.', array('%name' => $this->field['field_name'])), 'Correctly failed to save decimal value with more than one decimal point.');
    }

    // Try to create entries with minus sign not in the first position.
    $wrong_entries = array(
      '3-3',
      '4-',
      '1.3-',
      '1.2-4',
      '-10-10',
    );

    foreach ($wrong_entries as $wrong_entry) {
      $this->drupalGet('test-entity/add/test_bundle');
      $edit = array(
        "{$this->field['field_name']}[$langcode][0][value]" => $wrong_entry,
      );
      $this->drupalPost(NULL, $edit, t('Save'));
      $this->assertRaw(t('%name must be a number.', array('%name' => $this->field['field_name'])), 'Correctly failed to save decimal value with minus sign in the wrong position.');
    }
  }

  /**
   * Test number_integer field.
   */
  function testNumberIntegerField() {
    // Display the "Add content type" form.
    $this->drupalGet('admin/structure/types/add');

    // Add a content type.
    $name = $this->randomName();
    $type = drupal_strtolower($name);
    $edit = array('name' => $name, 'type' => $type);
    $this->drupalPost(NULL, $edit, t('Save and manage fields'));

    // Add an integer field to the newly-created type.
    $label = $this->randomName();
    $field_name = drupal_strtolower($label);
    $edit = array(
      'fields[_add_new_field][label]'=> $label,
      'fields[_add_new_field][field_name]' => $field_name,
      'fields[_add_new_field][type]' => 'number_integer',
      'fields[_add_new_field][widget_type]' => 'number',
    );
    $this->drupalPost(NULL, $edit, t('Save'));

    // Set the formatter to "number_integer" and to "unformatted", and just
    // check that the settings summary does not generate warnings.
    $this->drupalGet("admin/structure/types/manage/$type/display");
    $edit = array(
      "fields[field_$field_name][type]" => 'number_integer',
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $edit = array(
      "fields[field_$field_name][type]" => 'number_unformatted',
    );
    $this->drupalPost(NULL, $edit, t('Save'));
  }
}
