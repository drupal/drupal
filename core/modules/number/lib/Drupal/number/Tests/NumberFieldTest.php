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
  }

  /**
   * Test default formatter behavior
   */
  function testNumberFormatter() {
    $type = drupal_strtolower($this->randomName());
    $float_field = drupal_strtolower($this->randomName());
    $integer_field = drupal_strtolower($this->randomName());
    $thousand_separators = array('', '.', ',', ' ', chr(8201), "'");
    $decimal_separators = array('.', ',');
    $prefix = $this->randomName();
    $suffix = $this->randomName();
    $random_float = rand(0,pow(10,6));
    $random_integer = rand(0, pow(10,6));

    // Create a content type containing float and integer fields.
    $this->drupalCreateContentType(array('type' => $type));

    entity_create('field_entity', array(
      'name' => $float_field,
      'entity_type' => 'node',
      'type' => 'number_float',
    ))->save();

    entity_create('field_entity', array(
      'name' => $integer_field,
      'entity_type' => 'node',
      'type' => 'number_integer',
    ))->save();

    entity_create('field_instance', array(
      'field_name' => $float_field,
      'entity_type' => 'node',
      'bundle' => $type,
      'settings' => array(
        'prefix' => $prefix,
        'suffix' => $suffix
      ),
    ))->save();

    entity_create('field_instance', array(
      'field_name' => $integer_field,
      'entity_type' => 'node',
      'bundle' => $type,
      'settings' => array(
        'prefix' => $prefix,
        'suffix' => $suffix
      ),
    ))->save();

    entity_get_form_display('node', $type, 'default')
      ->setComponent($float_field, array(
        'type' => 'number',
        'settings' => array(
          'placeholder' => '0.00'
        ),
      ))
      ->setComponent($integer_field, array(
        'type' => 'number',
        'settings' => array(
          'placeholder' => '0.00'
        ),
      ))
      ->save();

    entity_get_display('node', $type, 'default')
      ->setComponent($float_field, array(
        'type' => 'number_decimal',
      ))
      ->setComponent($integer_field, array(
        'type' => 'number_unformatted',
      ))
      ->save();

    // Create a node to test formatters.
    $node = entity_create('node', array(
      'type' => $type,
      'title' => $this->randomName(),
      $float_field => array(
        'value' => $random_float,
      ),
      $integer_field => array(
        'value' => $random_integer,
      ),
    ));
    $node->save();

    // Go to manage display page.
    $this->drupalGet("admin/structure/types/manage/$type/display");

    // Configure number_decimal formatter for number_float_field
    $thousand_separator = $thousand_separators[array_rand($thousand_separators)];
    $decimal_separator = $decimal_separators[array_rand($decimal_separators)];
    $scale = rand(0, 10);

    $this->drupalPostAjaxForm(NULL, array(), "${float_field}_settings_edit");
    $edit = array(
      "fields[${float_field}][settings_edit_form][settings][prefix_suffix]" => TRUE,
      "fields[${float_field}][settings_edit_form][settings][scale]" => $scale,
      "fields[${float_field}][settings_edit_form][settings][decimal_separator]" => $decimal_separator,
      "fields[${float_field}][settings_edit_form][settings][thousand_separator]" => $thousand_separator,
    );
    $this->drupalPostAjaxForm(NULL, $edit, "${float_field}_plugin_settings_update");
    $this->drupalPostForm(NULL, array(), t('Save'));

    // Check number_decimal and number_unformatted formatters behavior.
    $this->drupalGet('node/' . $node->id());
    $float_formatted = number_format($random_float, $scale, $decimal_separator, $thousand_separator);
    $this->assertRaw("$prefix$float_formatted$suffix", 'Prefix and suffix added');
    $this->assertRaw((string) $random_integer);

    // Configure the number_decimal formatter.
    entity_get_display('node', $type, 'default')
      ->setComponent($integer_field, array(
        'type' => 'number_integer',
      ))
      ->save();
    $this->drupalGet("admin/structure/types/manage/$type/display");

    $thousand_separator = $thousand_separators[array_rand($thousand_separators)];

    $this->drupalPostAjaxForm(NULL, array(), "${integer_field}_settings_edit");
    $edit = array(
      "fields[${integer_field}][settings_edit_form][settings][prefix_suffix]" => FALSE,
      "fields[${integer_field}][settings_edit_form][settings][thousand_separator]" => $thousand_separator,
    );
    $this->drupalPostAjaxForm(NULL, $edit, "${integer_field}_plugin_settings_update");
    $this->drupalPostForm(NULL, array(), t('Save'));

    // Check number_integer formatter behavior.
    $this->drupalGet('node/' . $node->id());

    $integer_formatted = number_format($random_integer, 0, '', $thousand_separator);
    $this->assertRaw($integer_formatted, 'Random integer formatted');
  }
}
