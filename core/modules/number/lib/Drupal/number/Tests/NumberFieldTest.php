<?php

/**
 * @file
 * Definition of Drupal\number\NumberFieldTest.
 */

namespace Drupal\number\Tests;

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
  public static $modules = array('node', 'entity_test', 'number', 'field_ui');

  /**
   * A field to use in this class.
   *
   * @var \Drupal\field\Entity\Field
   */
  protected $field;

  /**
   * A field instance to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldInstance
   */
  protected $instance;

  /**
   * A user with permission to view and manage entities and content types.
   *
   * @var \Drupal\user\UserInterface
   */
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

    $this->web_user = $this->drupalCreateUser(array('view test entity', 'administer entity_test content', 'administer content types', 'administer node fields', 'administer node display', 'bypass node access'));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Test number_decimal field.
   */
  function testNumberDecimalField() {
    // Create a field with settings to validate.
    $field_name = drupal_strtolower($this->randomName());
    entity_create('field_entity', array(
      'name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'number_decimal',
      'settings' => array(
        'precision' => 8, 'scale' => 4, 'decimal_separator' => '.',
      )
    ))->save();
    entity_create('field_instance', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ))->save();

    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => 'number',
        'settings' => array(
          'placeholder' => '0.00'
        ),
      ))
      ->save();
    entity_get_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => 'number_decimal',
      ))
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value]", '', 'Widget is displayed');
    $this->assertRaw('placeholder="0.00"');

    // Submit a signed decimal value within the allowed precision and scale.
    $value = '-1234.5678';
    $edit = array(
      'user_id' => 1,
      'name' => $this->randomName(),
      "{$field_name}[0][value]" => $value,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)), 'Entity was created');
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
      $this->drupalGet('entity_test/add');
      $edit = array(
        "{$field_name}[0][value]" => $wrong_entry,
      );
      $this->drupalPostForm(NULL, $edit, t('Save'));
      $this->assertRaw(t('%name must be a number.', array('%name' => $field_name)), 'Correctly failed to save decimal value with more than one decimal point.');
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
      $this->drupalGet('entity_test/add');
      $edit = array(
        "{$field_name}[0][value]" => $wrong_entry,
      );
      $this->drupalPostForm(NULL, $edit, t('Save'));
      $this->assertRaw(t('%name must be a number.', array('%name' => $field_name)), 'Correctly failed to save decimal value with minus sign in the wrong position.');
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
    $this->drupalPostForm(NULL, $edit, t('Save and manage fields'));

    // Add an integer field to the newly-created type.
    $label = $this->randomName();
    $field_name = drupal_strtolower($label);
    $edit = array(
      'fields[_add_new_field][label]'=> $label,
      'fields[_add_new_field][field_name]' => $field_name,
      'fields[_add_new_field][type]' => 'number_integer',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Add prefix and suffix for the newly-created field.
    $prefix = $this->randomName();
    $suffix = $this->randomName();
    $edit = array(
      'instance[settings][prefix]' => $prefix,
      'instance[settings][suffix]' => $suffix,
    );
    $this->drupalPostForm("admin/structure/types/manage/$type/fields/node.$type.field_$field_name", $edit, t('Save settings'));

    // Set the formatter to "unformatted" and to "number_integer", and just
    // check that the settings summary does not generate warnings.
    $this->drupalGet("admin/structure/types/manage/$type/display");
    $edit = array(
      "fields[field_$field_name][type]" => 'number_unformatted',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $edit = array(
      "fields[field_$field_name][type]" => 'number_integer',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Configure the formatter to display the prefix and suffix.
    $this->drupalPostAjaxForm(NULL, array(), "field_${field_name}_settings_edit");
    $edit = array("fields[field_${field_name}][settings_edit_form][settings][prefix_suffix]" => TRUE);
    $this->drupalPostAjaxForm(NULL, $edit, "field_${field_name}_plugin_settings_update");
    $this->drupalPostForm(NULL, array(), t('Save'));

    // Create new content and check that prefix and suffix are shown.
    $rand_number = rand();
    $edit = array(
      'title[0][value]' => $this->randomName(),
      'field_' .$field_name . '[0][value]' => $rand_number,
    );
    $this->drupalPostForm("node/add/$type", $edit, t('Save'));

    $this->assertRaw("$prefix$rand_number$suffix", 'Prefix and suffix added');
  }
}
