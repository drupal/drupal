<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Number\NumberFieldTest.
 */

namespace Drupal\field\Tests\Number;

use Drupal\Component\Utility\Unicode;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the creation of numeric fields.
 *
 * @group field
 */
class NumberFieldTest extends WebTestBase {

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = TRUE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'entity_test', 'field_ui');

  /**
   * A user with permission to view and manage entities and content types.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $web_user;

  protected function setUp() {
    parent::setUp();

    $this->web_user = $this->drupalCreateUser(array('view test entity', 'administer entity_test content', 'administer content types', 'administer node fields', 'administer node display', 'bypass node access'));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Test decimal field.
   */
  function testNumberDecimalField() {
    // Create a field with settings to validate.
    $field_name = Unicode::strtolower($this->randomMachineName());
    entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'decimal',
      'settings' => array(
        'precision' => 8, 'scale' => 4,
      )
    ))->save();
    entity_create('field_config', array(
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
      "{$field_name}[0][value]" => $value,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)), 'Entity was created');
    $this->assertRaw($value, 'Value is displayed.');

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
   * Test integer field.
   */
  function testNumberIntegerField() {
    $minimum = rand(-4000, -2000);
    $maximum = rand(2000, 4000);

    // Create a field with settings to validate.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $storage = entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'integer',
    ));
    $storage->save();

    entity_create('field_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'settings' => array(
        'min' => $minimum, 'max' => $maximum,
      )
    ))->save();

    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => 'number',
        'settings' => array(
          'placeholder' => '4'
        ),
      ))
      ->save();
    entity_get_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => 'number_integer',
      ))
      ->save();

    // Check the storage schema.
    $expected = array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          'not null' => FALSE,
          'unsigned' => '',
          'size' => 'normal'
        ),
      ),
      'unique keys' => array(),
      'indexes' => array(),
      'foreign keys' => array()
    );
    $this->assertEqual($storage->getSchema(), $expected);

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value]", '', 'Widget is displayed');
    $this->assertRaw('placeholder="4"');

    // Submit a valid integer
    $value = rand($minimum, $maximum);
    $edit = array(
      "{$field_name}[0][value]" => $value,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)), 'Entity was created');

    // Try to set a value below the minimum value
    $this->drupalGet('entity_test/add');
    $edit = array(
      "{$field_name}[0][value]" => $minimum - 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('%name must be higher than or equal to %minimum.', array('%name' => $field_name, '%minimum' => $minimum)), 'Correctly failed to save integer value less than minimum allowed value.');

    // Try to set a decimal value
    $this->drupalGet('entity_test/add');
    $edit = array(
      "{$field_name}[0][value]" => 1.5,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('%name is not a valid number.', array('%name' => $field_name)), 'Correctly failed to save decimal value to integer field.');

    // Try to set a value above the maximum value
    $this->drupalGet('entity_test/add');
    $edit = array(
      "{$field_name}[0][value]" => $maximum + 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('%name must be lower than or equal to %maximum.', array('%name' => $field_name, '%maximum' => $maximum)), 'Correctly failed to save integer value greater than maximum allowed value.');

    // Test with valid entries.
    $valid_entries = array(
      '-1234',
      '0',
      '1234',
    );

    foreach ($valid_entries as $valid_entry) {
      $this->drupalGet('entity_test/add');
      $edit = array(
        "{$field_name}[0][value]" => $valid_entry,
      );
      $this->drupalPostForm(NULL, $edit, t('Save'));
      preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
      $id = $match[1];
      $this->assertText(t('entity_test @id has been created.', array('@id' => $id)), 'Entity was created');
      $this->assertRaw($valid_entry, 'Value is displayed.');
    }
  }

  /**
  * Test float field.
  */
  function testNumberFloatField() {
    // Create a field with settings to validate.
    $field_name = Unicode::strtolower($this->randomMachineName());
    entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'float',
    ))->save();

    entity_create('field_config', array(
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
      $this->assertRaw(t('%name must be a number.', array('%name' => $field_name)), 'Correctly failed to save float value with more than one decimal point.');
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
      $this->assertRaw(t('%name must be a number.', array('%name' => $field_name)), 'Correctly failed to save float value with minus sign in the wrong position.');
    }
  }

  /**
   * Test default formatter behavior
   */
  function testNumberFormatter() {
    $type = Unicode::strtolower($this->randomMachineName());
    $float_field = Unicode::strtolower($this->randomMachineName());
    $integer_field = Unicode::strtolower($this->randomMachineName());
    $thousand_separators = array('', '.', ',', ' ', chr(8201), "'");
    $decimal_separators = array('.', ',');
    $prefix = $this->randomMachineName();
    $suffix = $this->randomMachineName();
    $random_float = rand(0,pow(10,6));
    $random_integer = rand(0, pow(10,6));

    // Create a content type containing float and integer fields.
    $this->drupalCreateContentType(array('type' => $type));

    entity_create('field_storage_config', array(
      'field_name' => $float_field,
      'entity_type' => 'node',
      'type' => 'float',
    ))->save();

    entity_create('field_storage_config', array(
      'field_name' => $integer_field,
      'entity_type' => 'node',
      'type' => 'integer',
    ))->save();

    entity_create('field_config', array(
      'field_name' => $float_field,
      'entity_type' => 'node',
      'bundle' => $type,
      'settings' => array(
        'prefix' => $prefix,
        'suffix' => $suffix
      ),
    ))->save();

    entity_create('field_config', array(
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
      'title' => $this->randomMachineName(),
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

    // Configure number_decimal formatter for the 'float' field type.
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
