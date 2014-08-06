<?php

/**
 * @file
 * Contains \Drupal\datetime\Tests\DateTimeFieldTest.
 */

namespace Drupal\datetime\Tests;

use Drupal\entity\Entity\EntityViewDisplay;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Tests Datetime field functionality.
 *
 * @group datetime
 */
class DateTimeFieldTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'entity_test', 'datetime', 'field_ui');

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The instance used in this test class.
   *
   * @var \Drupal\field\Entity\FieldInstanceConfig
   */
  protected $instance;

  function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array(
      'access content',
      'view test entity',
      'administer entity_test content',
      'administer content types',
      'administer node fields',
    ));
    $this->drupalLogin($web_user);

    // Create a field with settings to validate.
    $this->fieldStorage = entity_create('field_storage_config', array(
      'name' => drupal_strtolower($this->randomMachineName()),
      'entity_type' => 'entity_test',
      'type' => 'datetime',
      'settings' => array('datetime_type' => 'date'),
    ));
    $this->fieldStorage->save();
    $this->instance = entity_create('field_instance_config', array(
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'required' => TRUE,
    ));
    $this->instance->save();

    entity_get_form_display($this->instance->entity_type, $this->instance->bundle, 'default')
      ->setComponent($this->fieldStorage->name, array(
        'type' => 'datetime_default',
      ))
      ->save();

    $this->display_options = array(
      'type' => 'datetime_default',
      'label' => 'hidden',
      'settings' => array('format_type' => 'medium'),
    );
    entity_get_display($this->instance->entity_type, $this->instance->bundle, 'full')
      ->setComponent($this->fieldStorage->name, $this->display_options)
      ->save();
  }

  /**
   * Tests date field functionality.
   */
  function testDateField() {
    $field_name = $this->fieldStorage->name;

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value][date]", '', 'Date element found.');
    $this->assertFieldByXPath('//*[@id="edit-' . $field_name . '-wrapper"]/h4[contains(@class, "form-required")]', TRUE, 'Required markup found');
    $this->assertNoFieldByName("{$field_name}[0][value][time]", '', 'Time element not found.');

    // Submit a valid date and ensure it is accepted.
    $value = '2012-12-31 00:00:00';
    $date = new DrupalDateTime($value);
    $date_format = entity_load('date_format', 'html_date')->getPattern();
    $time_format = entity_load('date_format', 'html_time')->getPattern();

    $edit = array(
      'user_id' => 1,
      'name' => $this->randomMachineName(),
      "{$field_name}[0][value][date]" => $date->format($date_format),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));
    $this->assertRaw($date->format($date_format));
    $this->assertNoRaw($date->format($time_format));

    // The expected values will use the default time.
    datetime_date_default_time($date);

    // Verify that the date is output according to the formatter settings.
    $options = array(
      'format_type' => array('short', 'medium', 'long'),
    );
    foreach ($options as $setting => $values) {
      foreach ($values as $new_value) {
        // Update the entity display settings.
        $this->display_options['settings'] = array($setting => $new_value);
        entity_get_display($this->instance->entity_type, $this->instance->bundle, 'full')
          ->setComponent($field_name, $this->display_options)
          ->save();

        $this->renderTestEntity($id);
        switch ($setting) {
          case 'format_type':
            // Verify that a date is displayed.
            $expected = format_date($date->getTimestamp(), $new_value);
            $this->renderTestEntity($id);
            $this->assertText($expected, format_string('Formatted date field using %value format displayed as %expected.', array('%value' => $new_value, '%expected' => $expected)));
            break;
        }
      }
    }

    // Verify that the plain formatter works.
    $this->display_options['type'] = 'datetime_plain';
    entity_get_display($this->instance->entity_type, $this->instance->bundle, 'full')
      ->setComponent($field_name, $this->display_options)
      ->save();
    $expected = $date->format(DATETIME_DATE_STORAGE_FORMAT);
    $this->renderTestEntity($id);
    $this->assertText($expected, format_string('Formatted date field using plain format displayed as %expected.', array('%expected' => $expected)));
  }

  /**
   * Tests date and time field.
   */
  function testDatetimeField() {
    $field_name = $this->fieldStorage->name;
    // Change the field to a datetime field.
    $this->fieldStorage->settings['datetime_type'] = 'datetime';
    $this->fieldStorage->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value][date]", '', 'Date element found.');
    $this->assertFieldByName("{$field_name}[0][value][time]", '', 'Time element found.');

    // Submit a valid date and ensure it is accepted.
    $value = '2012-12-31 00:00:00';
    $date = new DrupalDateTime($value);
    $date_format = entity_load('date_format', 'html_date')->getPattern();
    $time_format = entity_load('date_format', 'html_time')->getPattern();

    $edit = array(
      'user_id' => 1,
      'name' => $this->randomMachineName(),
      "{$field_name}[0][value][date]" => $date->format($date_format),
      "{$field_name}[0][value][time]" => $date->format($time_format),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));
    $this->assertRaw($date->format($date_format));
    $this->assertRaw($date->format($time_format));

    // Verify that the date is output according to the formatter settings.
    $options = array(
      'format_type' => array('short', 'medium', 'long'),
    );
    foreach ($options as $setting => $values) {
      foreach ($values as $new_value) {
        // Update the entity display settings.
        $this->display_options['settings'] = array($setting => $new_value);
        entity_get_display($this->instance->entity_type, $this->instance->bundle, 'full')
          ->setComponent($field_name, $this->display_options)
          ->save();

        $this->renderTestEntity($id);
        switch ($setting) {
          case 'format_type':
            // Verify that a date is displayed.
            $expected = format_date($date->getTimestamp(), $new_value);
            $this->renderTestEntity($id);
            $this->assertText($expected, format_string('Formatted date field using %value format displayed as %expected.', array('%value' => $new_value, '%expected' => $expected)));
            break;
        }
      }
    }

    // Verify that the plain formatter works.
    $this->display_options['type'] = 'datetime_plain';
    entity_get_display($this->instance->entity_type, $this->instance->bundle, 'full')
      ->setComponent($field_name, $this->display_options)
      ->save();
    $expected = $date->format(DATETIME_DATETIME_STORAGE_FORMAT);
    $this->renderTestEntity($id);
    $this->assertText($expected, format_string('Formatted date field using plain format displayed as %expected.', array('%expected' => $expected)));
  }

  /**
   * Tests Date List Widget functionality.
   */
  function testDatelistWidget() {
    $field_name = $this->fieldStorage->name;
    // Change the field to a datetime field.
    $this->fieldStorage->settings['datetime_type'] = 'datetime';
    $this->fieldStorage->save();

    // Change the widget to a datelist widget.
    entity_get_form_display($this->instance->entity_type, $this->instance->bundle, 'default')
      ->setComponent($field_name, array(
        'type' => 'datetime_datelist',
        'settings' => array(
          'increment' => 1,
          'date_order' => 'YMD',
          'time_type' => '12',
        ),
      ))
      ->save();
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Display creation form.
    $this->drupalGet('entity_test/add');

    $this->assertFieldByXPath("//*[@id=\"edit-$field_name-0-value-year\"]", NULL, 'Year element found.');
    $this->assertOptionSelected("edit-$field_name-0-value-year", '', 'No year selected.');
    $this->assertFieldByXPath("//*[@id=\"edit-$field_name-0-value-month\"]", NULL, 'Month element found.');
    $this->assertOptionSelected("edit-$field_name-0-value-month", '', 'No month selected.');
    $this->assertFieldByXPath("//*[@id=\"edit-$field_name-0-value-day\"]", NULL, 'Day element found.');
    $this->assertOptionSelected("edit-$field_name-0-value-day", '', 'No day selected.');
    $this->assertFieldByXPath("//*[@id=\"edit-$field_name-0-value-hour\"]", NULL, 'Hour element found.');
    $this->assertOptionSelected("edit-$field_name-0-value-hour", '', 'No hour selected.');
    $this->assertFieldByXPath("//*[@id=\"edit-$field_name-0-value-minute\"]", NULL, 'Minute element found.');
    $this->assertOptionSelected("edit-$field_name-0-value-minute", '', 'No minute selected.');
    $this->assertNoFieldByXPath("//*[@id=\"edit-$field_name-0-value-second\"]", NULL, 'Second element not found.');
    $this->assertFieldByXPath("//*[@id=\"edit-$field_name-0-value-ampm\"]", NULL, 'AMPM element found.');
    $this->assertOptionSelected("edit-$field_name-0-value-ampm", '', 'No ampm selected.');

    // Submit a valid date and ensure it is accepted.
    $date_value = array('year' => 2012, 'month' => 12, 'day' => 31, 'hour' => 5, 'minute' => 15);

    $edit = array(
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    );
    // Add the ampm indicator since we are testing 12 hour time.
    $date_value['ampm'] = 'am';
    foreach ($date_value as $part => $value) {
      $edit["{$field_name}[0][value][$part]"] = $value;
    }

    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));

    $this->assertOptionSelected("edit-$field_name-0-value-year", '2012', 'Correct year selected.');
    $this->assertOptionSelected("edit-$field_name-0-value-month", '12', 'Correct month selected.');
    $this->assertOptionSelected("edit-$field_name-0-value-day", '31', 'Correct day selected.');
    $this->assertOptionSelected("edit-$field_name-0-value-hour", '5', 'Correct hour selected.');
    $this->assertOptionSelected("edit-$field_name-0-value-minute", '15', 'Correct minute selected.');
    $this->assertOptionSelected("edit-$field_name-0-value-ampm", 'am', 'Correct ampm selected.');
  }

  /**
   * Test default value functionality.
   */
  function testDefaultValue() {
    // Create a test content type.
    $this->drupalCreateContentType(array('type' => 'date_content'));

    // Create a field storage with settings to validate.
    $field_storage = entity_create('field_storage_config', array(
      'name' => drupal_strtolower($this->randomMachineName()),
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => array('datetime_type' => 'date'),
    ));
    $field_storage->save();

    $instance = entity_create('field_instance_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'date_content',
    ));
    $instance->save();

    // Set now as default_value.
    $instance_edit = array(
      'default_value_input[default_date]' => 'now',
    );
    $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_storage->name, $instance_edit, t('Save settings'));

    // Check that default value is selected in default value form.
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_storage->name);
    $this->assertRaw('<option value="now" selected="selected">The current date</option>', 'The default value is selected in instance settings page');

    // Check if default_date has been stored successfully.
    $config_entity = $this->container->get('config.factory')->get('field.instance.node.date_content.' . $field_storage->name)->get();
    $this->assertEqual($config_entity['default_value'][0]['default_date'], 'now', 'Default value has been stored successfully');

    // Clear field cache in order to avoid stale cache values.
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Create a new node to check that datetime field default value is today.
    $new_node = entity_create('node', array('type' => 'date_content'));
    $expected_date = new DrupalDateTime('now', DATETIME_STORAGE_TIMEZONE);
    $this->assertEqual($new_node->get($field_storage->name)->offsetGet(0)->value, $expected_date->format(DATETIME_DATE_STORAGE_FORMAT));

    // Remove default value.
    $instance_edit = array(
      'default_value_input[default_date]' => '',
    );
    $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_storage->name, $instance_edit, t('Save settings'));

    // Check that default value is selected in default value form.
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_storage->name);
    $this->assertRaw('<option value="" selected="selected">' . t('- None -') . '</option>', 'The default value is selected in instance settings page');

    // Check if default_date has been stored successfully.
    $config_entity = $this->container->get('config.factory')->get('field.instance.node.date_content.' . $field_storage->name)->get();
    $this->assertTrue(empty($config_entity['default_value']), 'Empty default value has been stored successfully');

    // Clear field cache in order to avoid stale cache values.
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Create a new node to check that datetime field default value is today.
    $new_node = entity_create('node', array('type' => 'date_content'));
    $this->assertNull($new_node->get($field_storage->name)->offsetGet(0)->value, 'Default value is not set');
  }

  /**
   * Test that invalid values are caught and marked as invalid.
   */
  function testInvalidField() {

    // Change the field to a datetime field.
    $this->fieldStorage->settings['datetime_type'] = 'datetime';
    $this->fieldStorage->save();
    $field_name = $this->fieldStorage->name;

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value][date]", '', 'Date element found.');
    $this->assertFieldByName("{$field_name}[0][value][time]", '', 'Time element found.');

    // Submit invalid dates and ensure they is not accepted.
    $date_value = '';
    $edit = array(
      "{$field_name}[0][value][date]" => $date_value,
      "{$field_name}[0][value][time]" => '12:00:00',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', 'Empty date value has been caught.');

    $date_value = 'aaaa-12-01';
    $edit = array(
      "{$field_name}[0][value][date]" => $date_value,
      "{$field_name}[0][value][time]" => '00:00:00',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', format_string('Invalid year value %date has been caught.', array('%date' => $date_value)));

    $date_value = '2012-75-01';
    $edit = array(
      "{$field_name}[0][value][date]" => $date_value,
      "{$field_name}[0][value][time]" => '00:00:00',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', format_string('Invalid month value %date has been caught.', array('%date' => $date_value)));

    $date_value = '2012-12-99';
    $edit = array(
      "{$field_name}[0][value][date]" => $date_value,
      "{$field_name}[0][value][time]" => '00:00:00',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', format_string('Invalid day value %date has been caught.', array('%date' => $date_value)));

    $date_value = '2012-12-01';
    $time_value = '';
    $edit = array(
      "{$field_name}[0][value][date]" => $date_value,
      "{$field_name}[0][value][time]" => $time_value,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', 'Empty time value has been caught.');

    $date_value = '2012-12-01';
    $time_value = '49:00:00';
    $edit = array(
      "{$field_name}[0][value][date]" => $date_value,
      "{$field_name}[0][value][time]" => $time_value,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', format_string('Invalid hour value %time has been caught.', array('%time' => $time_value)));

    $date_value = '2012-12-01';
    $time_value = '12:99:00';
    $edit = array(
      "{$field_name}[0][value][date]" => $date_value,
      "{$field_name}[0][value][time]" => $time_value,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', format_string('Invalid minute value %time has been caught.', array('%time' => $time_value)));

    $date_value = '2012-12-01';
    $time_value = '12:15:99';
    $edit = array(
      "{$field_name}[0][value][date]" => $date_value,
      "{$field_name}[0][value][time]" => $time_value,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', format_string('Invalid second value %time has been caught.', array('%time' => $time_value)));
  }

  /**
   * Renders a entity_test and sets the output in the internal browser.
   *
   * @param int $id
   *   The entity_test ID to render.
   * @param string $view_mode
   *   (optional) The view mode to use for rendering. Defaults to 'full'.
   * @param bool $reset
   *   (optional) Whether to reset the entity_test controller cache. Defaults to
   *   TRUE to simplify testing.
   */
  protected function renderTestEntity($id, $view_mode = 'full', $reset = TRUE) {
    if ($reset) {
      entity_get_controller('entity_test')->resetCache(array($id));
    }
    $entity = entity_load('entity_test', $id);
    $display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
    $build = $display->build($entity);
    $output = drupal_render($build);
    $this->drupalSetContent($output);
    $this->verbose($output);
  }

}
