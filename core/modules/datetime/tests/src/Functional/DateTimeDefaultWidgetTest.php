<?php

namespace Drupal\Tests\datetime\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;

/**
 * Tests Datetime field functionality.
 *
 * @group datetime
 */
class DateTimeFieldDefaultWidgetTest extends DateTestBase {

  /**
   * The default display settings to use for the formatters.
   *
   * @var array
   *
   * @todo Probably want to move into DTB:createField?
   */
  protected $defaultSettings = ['timezone_override' => ''];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Tests date field functionality.
   */
  public function testDateField() {
    // Create a field with settings to validate.
    $this->createField('datetime', ['datetime_type' => DateTimeItem::DATETIME_TYPE_DATE], 'datetime_default', [], 'datetime_default', []);

    // Loop through defined time zones to test that date-only fields work at the
    // extremes.
    foreach (static::$timezones as $timezone) {

      $this->setSiteTimezone($timezone);
      $this->assertEquals($timezone, $this->config('system.date')->get('timezone.default'), 'Time zone set to ' . $timezone);

      // Display creation form.
      $this->drupalGet('entity_test/add');
      $this->assertSession()->fieldValueEquals("{$this->field_name}[0][value][date]", '');
      $this->assertSession()->elementExists('xpath', '//*[@id="edit-' . $this->field_name . '-wrapper"]//label[contains(@class,"js-form-required")]');
      $this->assertSession()->fieldNotExists("{$this->field_name}[0][value][time]");
      // ARIA described-by.
      $this->assertSession()->elementExists('xpath', '//input[@aria-describedby="edit-' . $this->field_name . '-0-value--description"]');
      $this->assertSession()->elementExists('xpath', '//div[@id="edit-' . $this->field_name . '-0-value--description"]');

      // Build up a date in the UTC time zone. Note that using this will also
      // mimic the user in a different time zone simply entering '2012-12-31' via
      // the UI.
      $value = '2012-12-31 00:00:00';
      $date = new DrupalDateTime($value, DateTimeItemInterface::STORAGE_TIMEZONE);

      // Submit a valid date and ensure it is accepted.
      $date_format = DateFormat::load('html_date')->getPattern();
      $time_format = DateFormat::load('html_time')->getPattern();

      $edit = [
        "{$this->field_name}[0][value][date]" => $date->format($date_format),
      ];
      $this->submitForm($edit, 'Save');
      preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
      $id = $match[1];
      $this->assertText('entity_test ' . $id . ' has been created.');
      $this->assertRaw($date->format($date_format));
      $this->assertNoRaw($date->format($time_format));

      // Verify the date doesn't change if using a time zone that is UTC+12 when
      // the entity is edited through the form.
      $entity = EntityTest::load($id);
      $this->assertEqual('2012-12-31', $entity->{$this->field_name}->value);
      $this->drupalGet('entity_test/manage/' . $id . '/edit');
      $this->submitForm([], 'Save');
      $this->drupalGet('entity_test/manage/' . $id . '/edit');
      $this->submitForm([], 'Save');
      $this->drupalGet('entity_test/manage/' . $id . '/edit');
      $this->submitForm([], 'Save');
      $entity = EntityTest::load($id);
      $this->assertEqual('2012-12-31', $entity->{$this->field_name}->value);
    }
  }

  /**
   * Tests date and time field.
   */
  public function testDatetimeField() {
    // Create a field with settings to validate.
    $this->createField('datetime', ['datetime_type' => DateTimeItem::DATETIME_TYPE_DATETIME], 'datetime_default', [], 'datetime_default', []);

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$this->field_name}[0][value][date]", '');
    $this->assertSession()->fieldValueEquals("{$this->field_name}[0][value][time]", '');
    $this->assertSession()->elementTextContains('xpath', '//fieldset[@id="edit-' . $this->field_name . '-0"]/legend', $this->field_label);
    $this->assertSession()->elementExists('xpath', '//fieldset[@aria-describedby="edit-' . $this->field_name . '-0--description"]');
    $this->assertSession()->elementExists('xpath', '//div[@id="edit-' . $this->field_name . '-0--description"]');

