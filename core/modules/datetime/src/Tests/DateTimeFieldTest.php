<?php

namespace Drupal\datetime\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

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
   * The default display settings to use for the formatters.
   */
  protected $defaultSettings;

  /**
   * An array of display options to pass to entity_get_display()
   *
   * @var array
   */
  protected $displayOptions;

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set an explicit site timezone, and disallow per-user timezones.
    $this->config('system.date')
      ->set('timezone.user.configurable', 0)
      ->set('timezone.default', 'Asia/Tokyo')
      ->save();

    $web_user = $this->drupalCreateUser(array(
      'access content',
      'view test entity',
      'administer entity_test content',
      'administer entity_test form display',
      'administer content types',
      'administer node fields',
    ));
    $this->drupalLogin($web_user);

    // Create a field with settings to validate.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $this->fieldStorage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'datetime',
      'settings' => array('datetime_type' => 'date'),
    ));
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'required' => TRUE,
    ]);
    $this->field->save();

    entity_get_form_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'default')
      ->setComponent($field_name, array(
        'type' => 'datetime_default',
      ))
      ->save();

    $this->defaultSettings = array(
      'timezone_override' => '',
    );

    $this->displayOptions = array(
      'type' => 'datetime_default',
      'label' => 'hidden',
      'settings' => array('format_type' => 'medium') + $this->defaultSettings,
    );
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
  }

  /**
   * Tests date field functionality.
   */
  function testDateField() {
    $field_name = $this->fieldStorage->getName();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value][date]", '', 'Date element found.');
    $this->assertFieldByXPath('//*[@id="edit-' . $field_name . '-wrapper"]/h4[contains(@class, "js-form-required")]', TRUE, 'Required markup found');
    $this->assertNoFieldByName("{$field_name}[0][value][time]", '', 'Time element not found.');

    // Build up a date in the UTC timezone.
    $value = '2012-12-31 00:00:00';
    $date = new DrupalDateTime($value, 'UTC');

    // The expected values will use the default time.
    datetime_date_default_time($date);

    // Update the timezone to the system default.
    $date->setTimezone(timezone_open(drupal_get_user_timezone()));

    // Submit a valid date and ensure it is accepted.
    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();

    $edit = array(
      "{$field_name}[0][value][date]" => $date->format($date_format),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));
    $this->assertRaw($date->format($date_format));
    $this->assertNoRaw($date->format($time_format));

    // Verify that the date is output according to the formatter settings.
    $options = array(
      'format_type' => array('short', 'medium', 'long'),
    );
    foreach ($options as $setting => $values) {
      foreach ($values as $new_value) {
        // Update the entity display settings.
        $this->displayOptions['settings'] = array($setting => $new_value) + $this->defaultSettings;
        entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
          ->setComponent($field_name, $this->displayOptions)
          ->save();

        $this->renderTestEntity($id);
        switch ($setting) {
          case 'format_type':
            // Verify that a date is displayed.
            $expected = format_date($date->getTimestamp(), $new_value);
            $expected_iso = format_date($date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
            $this->renderTestEntity($id);
            $this->assertFieldByXPath('//time[@datetime="' . $expected_iso . '"]', $expected, SafeMarkup::format('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', array('%value' => $new_value, '%expected' => $expected, '%expected_iso' => $expected_iso)));
            break;
        }
      }
    }

    // Verify that the plain formatter works.
    $this->displayOptions['type'] = 'datetime_plain';
    $this->displayOptions['settings'] = $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $date->format(DATETIME_DATE_STORAGE_FORMAT);
    $this->renderTestEntity($id);
    $this->assertText($expected, SafeMarkup::format('Formatted date field using plain format displayed as %expected.', array('%expected' => $expected)));

    // Verify that the 'datetime_custom' formatter works.
    $this->displayOptions['type'] = 'datetime_custom';
    $this->displayOptions['settings'] = array('date_format' => 'm/d/Y') + $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $date->format($this->displayOptions['settings']['date_format']);
    $this->renderTestEntity($id);
    $this->assertText($expected, SafeMarkup::format('Formatted date field using datetime_custom format displayed as %expected.', array('%expected' => $expected)));

    // Verify that the 'datetime_time_ago' formatter works for intervals in the
    // past.  First update the test entity so that the date difference always
    // has the same interval.  Since the database always stores UTC, and the
    // interval will use this, force the test date to use UTC and not the local
    // or user timezome.
    $timestamp = REQUEST_TIME - 87654321;
    $entity = EntityTest::load($id);
    $field_name = $this->fieldStorage->getName();
    $date = DrupalDateTime::createFromTimestamp($timestamp, 'UTC');
    $entity->{$field_name}->value = $date->format($date_format);
    $entity->save();

    $this->displayOptions['type'] = 'datetime_time_ago';
    $this->displayOptions['settings'] = array(
      'future_format' => '@interval in the future',
      'past_format' => '@interval in the past',
      'granularity' => 3,
    );
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = SafeMarkup::format($this->displayOptions['settings']['past_format'], [
      '@interval' => \Drupal::service('date.formatter')->formatTimeDiffSince($timestamp, ['granularity' => $this->displayOptions['settings']['granularity']])
    ]);
    $this->renderTestEntity($id);
    $this->assertText($expected, SafeMarkup::format('Formatted date field using datetime_time_ago format displayed as %expected.', array('%expected' => $expected)));

    // Verify that the 'datetime_time_ago' formatter works for intervals in the
    // future.  First update the test entity so that the date difference always
    // has the same interval.  Since the database always stores UTC, and the
    // interval will use this, force the test date to use UTC and not the local
    // or user timezome.
    $timestamp = REQUEST_TIME + 87654321;
    $entity = EntityTest::load($id);
    $field_name = $this->fieldStorage->getName();
    $date = DrupalDateTime::createFromTimestamp($timestamp, 'UTC');
    $entity->{$field_name}->value = $date->format($date_format);
    $entity->save();

    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = SafeMarkup::format($this->displayOptions['settings']['future_format'], [
      '@interval' => \Drupal::service('date.formatter')->formatTimeDiffUntil($timestamp, ['granularity' => $this->displayOptions['settings']['granularity']])
    ]);
    $this->renderTestEntity($id);
    $this->assertText($expected, SafeMarkup::format('Formatted date field using datetime_time_ago format displayed as %expected.', array('%expected' => $expected)));
  }

  /**
   * Tests date and time field.
   */
  function testDatetimeField() {
    $field_name = $this->fieldStorage->getName();
    // Change the field to a datetime field.
    $this->fieldStorage->setSetting('datetime_type', 'datetime');
    $this->fieldStorage->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value][date]", '', 'Date element found.');
    $this->assertFieldByName("{$field_name}[0][value][time]", '', 'Time element found.');

    // Build up a date in the UTC timezone.
    $value = '2012-12-31 00:00:00';
    $date = new DrupalDateTime($value, 'UTC');

    // Update the timezone to the system default.
    $date->setTimezone(timezone_open(drupal_get_user_timezone()));

    // Submit a valid date and ensure it is accepted.
    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();

    $edit = array(
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
        $this->displayOptions['settings'] = array($setting => $new_value) + $this->defaultSettings;
        entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
          ->setComponent($field_name, $this->displayOptions)
          ->save();

        $this->renderTestEntity($id);
        switch ($setting) {
          case 'format_type':
            // Verify that a date is displayed.
            $expected = format_date($date->getTimestamp(), $new_value);
            $expected_iso = format_date($date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
            $this->renderTestEntity($id);
            $this->assertFieldByXPath('//time[@datetime="' . $expected_iso . '"]', $expected, SafeMarkup::format('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', array('%value' => $new_value, '%expected' => $expected, '%expected_iso' => $expected_iso)));
            break;
        }
      }
    }

    // Verify that the plain formatter works.
    $this->displayOptions['type'] = 'datetime_plain';
    $this->displayOptions['settings'] = $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $date->format(DATETIME_DATETIME_STORAGE_FORMAT);
    $this->renderTestEntity($id);
    $this->assertText($expected, SafeMarkup::format('Formatted date field using plain format displayed as %expected.', array('%expected' => $expected)));

    // Verify that the 'datetime_custom' formatter works.
    $this->displayOptions['type'] = 'datetime_custom';
    $this->displayOptions['settings'] = array('date_format' => 'm/d/Y g:i:s A') + $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $date->format($this->displayOptions['settings']['date_format']);
    $this->renderTestEntity($id);
    $this->assertText($expected, SafeMarkup::format('Formatted date field using datetime_custom format displayed as %expected.', array('%expected' => $expected)));

    // Verify that the 'timezone_override' setting works.
    $this->displayOptions['type'] = 'datetime_custom';
    $this->displayOptions['settings'] = array('date_format' => 'm/d/Y g:i:s A', 'timezone_override' => 'America/New_York') + $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $date->format($this->displayOptions['settings']['date_format'], array('timezone' => 'America/New_York'));
    $this->renderTestEntity($id);
    $this->assertText($expected, SafeMarkup::format('Formatted date field using datetime_custom format displayed as %expected.', array('%expected' => $expected)));

    // Verify that the 'datetime_time_ago' formatter works for intervals in the
    // past.  First update the test entity so that the date difference always
    // has the same interval.  Since the database always stores UTC, and the
    // interval will use this, force the test date to use UTC and not the local
    // or user timezome.
    $timestamp = REQUEST_TIME - 87654321;
    $entity = EntityTest::load($id);
    $field_name = $this->fieldStorage->getName();
    $date = DrupalDateTime::createFromTimestamp($timestamp, 'UTC');
    $entity->{$field_name}->value = $date->format(DATETIME_DATETIME_STORAGE_FORMAT);
    $entity->save();

    $this->displayOptions['type'] = 'datetime_time_ago';
    $this->displayOptions['settings'] = array(
      'future_format' => '@interval from now',
      'past_format' => '@interval earlier',
      'granularity' => 3,
    );
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = SafeMarkup::format($this->displayOptions['settings']['past_format'], [
      '@interval' => \Drupal::service('date.formatter')->formatTimeDiffSince($timestamp, ['granularity' => $this->displayOptions['settings']['granularity']])
    ]);
    $this->renderTestEntity($id);
    $this->assertText($expected, SafeMarkup::format('Formatted date field using datetime_time_ago format displayed as %expected.', array('%expected' => $expected)));

    // Verify that the 'datetime_time_ago' formatter works for intervals in the
    // future.  First update the test entity so that the date difference always
    // has the same interval.  Since the database always stores UTC, and the
    // interval will use this, force the test date to use UTC and not the local
    // or user timezome.
    $timestamp = REQUEST_TIME + 87654321;
    $entity = EntityTest::load($id);
    $field_name = $this->fieldStorage->getName();
    $date = DrupalDateTime::createFromTimestamp($timestamp, 'UTC');
    $entity->{$field_name}->value = $date->format(DATETIME_DATETIME_STORAGE_FORMAT);
    $entity->save();

    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = SafeMarkup::format($this->displayOptions['settings']['future_format'], [
      '@interval' => \Drupal::service('date.formatter')->formatTimeDiffUntil($timestamp, ['granularity' => $this->displayOptions['settings']['granularity']])
    ]);
    $this->renderTestEntity($id);
    $this->assertText($expected, SafeMarkup::format('Formatted date field using datetime_time_ago format displayed as %expected.', array('%expected' => $expected)));
  }

  /**
   * Tests Date List Widget functionality.
   */
  function testDatelistWidget() {
    $field_name = $this->fieldStorage->getName();

    // Ensure field is set to a date only field.
    $this->fieldStorage->setSetting('datetime_type', 'date');
    $this->fieldStorage->save();

    // Change the widget to a datelist widget.
    entity_get_form_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'default')
      ->setComponent($field_name, array(
        'type' => 'datetime_datelist',
        'settings' => array(
          'date_order' => 'YMD',
        ),
      ))
      ->save();
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Display creation form.
    $this->drupalGet('entity_test/add');

    // Assert that Hour and Minute Elements do not appear on Date Only
    $this->assertNoFieldByXPath("//*[@id=\"edit-$field_name-0-value-hour\"]", NULL, 'Hour element not found on Date Only.');
    $this->assertNoFieldByXPath("//*[@id=\"edit-$field_name-0-value-minute\"]", NULL, 'Minute element not found on Date Only.');

    // Go to the form display page to assert that increment option does not appear on Date Only
    $fieldEditUrl = 'entity_test/structure/entity_test/form-display';
    $this->drupalGet($fieldEditUrl);

    // Click on the widget settings button to open the widget settings form.
    $this->drupalPostAjaxForm(NULL, array(), $field_name . "_settings_edit");
    $xpathIncr = "//select[starts-with(@id, \"edit-fields-$field_name-settings-edit-form-settings-increment\")]";
    $this->assertNoFieldByXPath($xpathIncr, NULL, 'Increment element not found for Date Only.');

    // Change the field to a datetime field.
    $this->fieldStorage->setSetting('datetime_type', 'datetime');
    $this->fieldStorage->save();

    // Change the widget to a datelist widget.
    entity_get_form_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'default')
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

    // Go to the form display page to assert that increment option does appear on Date Time
    $fieldEditUrl = 'entity_test/structure/entity_test/form-display';
    $this->drupalGet($fieldEditUrl);

    // Click on the widget settings button to open the widget settings form.
    $this->drupalPostAjaxForm(NULL, array(), $field_name . "_settings_edit");
    $this->assertFieldByXPath($xpathIncr, NULL, 'Increment element found for Date and time.');

    // Display creation form.
    $this->drupalGet('entity_test/add');

    $this->assertFieldByXPath("//*[@id=\"edit-$field_name-0-value-year\"]", NULL, 'Year element found.');
    $this->assertOptionSelected("edit-$field_name-0-value-year", '', 'No year selected.');
    $this->assertOptionByText("edit-$field_name-0-value-year", t('Year'));
    $this->assertFieldByXPath("//*[@id=\"edit-$field_name-0-value-month\"]", NULL, 'Month element found.');
    $this->assertOptionSelected("edit-$field_name-0-value-month", '', 'No month selected.');
    $this->assertOptionByText("edit-$field_name-0-value-month", t('Month'));
    $this->assertFieldByXPath("//*[@id=\"edit-$field_name-0-value-day\"]", NULL, 'Day element found.');
    $this->assertOptionSelected("edit-$field_name-0-value-day", '', 'No day selected.');
    $this->assertOptionByText("edit-$field_name-0-value-day", t('Day'));
    $this->assertFieldByXPath("//*[@id=\"edit-$field_name-0-value-hour\"]", NULL, 'Hour element found.');
    $this->assertOptionSelected("edit-$field_name-0-value-hour", '', 'No hour selected.');
    $this->assertOptionByText("edit-$field_name-0-value-hour", t('Hour'));
    $this->assertFieldByXPath("//*[@id=\"edit-$field_name-0-value-minute\"]", NULL, 'Minute element found.');
    $this->assertOptionSelected("edit-$field_name-0-value-minute", '', 'No minute selected.');
    $this->assertOptionByText("edit-$field_name-0-value-minute", t('Minute'));
    $this->assertNoFieldByXPath("//*[@id=\"edit-$field_name-0-value-second\"]", NULL, 'Second element not found.');
    $this->assertFieldByXPath("//*[@id=\"edit-$field_name-0-value-ampm\"]", NULL, 'AMPM element found.');
    $this->assertOptionSelected("edit-$field_name-0-value-ampm", '', 'No ampm selected.');
    $this->assertOptionByText("edit-$field_name-0-value-ampm", t('AM/PM'));

    // Submit a valid date and ensure it is accepted.
    $date_value = array('year' => 2012, 'month' => 12, 'day' => 31, 'hour' => 5, 'minute' => 15);

    $edit = array();
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

    // Test the widget using increment other than 1 and 24 hour mode.
    entity_get_form_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'default')
      ->setComponent($field_name, array(
        'type' => 'datetime_datelist',
        'settings' => array(
          'increment' => 15,
          'date_order' => 'YMD',
          'time_type' => '24',
        ),
      ))
      ->save();
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Display creation form.
    $this->drupalGet('entity_test/add');

    // Other elements are unaffected by the changed settings.
    $this->assertFieldByXPath("//*[@id=\"edit-$field_name-0-value-hour\"]", NULL, 'Hour element found.');
    $this->assertOptionSelected("edit-$field_name-0-value-hour", '', 'No hour selected.');
    $this->assertNoFieldByXPath("//*[@id=\"edit-$field_name-0-value-ampm\"]", NULL, 'AMPM element not found.');

    // Submit a valid date and ensure it is accepted.
    $date_value = array('year' => 2012, 'month' => 12, 'day' => 31, 'hour' => 17, 'minute' => 15);

    $edit = array();
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
    $this->assertOptionSelected("edit-$field_name-0-value-hour", '17', 'Correct hour selected.');
    $this->assertOptionSelected("edit-$field_name-0-value-minute", '15', 'Correct minute selected.');

    // Test the widget for partial completion of fields.
    entity_get_form_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'default')
      ->setComponent($field_name, array(
        'type' => 'datetime_datelist',
        'settings' => array(
          'increment' => 1,
          'date_order' => 'YMD',
          'time_type' => '24',
        ),
      ))
      ->save();
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Test the widget for validation notifications.
    foreach ($this->datelistDataProvider() as $data) {
      list($date_value, $expected) = $data;

      // Display creation form.
      $this->drupalGet('entity_test/add');

      // Submit a partial date and ensure and error message is provided.
      $edit = array();
      foreach ($date_value as $part => $value) {
        $edit["{$field_name}[0][value][$part]"] = $value;
      }

      $this->drupalPostForm(NULL, $edit, t('Save'));
      $this->assertResponse(200);
      foreach ($expected as $expected_text) {
        $this->assertText(t($expected_text));
      }
    }

    // Test the widget for complete input with zeros as part of selections.
    $this->drupalGet('entity_test/add');

    $date_value = array('year' => 2012, 'month' => '12', 'day' => '31', 'hour' => '0', 'minute' => '0');
    $edit = array();
    foreach ($date_value as $part => $value) {
      $edit["{$field_name}[0][value][$part]"] = $value;
    }

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));

    // Test the widget to ensure zeros are not deselected on validation.
    $this->drupalGet('entity_test/add');

    $date_value = array('year' => 2012, 'month' => '12', 'day' => '31', 'hour' => '', 'minute' => '0');
    $edit = array();
    foreach ($date_value as $part => $value) {
      $edit["{$field_name}[0][value][$part]"] = $value;
    }

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertOptionSelected("edit-$field_name-0-value-minute", '0', 'Correct minute selected.');
 }

  /**
   * The data provider for testing the validation of the datelist widget.
   *
   * @return array
   *   An array of datelist input permutations to test.
   */
  protected function datelistDataProvider() {
    return [
      // Year only selected, validation error on Month, Day, Hour, Minute.
      [['year' => 2012, 'month' => '', 'day' => '', 'hour' => '', 'minute' => ''], [
        'A value must be selected for month.',
        'A value must be selected for day.',
        'A value must be selected for hour.',
        'A value must be selected for minute.',
      ]],
      // Year and Month selected, validation error on Day, Hour, Minute.
      [['year' => 2012, 'month' => '12', 'day' => '', 'hour' => '', 'minute' => ''], [
        'A value must be selected for day.',
        'A value must be selected for hour.',
        'A value must be selected for minute.',
      ]],
      // Year, Month and Day selected, validation error on Hour, Minute.
      [['year' => 2012, 'month' => '12', 'day' => '31', 'hour' => '', 'minute' => ''], [
        'A value must be selected for hour.',
        'A value must be selected for minute.',
      ]],
      // Year, Month, Day and Hour selected, validation error on Minute only.
      [['year' => 2012, 'month' => '12', 'day' => '31', 'hour' => '0', 'minute' => ''], [
        'A value must be selected for minute.',
      ]],
    ];
  }

  /**
   * Test default value functionality.
   */
  function testDefaultValue() {
    // Create a test content type.
    $this->drupalCreateContentType(array('type' => 'date_content'));

    // Create a field storage with settings to validate.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => array('datetime_type' => 'date'),
    ));
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'date_content',
    ]);
    $field->save();

    // Set now as default_value.
    $field_edit = array(
      'default_value_input[default_date_type]' => 'now',
    );
    $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name, $field_edit, t('Save settings'));

    // Check that default value is selected in default value form.
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->assertOptionSelected('edit-default-value-input-default-date-type', 'now', 'The default value is selected in instance settings page');
    $this->assertFieldByName('default_value_input[default_date]', '', 'The relative default value is empty in instance settings page');

    // Check if default_date has been stored successfully.
    $config_entity = $this->config('field.field.node.date_content.' . $field_name)->get();
    $this->assertEqual($config_entity['default_value'][0], array('default_date_type' => 'now', 'default_date' => 'now'), 'Default value has been stored successfully');

    // Clear field cache in order to avoid stale cache values.
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Create a new node to check that datetime field default value is today.
    $new_node = Node::create(['type' => 'date_content']);
    $expected_date = new DrupalDateTime('now', DATETIME_STORAGE_TIMEZONE);
    $this->assertEqual($new_node->get($field_name)->offsetGet(0)->value, $expected_date->format(DATETIME_DATE_STORAGE_FORMAT));

    // Set an invalid relative default_value to test validation.
    $field_edit = array(
      'default_value_input[default_date_type]' => 'relative',
      'default_value_input[default_date]' => 'invalid date',
    );
    $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name, $field_edit, t('Save settings'));

    $this->assertText('The relative date value entered is invalid.');

    // Set a relative default_value.
    $field_edit = array(
      'default_value_input[default_date_type]' => 'relative',
      'default_value_input[default_date]' => '+90 days',
    );
    $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name, $field_edit, t('Save settings'));

    // Check that default value is selected in default value form.
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->assertOptionSelected('edit-default-value-input-default-date-type', 'relative', 'The default value is selected in instance settings page');
    $this->assertFieldByName('default_value_input[default_date]', '+90 days', 'The relative default value is displayed in instance settings page');

    // Check if default_date has been stored successfully.
    $config_entity = $this->config('field.field.node.date_content.' . $field_name)->get();
    $this->assertEqual($config_entity['default_value'][0], array('default_date_type' => 'relative', 'default_date' => '+90 days'), 'Default value has been stored successfully');

    // Clear field cache in order to avoid stale cache values.
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Create a new node to check that datetime field default value is +90 days.
    $new_node = Node::create(['type' => 'date_content']);
    $expected_date = new DrupalDateTime('+90 days', DATETIME_STORAGE_TIMEZONE);
    $this->assertEqual($new_node->get($field_name)->offsetGet(0)->value, $expected_date->format(DATETIME_DATE_STORAGE_FORMAT));

    // Remove default value.
    $field_edit = array(
      'default_value_input[default_date_type]' => '',
    );
    $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name, $field_edit, t('Save settings'));

    // Check that default value is selected in default value form.
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->assertOptionSelected('edit-default-value-input-default-date-type', '', 'The default value is selected in instance settings page');
    $this->assertFieldByName('default_value_input[default_date]', '', 'The relative default value is empty in instance settings page');

    // Check if default_date has been stored successfully.
    $config_entity = $this->config('field.field.node.date_content.' . $field_name)->get();
    $this->assertTrue(empty($config_entity['default_value']), 'Empty default value has been stored successfully');

    // Clear field cache in order to avoid stale cache values.
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Create a new node to check that datetime field default value is not set.
    $new_node = Node::create(['type' => 'date_content']);
    $this->assertNull($new_node->get($field_name)->value, 'Default value is not set');
  }

  /**
   * Test that invalid values are caught and marked as invalid.
   */
  function testInvalidField() {
    // Change the field to a datetime field.
    $this->fieldStorage->setSetting('datetime_type', 'datetime');
    $this->fieldStorage->save();
    $field_name = $this->fieldStorage->getName();

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
   * Tests that 'Date' field storage setting form is disabled if field has data.
   */
  public function testDateStorageSettings() {
    // Create a test content type.
    $this->drupalCreateContentType(['type' => 'date_content']);

    // Create a field storage with settings to validate.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => [
        'datetime_type' => 'date',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'field_name' => $field_name,
      'bundle' => 'date_content',
    ]);
    $field->save();

    entity_get_form_display('node', 'date_content', 'default')
      ->setComponent($field_name, [
        'type' => 'datetime_default',
      ])
      ->save();
    $edit = [
      'title[0][value]' => $this->randomString(),
      'body[0][value]' => $this->randomString(),
      $field_name . '[0][value][date]' => '2016-04-01',
    ];
    $this->drupalPostForm('node/add/date_content', $edit, t('Save'));
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name . '/storage');
    $result = $this->xpath("//*[@id='edit-settings-datetime-type' and contains(@disabled, 'disabled')]");
    $this->assertEqual(count($result), 1, "Changing datetime setting is disabled.");
    $this->assertText('There is data for this field in the database. The field settings can no longer be changed.');
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
      \Drupal::entityManager()->getStorage('entity_test')->resetCache(array($id));
    }
    $entity = EntityTest::load($id);
    $display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
    $build = $display->build($entity);
    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->setRawContent($output);
    $this->verbose($output);
  }

}