    // Build up a date in the UTC time zone.
    $value = '2012-12-31 00:00:00';
    $date = new DrupalDateTime($value, 'UTC');

    // Update the time zone to the system default.
    $date->setTimezone(timezone_open(date_default_timezone_get()));

    // Submit a valid date and ensure it is accepted.
    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();

    $edit = [
      "{$this->field_name}[0][value][date]" => $date->format($date_format),
      "{$this->field_name}[0][value][time]" => $date->format($time_format),
    ];
    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText('entity_test ' . $id . ' has been created.');
    $this->assertRaw($date->format($date_format));
    $this->assertRaw($date->format($time_format));
  }

  /**
   * Test default value functionality.
   */
  public function testDefaultValue() {
    // @todo Don't get why we are using a whole new content type here?
    // Create a test content type.
    $this->drupalCreateContentType(['type' => 'date_content']);

    // Create a field storage with settings to validate.
    $field_name = mb_strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => ['datetime_type' => 'date'],
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'date_content',
    ]);
    $field->save();

    // Loop through defined time zones to test that date-only defaults work at
    // the extremes.
    foreach (static::$timezones as $timezone) {

      $this->setSiteTimezone($timezone);
      $this->assertEquals($timezone, $this->config('system.date')->get('timezone.default'), 'Time zone set to ' . $timezone);

      // Set now as default_value.
      $field_edit = [
        'default_value_input[default_date_type]' => 'now',
      ];
      $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name, $field_edit, 'Save settings');

      // Check that default value is selected in default value form.
      $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
      $this->assertTrue($this->assertSession()->optionExists('edit-default-value-input-default-date-type', 'now')->isSelected());
      // Check that the relative default value is empty.
      $this->assertSession()->fieldValueEquals('default_value_input[default_date]', '');

      // Check if default_date has been stored successfully.
      $config_entity = $this->config('field.field.node.date_content.' . $field_name)
        ->get();
      $this->assertEqual($config_entity['default_value'][0], [
        'default_date_type' => 'now',
        'default_date' => 'now',
      ], 'Default value has been stored successfully');

      // Clear field cache in order to avoid stale cache values.
      \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

      // Create a new node to check that datetime field default value is today.
      $new_node = Node::create(['type' => 'date_content']);
      $expected_date = new DrupalDateTime('now', date_default_timezone_get());
      $this->assertEqual($new_node->get($field_name)
        ->offsetGet(0)->value, $expected_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT));

      // Set an invalid relative default_value to test validation.
      $field_edit = [
        'default_value_input[default_date_type]' => 'relative',
        'default_value_input[default_date]' => 'invalid date',
      ];
      $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name, $field_edit, 'Save settings');

      $this->assertText('The relative date value entered is invalid.');

      // Set a relative default_value.
      $field_edit = [
        'default_value_input[default_date_type]' => 'relative',
        'default_value_input[default_date]' => '+90 days',
      ];
      $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name, $field_edit, 'Save settings');

      // Check that default value is selected in default value form.
      $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
      $this->assertTrue($this->assertSession()->optionExists('edit-default-value-input-default-date-type', 'relative')->isSelected());
      // Check that the relative default value is displayed.
      $this->assertSession()->fieldValueEquals('default_value_input[default_date]', '+90 days');

      // Check if default_date has been stored successfully.
      $config_entity = $this->config('field.field.node.date_content.' . $field_name)
        ->get();
      $this->assertEqual($config_entity['default_value'][0], [
        'default_date_type' => 'relative',
        'default_date' => '+90 days',
      ], 'Default value has been stored successfully');

      // Clear field cache in order to avoid stale cache values.
      \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

      // Create a new node to check that datetime field default value is +90
      // days.
      $new_node = Node::create(['type' => 'date_content']);
      $expected_date = new DrupalDateTime('+90 days', date_default_timezone_get());
      $this->assertEqual($new_node->get($field_name)
        ->offsetGet(0)->value, $expected_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT));

      // Remove default value.
      $field_edit = [
        'default_value_input[default_date_type]' => '',
      ];
      $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name, $field_edit, 'Save settings');

      // Check that default value is selected in default value form.
      $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
      $this->assertTrue($this->assertSession()->optionExists('edit-default-value-input-default-date-type', '')->isSelected());
      // Check that the relative default value is empty.
      $this->assertSession()->fieldValueEquals('default_value_input[default_date]', '');

      // Check if default_date has been stored successfully.
      $config_entity = $this->config('field.field.node.date_content.' . $field_name)
        ->get();
      $this->assertTrue(empty($config_entity['default_value']), 'Empty default value has been stored successfully');

      // Clear field cache in order to avoid stale cache values.
      \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

      // Create a new node to check that datetime field default value is not
      // set.
      $new_node = Node::create(['type' => 'date_content']);
      $this->assertNull($new_node->get($field_name)->value, 'Default value is not set');
    }
  }

  /**
   * Test that invalid values are caught and marked as invalid.
   *
   * @todo Prob want to use a provider.
   */
  public function testInvalidField() {
    // Create a field with settings to validate.
    $this->createField('datetime', ['datetime_type' => DateTimeItem::DATETIME_TYPE_DATETIME], 'datetime_default', [], 'datetime_default', []);

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$this->field_name}[0][value][date]", '');
    $this->assertSession()->fieldValueEquals("{$this->field_name}[0][value][time]", '');

    // Submit invalid dates and ensure they is not accepted.
    $date_value = '';
    $edit = [
      "{$this->field_name}[0][value][date]" => $date_value,
      "{$this->field_name}[0][value][time]" => '12:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertText('date is invalid', 'Empty date value has been caught.');

    $date_value = 'aaaa-12-01';
    $edit = [
      "{$this->field_name}[0][value][date]" => $date_value,
      "{$this->field_name}[0][value][time]" => '00:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertText('date is invalid', new FormattableMarkup('Invalid year value %date has been caught.', ['%date' => $date_value]));

    $date_value = '2012-75-01';
    $edit = [
      "{$this->field_name}[0][value][date]" => $date_value,
      "{$this->field_name}[0][value][time]" => '00:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertText('date is invalid', new FormattableMarkup('Invalid month value %date has been caught.', ['%date' => $date_value]));

    $date_value = '2012-12-99';
    $edit = [
      "{$this->field_name}[0][value][date]" => $date_value,
      "{$this->field_name}[0][value][time]" => '00:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertText('date is invalid', new FormattableMarkup('Invalid day value %date has been caught.', ['%date' => $date_value]));

    $date_value = '2012-12-01';
    $time_value = '';
    $edit = [
      "{$this->field_name}[0][value][date]" => $date_value,
      "{$this->field_name}[0][value][time]" => $time_value,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertText('date is invalid', 'Empty time value has been caught.');

    $date_value = '2012-12-01';
    $time_value = '49:00:00';
    $edit = [
      "{$this->field_name}[0][value][date]" => $date_value,
      "{$this->field_name}[0][value][time]" => $time_value,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertText('date is invalid', new FormattableMarkup('Invalid hour value %time has been caught.', ['%time' => $time_value]));

    $date_value = '2012-12-01';
    $time_value = '12:99:00';
    $edit = [
      "{$this->field_name}[0][value][date]" => $date_value,
      "{$this->field_name}[0][value][time]" => $time_value,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertText('date is invalid', new FormattableMarkup('Invalid minute value %time has been caught.', ['%time' => $time_value]));

    $date_value = '2012-12-01';
    $time_value = '12:15:99';
    $edit = [
      "{$this->field_name}[0][value][date]" => $date_value,
      "{$this->field_name}[0][value][time]" => $time_value,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertText('date is invalid', new FormattableMarkup('Invalid second value %time has been caught.', ['%time' => $time_value]));
  }

}
