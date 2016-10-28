<?php

namespace Drupal\datetime_range\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\datetime\Tests\DateTestBase;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;

/**
 * Tests Daterange field functionality.
 *
 * @group datetime
 */
class DateRangeFieldTest extends DateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['datetime_range'];

  /**
   * The default display settings to use for the formatters.
   *
   * @var array
   */
  protected $defaultSettings = ['timezone_override' => '', 'separator' => '-'];

  /**
   * {@inheritdoc}
   */
  protected function getTestFieldType() {
    return 'daterange';
  }

  /**
   * Tests date field functionality.
   */
  public function testDateRangeField() {
    $field_name = $this->fieldStorage->getName();

    // Loop through defined timezones to test that date-only fields work at the
    // extremes.
    foreach (static::$timezones as $timezone) {

      $this->setSiteTimezone($timezone);

      // Ensure field is set to a date-only field.
      $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_DATE);
      $this->fieldStorage->save();

      // Display creation form.
      $this->drupalGet('entity_test/add');
      $this->assertFieldByName("{$field_name}[0][value][date]", '', 'Start date element found.');
      $this->assertFieldByName("{$field_name}[0][end_value][date]", '', 'End date element found.');
      $this->assertFieldByXPath('//*[@id="edit-' . $field_name . '-wrapper"]/h4[contains(@class, "js-form-required")]', TRUE, 'Required markup found');
      $this->assertNoFieldByName("{$field_name}[0][value][time]", '', 'Start time element not found.');
      $this->assertNoFieldByName("{$field_name}[0][end_value][time]", '', 'End time element not found.');

      // Build up dates in the UTC timezone.
      $value = '2012-12-31 00:00:00';
      $start_date = new DrupalDateTime($value, 'UTC');
      $end_value = '2013-06-06 00:00:00';
      $end_date = new DrupalDateTime($end_value, 'UTC');

      // Submit a valid date and ensure it is accepted.
      $date_format = DateFormat::load('html_date')->getPattern();
      $time_format = DateFormat::load('html_time')->getPattern();

      $edit = array(
        "{$field_name}[0][value][date]" => $start_date->format($date_format),
        "{$field_name}[0][end_value][date]" => $end_date->format($date_format),
      );
      $this->drupalPostForm(NULL, $edit, t('Save'));
      preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
      $id = $match[1];
      $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));
      $this->assertRaw($start_date->format($date_format));
      $this->assertNoRaw($start_date->format($time_format));
      $this->assertRaw($end_date->format($date_format));
      $this->assertNoRaw($end_date->format($time_format));

      // Verify the date doesn't change when entity is edited through the form.
      $entity = EntityTest::load($id);
      $this->assertEqual('2012-12-31', $entity->{$field_name}->value);
      $this->assertEqual('2013-06-06', $entity->{$field_name}->end_value);
      $this->drupalGet('entity_test/manage/' . $id . '/edit');
      $this->drupalPostForm(NULL, [], t('Save'));
      $this->drupalGet('entity_test/manage/' . $id . '/edit');
      $this->drupalPostForm(NULL, [], t('Save'));
      $this->drupalGet('entity_test/manage/' . $id . '/edit');
      $this->drupalPostForm(NULL, [], t('Save'));
      $entity = EntityTest::load($id);
      $this->assertEqual('2012-12-31', $entity->{$field_name}->value);
      $this->assertEqual('2013-06-06', $entity->{$field_name}->end_value);

      // Formats that display a time component for date-only fields will display
      // the default time, so that is applied before calculating the expected
      // value.
      datetime_date_default_time($start_date);
      datetime_date_default_time($end_date);

      // Reset display options since these get changed below.
      $this->displayOptions = [
        'type' => 'daterange_default',
        'label' => 'hidden',
        'settings' => [
          'format_type' => 'long',
          'separator' => 'THESEPARATOR',
        ] + $this->defaultSettings,
      ];

      // Verify that the default formatter works.
      entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
        ->setComponent($field_name, $this->displayOptions)
        ->save();

      $start_expected = $this->dateFormatter->format($start_date->getTimestamp(), 'long', '', DATETIME_STORAGE_TIMEZONE);
      $start_expected_iso = $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', DATETIME_STORAGE_TIMEZONE);
      $end_expected = $this->dateFormatter->format($end_date->getTimestamp(), 'long', '', DATETIME_STORAGE_TIMEZONE);
      $end_expected_iso = $this->dateFormatter->format($end_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', DATETIME_STORAGE_TIMEZONE);
      $this->renderTestEntity($id);
      $this->assertFieldByXPath('//time[@datetime="' . $start_expected_iso . '"]', $start_expected, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', [
        '%value' => 'long',
        '%expected' => $start_expected,
        '%expected_iso' => $start_expected_iso,
      ]));
      $this->assertFieldByXPath('//time[@datetime="' . $end_expected_iso . '"]', $end_expected, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', [
        '%value' => 'long',
        '%expected' => $end_expected,
        '%expected_iso' => $end_expected_iso,
      ]));
      $this->assertText(' THESEPARATOR ', 'Found proper separator');

      // Verify that hook_entity_prepare_view can add attributes.
      // @see entity_test_entity_prepare_view()
      $this->drupalGet('entity_test/' . $id);
      $this->assertFieldByXPath('//div[@data-field-item-attr="foobar"]');

      // Verify that the plain formatter works.
      $this->displayOptions['type'] = 'daterange_plain';
      $this->displayOptions['settings'] = $this->defaultSettings;
      entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
        ->setComponent($field_name, $this->displayOptions)
        ->save();
      $expected = $start_date->format(DATETIME_DATE_STORAGE_FORMAT) . ' - ' . $end_date->format(DATETIME_DATE_STORAGE_FORMAT);
      $this->renderTestEntity($id);
      $this->assertText($expected, new FormattableMarkup('Formatted date field using plain format displayed as %expected.', array('%expected' => $expected)));

      // Verify that the custom formatter works.
      $this->displayOptions['type'] = 'daterange_custom';
      $this->displayOptions['settings'] = array('date_format' => 'm/d/Y') + $this->defaultSettings;
      entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
        ->setComponent($field_name, $this->displayOptions)
        ->save();
      $expected = $start_date->format($this->displayOptions['settings']['date_format']) . ' - ' . $end_date->format($this->displayOptions['settings']['date_format']);
      $this->renderTestEntity($id);
      $this->assertText($expected, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected.', array('%expected' => $expected)));

      // Test formatters when start date and end date are the same
      $this->drupalGet('entity_test/add');
      $value = '2012-12-31 00:00:00';
      $start_date = new DrupalDateTime($value, 'UTC');

      $date_format = DateFormat::load('html_date')->getPattern();
      $time_format = DateFormat::load('html_time')->getPattern();

      $edit = array(
        "{$field_name}[0][value][date]" => $start_date->format($date_format),
        "{$field_name}[0][end_value][date]" => $start_date->format($date_format),
      );

      $this->drupalPostForm(NULL, $edit, t('Save'));
      preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
      $id = $match[1];
      $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));

      datetime_date_default_time($start_date);

      $this->displayOptions = [
        'type' => 'daterange_default',
        'label' => 'hidden',
        'settings' => [
            'format_type' => 'long',
            'separator' => 'THESEPARATOR',
          ] + $this->defaultSettings,
      ];

      entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
        ->setComponent($field_name, $this->displayOptions)
        ->save();

      $start_expected = $this->dateFormatter->format($start_date->getTimestamp(), 'long', '', DATETIME_STORAGE_TIMEZONE);
      $start_expected_iso = $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', DATETIME_STORAGE_TIMEZONE);
      $this->renderTestEntity($id);
      $this->assertFieldByXPath('//time[@datetime="' . $start_expected_iso . '"]', $start_expected, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', [
        '%value' => 'long',
        '%expected' => $start_expected,
        '%expected_iso' => $start_expected_iso,
      ]));
      $this->assertNoText(' THESEPARATOR ', 'Separator not found on page');

      // Verify that hook_entity_prepare_view can add attributes.
      // @see entity_test_entity_prepare_view()
      $this->drupalGet('entity_test/' . $id);
      $this->assertFieldByXPath('//time[@data-field-item-attr="foobar"]');

      $this->displayOptions['type'] = 'daterange_plain';
      $this->displayOptions['settings'] = $this->defaultSettings;
      entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
        ->setComponent($field_name, $this->displayOptions)
        ->save();
      $expected = $start_date->format(DATETIME_DATE_STORAGE_FORMAT);
      $this->renderTestEntity($id);
      $this->assertText($expected, new FormattableMarkup('Formatted date field using plain format displayed as %expected.', array('%expected' => $expected)));
      $this->assertNoText(' THESEPARATOR ', 'Separator not found on page');

      $this->displayOptions['type'] = 'daterange_custom';
      $this->displayOptions['settings'] = array('date_format' => 'm/d/Y') + $this->defaultSettings;
      entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
        ->setComponent($field_name, $this->displayOptions)
        ->save();
      $expected = $start_date->format($this->displayOptions['settings']['date_format']);
      $this->renderTestEntity($id);
      $this->assertText($expected, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected.', array('%expected' => $expected)));
      $this->assertNoText(' THESEPARATOR ', 'Separator not found on page');
    }
  }

  /**
   * Tests date and time field.
   */
  public function testDatetimeRangeField() {
    $field_name = $this->fieldStorage->getName();

    // Ensure the field to a datetime field.
    $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_DATETIME);
    $this->fieldStorage->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value][date]", '', 'Start date element found.');
    $this->assertFieldByName("{$field_name}[0][value][time]", '', 'Start time element found.');
    $this->assertFieldByName("{$field_name}[0][end_value][date]", '', 'End date element found.');
    $this->assertFieldByName("{$field_name}[0][end_value][time]", '', 'End time element found.');

    // Build up dates in the UTC timezone.
    $value = '2012-12-31 00:00:00';
    $start_date = new DrupalDateTime($value, 'UTC');
    $end_value = '2013-06-06 00:00:00';
    $end_date = new DrupalDateTime($end_value, 'UTC');

    // Update the timezone to the system default.
    $start_date->setTimezone(timezone_open(drupal_get_user_timezone()));
    $end_date->setTimezone(timezone_open(drupal_get_user_timezone()));

    // Submit a valid date and ensure it is accepted.
    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();

    $edit = array(
      "{$field_name}[0][value][date]" => $start_date->format($date_format),
      "{$field_name}[0][value][time]" => $start_date->format($time_format),
      "{$field_name}[0][end_value][date]" => $end_date->format($date_format),
      "{$field_name}[0][end_value][time]" => $end_date->format($time_format),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));
    $this->assertRaw($start_date->format($date_format));
    $this->assertRaw($start_date->format($time_format));
    $this->assertRaw($end_date->format($date_format));
    $this->assertRaw($end_date->format($time_format));

    // Verify that the default formatter works.
    $this->displayOptions['settings'] = [
      'format_type' => 'long',
      'separator' => 'THESEPARATOR',
    ] + $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();

    $start_expected = $this->dateFormatter->format($start_date->getTimestamp(), 'long');
    $start_expected_iso = $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
    $end_expected = $this->dateFormatter->format($end_date->getTimestamp(), 'long');
    $end_expected_iso = $this->dateFormatter->format($end_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
    $this->renderTestEntity($id);
    $this->assertFieldByXPath('//time[@datetime="' . $start_expected_iso . '"]', $start_expected, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', ['%value' => 'long', '%expected' => $start_expected, '%expected_iso' => $start_expected_iso]));
    $this->assertFieldByXPath('//time[@datetime="' . $end_expected_iso . '"]', $end_expected, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', ['%value' => 'long', '%expected' => $end_expected, '%expected_iso' => $end_expected_iso]));
    $this->assertText(' THESEPARATOR ', 'Found proper separator');

    // Verify that hook_entity_prepare_view can add attributes.
    // @see entity_test_entity_prepare_view()
    $this->drupalGet('entity_test/' . $id);
    $this->assertFieldByXPath('//div[@data-field-item-attr="foobar"]');

    // Verify that the plain formatter works.
    $this->displayOptions['type'] = 'daterange_plain';
    $this->displayOptions['settings'] = $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format(DATETIME_DATETIME_STORAGE_FORMAT) . ' - ' . $end_date->format(DATETIME_DATETIME_STORAGE_FORMAT);
    $this->renderTestEntity($id);
    $this->assertText($expected, new FormattableMarkup('Formatted date field using plain format displayed as %expected.', array('%expected' => $expected)));

    // Verify that the 'datetime_custom' formatter works.
    $this->displayOptions['type'] = 'daterange_custom';
    $this->displayOptions['settings'] = ['date_format' => 'm/d/Y g:i:s A'] + $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format($this->displayOptions['settings']['date_format']) . ' - ' . $end_date->format($this->displayOptions['settings']['date_format']);
    $this->renderTestEntity($id);
    $this->assertText($expected, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected.', array('%expected' => $expected)));

    // Verify that the 'timezone_override' setting works.
    $this->displayOptions['type'] = 'daterange_custom';
    $this->displayOptions['settings'] = ['date_format' => 'm/d/Y g:i:s A', 'timezone_override' => 'America/New_York'] + $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format($this->displayOptions['settings']['date_format'], ['timezone' => 'America/New_York']);
    $expected .= ' - ' . $end_date->format($this->displayOptions['settings']['date_format'], ['timezone' => 'America/New_York']);
    $this->renderTestEntity($id);
    $this->assertText($expected, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected.', array('%expected' => $expected)));

    // Test formatters when start date and end date are the same
    $this->drupalGet('entity_test/add');
    $value = '2012-12-31 00:00:00';
    $start_date = new DrupalDateTime($value, 'UTC');
    $start_date->setTimezone(timezone_open(drupal_get_user_timezone()));

    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();

    $edit = array(
      "{$field_name}[0][value][date]" => $start_date->format($date_format),
      "{$field_name}[0][value][time]" => $start_date->format($time_format),
      "{$field_name}[0][end_value][date]" => $start_date->format($date_format),
      "{$field_name}[0][end_value][time]" => $start_date->format($time_format),
    );

    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));

    $this->displayOptions = [
      'type' => 'daterange_default',
      'label' => 'hidden',
      'settings' => [
        'format_type' => 'long',
        'separator' => 'THESEPARATOR',
      ] + $this->defaultSettings,
    ];

    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();

    $start_expected = $this->dateFormatter->format($start_date->getTimestamp(), 'long');
    $start_expected_iso = $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
    $this->renderTestEntity($id);
    $this->assertFieldByXPath('//time[@datetime="' . $start_expected_iso . '"]', $start_expected, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', ['%value' => 'long', '%expected' => $start_expected, '%expected_iso' => $start_expected_iso]));
    $this->assertNoText(' THESEPARATOR ', 'Separator not found on page');

    // Verify that hook_entity_prepare_view can add attributes.
    // @see entity_test_entity_prepare_view()
    $this->drupalGet('entity_test/' . $id);
    $this->assertFieldByXPath('//time[@data-field-item-attr="foobar"]');

    $this->displayOptions['type'] = 'daterange_plain';
    $this->displayOptions['settings'] = $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format(DATETIME_DATETIME_STORAGE_FORMAT);
    $this->renderTestEntity($id);
    $this->assertText($expected, new FormattableMarkup('Formatted date field using plain format displayed as %expected.', array('%expected' => $expected)));
    $this->assertNoText(' THESEPARATOR ', 'Separator not found on page');

    $this->displayOptions['type'] = 'daterange_custom';
    $this->displayOptions['settings'] = ['date_format' => 'm/d/Y g:i:s A'] + $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format($this->displayOptions['settings']['date_format']);
    $this->renderTestEntity($id);
    $this->assertText($expected, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected.', array('%expected' => $expected)));
    $this->assertNoText(' THESEPARATOR ', 'Separator not found on page');
  }

  /**
   * Tests all-day field.
   */
  public function testAlldayRangeField() {
    $field_name = $this->fieldStorage->getName();

    // Ensure field is set to a all-day field.
    $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_ALLDAY);
    $this->fieldStorage->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value][date]", '', 'Start date element found.');
    $this->assertFieldByName("{$field_name}[0][end_value][date]", '', 'End date element found.');
    $this->assertFieldByXPath('//*[@id="edit-' . $field_name . '-wrapper"]/h4[contains(@class, "js-form-required")]', TRUE, 'Required markup found');
    $this->assertNoFieldByName("{$field_name}[0][value][time]", '', 'Start time element not found.');
    $this->assertNoFieldByName("{$field_name}[0][end_value][time]", '', 'End time element not found.');

    // Build up dates in the proper timezone.
    $value = '2012-12-31 00:00:00';
    $start_date = new DrupalDateTime($value, timezone_open(drupal_get_user_timezone()));
    $end_value = '2013-06-06 23:59:59';
    $end_date = new DrupalDateTime($end_value, timezone_open(drupal_get_user_timezone()));

    // Submit a valid date and ensure it is accepted.
    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();

    $edit = array(
      "{$field_name}[0][value][date]" => $start_date->format($date_format),
      "{$field_name}[0][end_value][date]" => $end_date->format($date_format),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));
    $this->assertRaw($start_date->format($date_format));
    $this->assertNoRaw($start_date->format($time_format));
    $this->assertRaw($end_date->format($date_format));
    $this->assertNoRaw($end_date->format($time_format));

    // Verify that the default formatter works.
    $this->displayOptions['settings'] = [
      'format_type' => 'long',
      'separator' => 'THESEPARATOR',
    ] + $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();

    $start_expected = $this->dateFormatter->format($start_date->getTimestamp(), 'long');
    $start_expected_iso = $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
    $end_expected = $this->dateFormatter->format($end_date->getTimestamp(), 'long');
    $end_expected_iso = $this->dateFormatter->format($end_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
    $this->renderTestEntity($id);
    $this->assertFieldByXPath('//time[@datetime="' . $start_expected_iso . '"]', $start_expected, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', ['%value' => 'long', '%expected' => $start_expected, '%expected_iso' => $start_expected_iso]));
    $this->assertFieldByXPath('//time[@datetime="' . $end_expected_iso . '"]', $end_expected, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', ['%value' => 'long', '%expected' => $end_expected, '%expected_iso' => $end_expected_iso]));
    $this->assertText(' THESEPARATOR ', 'Found proper separator');

    // Verify that hook_entity_prepare_view can add attributes.
    // @see entity_test_entity_prepare_view()
    $this->drupalGet('entity_test/' . $id);
    $this->assertFieldByXPath('//div[@data-field-item-attr="foobar"]');

    // Verify that the plain formatter works.
    $this->displayOptions['type'] = 'daterange_plain';
    $this->displayOptions['settings'] = $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format(DATETIME_DATETIME_STORAGE_FORMAT) . ' - ' . $end_date->format(DATETIME_DATETIME_STORAGE_FORMAT);
    $this->renderTestEntity($id);
    $this->assertText($expected, new FormattableMarkup('Formatted date field using plain format displayed as %expected.', array('%expected' => $expected)));

    // Verify that the custom formatter works.
    $this->displayOptions['type'] = 'daterange_custom';
    $this->displayOptions['settings'] = array('date_format' => 'm/d/Y') + $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format($this->displayOptions['settings']['date_format']) . ' - ' . $end_date->format($this->displayOptions['settings']['date_format']);
    $this->renderTestEntity($id);
    $this->assertText($expected, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected.', array('%expected' => $expected)));

    // Verify that the 'timezone_override' setting works.
    $this->displayOptions['type'] = 'daterange_custom';
    $this->displayOptions['settings'] = ['date_format' => 'm/d/Y g:i:s A', 'timezone_override' => 'America/New_York'] + $this->defaultSettings;
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format($this->displayOptions['settings']['date_format'], ['timezone' => 'America/New_York']);
    $expected .= ' - ' . $end_date->format($this->displayOptions['settings']['date_format'], ['timezone' => 'America/New_York']);
    $this->renderTestEntity($id);
    $this->assertText($expected, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected.', array('%expected' => $expected)));

    // Test formatters when start date and end date are the same
    $this->drupalGet('entity_test/add');

    $value = '2012-12-31 00:00:00';
    $start_date = new DrupalDateTime($value, timezone_open(drupal_get_user_timezone()));
    $end_value = '2012-12-31 23:59:59';
    $end_date = new DrupalDateTime($end_value, timezone_open(drupal_get_user_timezone()));

    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();

    $edit = array(
      "{$field_name}[0][value][date]" => $start_date->format($date_format),
      "{$field_name}[0][end_value][date]" => $start_date->format($date_format),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));

    $this->displayOptions = [
      'type' => 'daterange_default',
      'label' => 'hidden',
      'settings' => [
        'format_type' => 'long',
        'separator' => 'THESEPARATOR',
      ] + $this->defaultSettings,
    ];

    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();

    $start_expected = $this->dateFormatter->format($start_date->getTimestamp(), 'long');
    $start_expected_iso = $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
    $end_expected = $this->dateFormatter->format($end_date->getTimestamp(), 'long');
    $end_expected_iso = $this->dateFormatter->format($end_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
    $this->renderTestEntity($id);
    $this->assertFieldByXPath('//time[@datetime="' . $start_expected_iso . '"]', $start_expected, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', ['%value' => 'long', '%expected' => $start_expected, '%expected_iso' => $start_expected_iso]));
    $this->assertFieldByXPath('//time[@datetime="' . $end_expected_iso . '"]', $end_expected, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', ['%value' => 'long', '%expected' => $end_expected, '%expected_iso' => $end_expected_iso]));
    $this->assertText(' THESEPARATOR ', 'Found proper separator');

    // Verify that hook_entity_prepare_view can add attributes.
    // @see entity_test_entity_prepare_view()
    $this->drupalGet('entity_test/' . $id);
    $this->assertFieldByXPath('//div[@data-field-item-attr="foobar"]');

    $this->displayOptions['type'] = 'daterange_plain';
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format(DATETIME_DATETIME_STORAGE_FORMAT) . ' THESEPARATOR ' . $end_date->format(DATETIME_DATETIME_STORAGE_FORMAT);
    $this->renderTestEntity($id);
    $this->assertText($expected, new FormattableMarkup('Formatted date field using plain format displayed as %expected.', array('%expected' => $expected)));
    $this->assertText(' THESEPARATOR ', 'Found proper separator');

    $this->displayOptions['type'] = 'daterange_custom';
    $this->displayOptions['settings']['date_format'] = 'm/d/Y';
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format($this->displayOptions['settings']['date_format']) . ' THESEPARATOR ' . $end_date->format($this->displayOptions['settings']['date_format']);
    $this->renderTestEntity($id);
    $this->assertText($expected, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected.', array('%expected' => $expected)));
    $this->assertText(' THESEPARATOR ', 'Found proper separator');

  }

  /**
   * Tests Date Range List Widget functionality.
   */
  public function testDatelistWidget() {
    $field_name = $this->fieldStorage->getName();

    // Ensure field is set to a date only field.
    $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_DATE);
    $this->fieldStorage->save();

    // Change the widget to a datelist widget.
    entity_get_form_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'default')
      ->setComponent($field_name, [
        'type' => 'daterange_datelist',
        'settings' => [
          'date_order' => 'YMD',
        ],
      ])
      ->save();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Display creation form.
    $this->drupalGet('entity_test/add');

    // Assert that Hour and Minute Elements do not appear on Date Only.
    $this->assertNoFieldByXPath("//*[@id=\"edit-$field_name-0-value-hour\"]", NULL, 'Hour element not found on Date Only.');
    $this->assertNoFieldByXPath("//*[@id=\"edit-$field_name-0-value-minute\"]", NULL, 'Minute element not found on Date Only.');
    $this->assertNoFieldByXPath("//*[@id=\"edit-$field_name-0-end-value-hour\"]", NULL, 'Hour element not found on Date Only.');
    $this->assertNoFieldByXPath("//*[@id=\"edit-$field_name-0-end-value-minute\"]", NULL, 'Minute element not found on Date Only.');

    // Go to the form display page to assert that increment option does not
    // appear on Date Only.
    $fieldEditUrl = 'entity_test/structure/entity_test/form-display';
    $this->drupalGet($fieldEditUrl);

    // Click on the widget settings button to open the widget settings form.
    $this->drupalPostAjaxForm(NULL, [], $field_name . "_settings_edit");
    $xpathIncr = "//select[starts-with(@id, \"edit-fields-$field_name-settings-edit-form-settings-increment\")]";
    $this->assertNoFieldByXPath($xpathIncr, NULL, 'Increment element not found for Date Only.');

    // Change the field is set to an all day field.
    $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_ALLDAY);
    $this->fieldStorage->save();

    // Change the widget to a datelist widget.
    entity_get_form_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'default')
      ->setComponent($field_name, [
        'type' => 'daterange_datelist',
        'settings' => [
          'date_order' => 'YMD',
        ],
      ])
      ->save();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Display creation form.
    $this->drupalGet('entity_test/add');

    // Assert that Hour and Minute Elements do not appear on Date Only.
    $this->assertNoFieldByXPath("//*[@id=\"edit-$field_name-0-value-hour\"]", NULL, 'Hour element not found on Date Only.');
    $this->assertNoFieldByXPath("//*[@id=\"edit-$field_name-0-value-minute\"]", NULL, 'Minute element not found on Date Only.');
    $this->assertNoFieldByXPath("//*[@id=\"edit-$field_name-0-end-value-hour\"]", NULL, 'Hour element not found on Date Only.');
    $this->assertNoFieldByXPath("//*[@id=\"edit-$field_name-0-end-value-minute\"]", NULL, 'Minute element not found on Date Only.');

    // Go to the form display page to assert that increment option does not
    // appear on Date Only.
    $fieldEditUrl = 'entity_test/structure/entity_test/form-display';
    $this->drupalGet($fieldEditUrl);

    // Click on the widget settings button to open the widget settings form.
    $this->drupalPostAjaxForm(NULL, [], $field_name . "_settings_edit");
    $xpathIncr = "//select[starts-with(@id, \"edit-fields-$field_name-settings-edit-form-settings-increment\")]";
    $this->assertNoFieldByXPath($xpathIncr, NULL, 'Increment element not found for Date Only.');

    // Change the field to a datetime field.
    $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_DATETIME);
    $this->fieldStorage->save();

    // Change the widget to a datelist widget.
    entity_get_form_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'default')
      ->setComponent($field_name, [
        'type' => 'daterange_datelist',
        'settings' => [
          'increment' => 1,
          'date_order' => 'YMD',
          'time_type' => '12',
        ],
      ])
      ->save();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Go to the form display page to assert that increment option does appear
    // on Date Time.
    $fieldEditUrl = 'entity_test/structure/entity_test/form-display';
    $this->drupalGet($fieldEditUrl);

    // Click on the widget settings button to open the widget settings form.
    $this->drupalPostAjaxForm(NULL, [], $field_name . "_settings_edit");
    $this->assertFieldByXPath($xpathIncr, NULL, 'Increment element found for Date and time.');

    // Display creation form.
    $this->drupalGet('entity_test/add');

    foreach (['value', 'end-value'] as $column) {
      foreach (['year', 'month', 'day', 'hour', 'minute', 'ampm'] as $element) {
        $this->assertFieldByXPath("//*[@id=\"edit-$field_name-0-$column-$element\"]", NULL, $element . ' element found.');
        $this->assertOptionSelected("edit-$field_name-0-$column-$element", '', 'No ' . $element . ' selected.');
      }
    }

    // Submit a valid date and ensure it is accepted.
    $start_date_value = ['year' => 2012, 'month' => 12, 'day' => 31, 'hour' => 5, 'minute' => 15];
    $end_date_value = ['year' => 2013, 'month' => 1, 'day' => 15, 'hour' => 3, 'minute' => 30];

    $edit = [];
    // Add the ampm indicator since we are testing 12 hour time.
    $start_date_value['ampm'] = 'am';
    $end_date_value['ampm'] = 'pm';
    foreach ($start_date_value as $part => $value) {
      $edit["{$field_name}[0][value][$part]"] = $value;
    }
    foreach ($end_date_value as $part => $value) {
      $edit["{$field_name}[0][end_value][$part]"] = $value;
    }

    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]));

    $this->assertOptionSelected("edit-$field_name-0-value-year", '2012', 'Correct year selected.');
    $this->assertOptionSelected("edit-$field_name-0-value-month", '12', 'Correct month selected.');
    $this->assertOptionSelected("edit-$field_name-0-value-day", '31', 'Correct day selected.');
    $this->assertOptionSelected("edit-$field_name-0-value-hour", '5', 'Correct hour selected.');
    $this->assertOptionSelected("edit-$field_name-0-value-minute", '15', 'Correct minute selected.');
    $this->assertOptionSelected("edit-$field_name-0-value-ampm", 'am', 'Correct ampm selected.');

    $this->assertOptionSelected("edit-$field_name-0-end-value-year", '2013', 'Correct year selected.');
    $this->assertOptionSelected("edit-$field_name-0-end-value-month", '1', 'Correct month selected.');
    $this->assertOptionSelected("edit-$field_name-0-end-value-day", '15', 'Correct day selected.');
    $this->assertOptionSelected("edit-$field_name-0-end-value-hour", '3', 'Correct hour selected.');
    $this->assertOptionSelected("edit-$field_name-0-end-value-minute", '30', 'Correct minute selected.');
    $this->assertOptionSelected("edit-$field_name-0-end-value-ampm", 'pm', 'Correct ampm selected.');

    // Test the widget using increment other than 1 and 24 hour mode.
    entity_get_form_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'default')
      ->setComponent($field_name, [
        'type' => 'daterange_datelist',
        'settings' => [
          'increment' => 15,
          'date_order' => 'YMD',
          'time_type' => '24',
        ],
      ])
      ->save();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Display creation form.
    $this->drupalGet('entity_test/add');

    // Other elements are unaffected by the changed settings.
    $this->assertFieldByXPath("//*[@id=\"edit-$field_name-0-value-hour\"]", NULL, 'Hour element found.');
    $this->assertOptionSelected("edit-$field_name-0-value-hour", '', 'No hour selected.');
    $this->assertNoFieldByXPath("//*[@id=\"edit-$field_name-0-value-ampm\"]", NULL, 'AMPM element not found.');
    $this->assertFieldByXPath("//*[@id=\"edit-$field_name-0-end-value-hour\"]", NULL, 'Hour element found.');
    $this->assertOptionSelected("edit-$field_name-0-end-value-hour", '', 'No hour selected.');
    $this->assertNoFieldByXPath("//*[@id=\"edit-$field_name-0-end-value-ampm\"]", NULL, 'AMPM element not found.');

    // Submit a valid date and ensure it is accepted.
    $start_date_value = ['year' => 2012, 'month' => 12, 'day' => 31, 'hour' => 17, 'minute' => 15];
    $end_date_value = ['year' => 2013, 'month' => 1, 'day' => 15, 'hour' => 3, 'minute' => 30];

    $edit = [];
    foreach ($start_date_value as $part => $value) {
      $edit["{$field_name}[0][value][$part]"] = $value;
    }
    foreach ($end_date_value as $part => $value) {
      $edit["{$field_name}[0][end_value][$part]"] = $value;
    }

    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]));

    $this->assertOptionSelected("edit-$field_name-0-value-year", '2012', 'Correct year selected.');
    $this->assertOptionSelected("edit-$field_name-0-value-month", '12', 'Correct month selected.');
    $this->assertOptionSelected("edit-$field_name-0-value-day", '31', 'Correct day selected.');
    $this->assertOptionSelected("edit-$field_name-0-value-hour", '17', 'Correct hour selected.');
    $this->assertOptionSelected("edit-$field_name-0-value-minute", '15', 'Correct minute selected.');

    $this->assertOptionSelected("edit-$field_name-0-end-value-year", '2013', 'Correct year selected.');
    $this->assertOptionSelected("edit-$field_name-0-end-value-month", '1', 'Correct month selected.');
    $this->assertOptionSelected("edit-$field_name-0-end-value-day", '15', 'Correct day selected.');
    $this->assertOptionSelected("edit-$field_name-0-end-value-hour", '3', 'Correct hour selected.');
    $this->assertOptionSelected("edit-$field_name-0-end-value-minute", '30', 'Correct minute selected.');

    // Test the widget for partial completion of fields.
    entity_get_form_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'default')
      ->setComponent($field_name, [
        'type' => 'daterange_datelist',
        'settings' => [
          'increment' => 1,
          'date_order' => 'YMD',
          'time_type' => '24',
        ],
      ])
      ->save();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Test the widget for validation notifications.
    foreach ($this->datelistDataProvider() as $data) {
      list($start_date_value, $end_date_value, $expected) = $data;

      // Display creation form.
      $this->drupalGet('entity_test/add');

      // Submit a partial date and ensure and error message is provided.
      $edit = [];
      foreach ($start_date_value as $part => $value) {
        $edit["{$field_name}[0][value][$part]"] = $value;
      }
      foreach ($end_date_value as $part => $value) {
        $edit["{$field_name}[0][end_value][$part]"] = $value;
      }

      $this->drupalPostForm(NULL, $edit, t('Save'));
      $this->assertResponse(200);
      foreach ($expected as $expected_text) {
        $this->assertText(t($expected_text));
      }
    }

    // Test the widget for complete input with zeros as part of selections.
    $this->drupalGet('entity_test/add');

    $start_date_value = ['year' => 2012, 'month' => 12, 'day' => 31, 'hour' => 0, 'minute' => 0];
    $end_date_value = ['year' => 2013, 'month' => 1, 'day' => 15, 'hour' => 3, 'minute' => 30];
    $edit = [];
    foreach ($start_date_value as $part => $value) {
      $edit["{$field_name}[0][value][$part]"] = $value;
    }
    foreach ($end_date_value as $part => $value) {
      $edit["{$field_name}[0][end_value][$part]"] = $value;
    }

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]));

    // Test the widget to ensure zeros are not deselected on validation.
    $this->drupalGet('entity_test/add');

    $start_date_value = ['year' => 2012, 'month' => 12, 'day' => 31, 'hour' => 0, 'minute' => 0];
    $end_date_value = ['year' => 2013, 'month' => 1, 'day' => 15, 'hour' => 3, 'minute' => 0];
    $edit = [];
    foreach ($start_date_value as $part => $value) {
      $edit["{$field_name}[0][value][$part]"] = $value;
    }
    foreach ($end_date_value as $part => $value) {
      $edit["{$field_name}[0][end_value][$part]"] = $value;
    }

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertOptionSelected("edit-$field_name-0-value-minute", '0', 'Correct minute selected.');
    $this->assertOptionSelected("edit-$field_name-0-end-value-minute", '0', 'Correct minute selected.');
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
      [
        ['year' => 2012, 'month' => '', 'day' => '', 'hour' => '', 'minute' => ''],
        ['year' => 2013, 'month' => '1', 'day' => '15', 'hour' => '3', 'minute' => '30'], [
          'A value must be selected for month.',
          'A value must be selected for day.',
          'A value must be selected for hour.',
          'A value must be selected for minute.',
        ],
      ],
      // Year and Month selected, validation error on Day, Hour, Minute.
      [
        ['year' => 2012, 'month' => '12', 'day' => '', 'hour' => '', 'minute' => ''],
        ['year' => 2013, 'month' => '1', 'day' => '15', 'hour' => '3', 'minute' => '30'], [
          'A value must be selected for day.',
          'A value must be selected for hour.',
          'A value must be selected for minute.',
        ],
      ],
      // Year, Month and Day selected, validation error on Hour, Minute.
      [
        ['year' => 2012, 'month' => '12', 'day' => '31', 'hour' => '', 'minute' => ''],
        ['year' => 2013, 'month' => '1', 'day' => '15', 'hour' => '3', 'minute' => '30'], [
          'A value must be selected for hour.',
          'A value must be selected for minute.',
        ],
      ],
      // Year, Month, Day and Hour selected, validation error on Minute only.
      [
        ['year' => 2012, 'month' => '12', 'day' => '31', 'hour' => '0', 'minute' => ''],
        ['year' => 2013, 'month' => '1', 'day' => '15', 'hour' => '3', 'minute' => '30'], [
          'A value must be selected for minute.',
        ],
      ],
      // Year selected, validation error on Month, Day, Hour, Minute.
      [
        ['year' => 2012, 'month' => '12', 'day' => '31', 'hour' => '0', 'minute' => '0'],
        ['year' => 2013, 'month' => '', 'day' => '', 'hour' => '', 'minute' => ''], [
          'A value must be selected for month.',
          'A value must be selected for day.',
          'A value must be selected for hour.',
          'A value must be selected for minute.',
        ],
      ],
      // Year and Month selected, validation error on Day, Hour, Minute.
      [
        ['year' => 2012, 'month' => '12', 'day' => '31', 'hour' => '0', 'minute' => '0'],
        ['year' => 2013, 'month' => '1', 'day' => '', 'hour' => '', 'minute' => ''], [
          'A value must be selected for day.',
          'A value must be selected for hour.',
          'A value must be selected for minute.',
        ],
      ],
      // Year, Month and Day selected, validation error on Hour, Minute.
      [
        ['year' => 2012, 'month' => '12', 'day' => '31', 'hour' => '0', 'minute' => '0'],
        ['year' => 2013, 'month' => '1', 'day' => '15', 'hour' => '', 'minute' => ''], [
          'A value must be selected for hour.',
          'A value must be selected for minute.',
        ],
      ],
      // Year, Month, Day and Hour selected, validation error on Minute only.
      [
        ['year' => 2012, 'month' => '12', 'day' => '31', 'hour' => '0', 'minute' => '0'],
        ['year' => 2013, 'month' => '1', 'day' => '15', 'hour' => '3', 'minute' => ''], [
          'A value must be selected for minute.',
        ],
      ],
    ];
  }

  /**
   * Test default value functionality.
   */
  public function testDefaultValue() {
    // Create a test content type.
    $this->drupalCreateContentType(['type' => 'date_content']);

    // Create a field storage with settings to validate.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'daterange',
      'settings' => ['datetime_type' => DateRangeItem::DATETIME_TYPE_DATE],
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'date_content',
    ]);
    $field->save();

    // Set now as default_value.
    $field_edit = [
      'default_value_input[default_date_type]' => 'now',
      'default_value_input[default_end_date_type]' => 'now',
    ];
    $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name, $field_edit, t('Save settings'));

    // Check that default value is selected in default value form.
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->assertOptionSelected('edit-default-value-input-default-date-type', 'now', 'The default start value is selected in instance settings page');
    $this->assertFieldByName('default_value_input[default_date]', '', 'The relative start default value is empty in instance settings page');
    $this->assertOptionSelected('edit-default-value-input-default-end-date-type', 'now', 'The default end value is selected in instance settings page');
    $this->assertFieldByName('default_value_input[default_end_date]', '', 'The relative end default value is empty in instance settings page');

    // Check if default_date has been stored successfully.
    $config_entity = $this->config('field.field.node.date_content.' . $field_name)->get();
    $this->assertEqual($config_entity['default_value'][0], [
      'default_date_type' => 'now',
      'default_date' => 'now',
      'default_end_date_type' => 'now',
      'default_end_date' => 'now',
    ], 'Default value has been stored successfully');

    // Clear field cache in order to avoid stale cache values.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Create a new node to check that datetime field default value is today.
    $new_node = Node::create(['type' => 'date_content']);
    $expected_date = new DrupalDateTime('now', DATETIME_STORAGE_TIMEZONE);
    $this->assertEqual($new_node->get($field_name)->offsetGet(0)->value, $expected_date->format(DATETIME_DATE_STORAGE_FORMAT));
    $this->assertEqual($new_node->get($field_name)->offsetGet(0)->end_value, $expected_date->format(DATETIME_DATE_STORAGE_FORMAT));

    // Set an invalid relative default_value to test validation.
    $field_edit = [
      'default_value_input[default_date_type]' => 'relative',
      'default_value_input[default_date]' => 'invalid date',
      'default_value_input[default_end_date_type]' => 'relative',
      'default_value_input[default_end_date]' => '+1 day',
    ];
    $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name, $field_edit, t('Save settings'));
    $this->assertText('The relative start date value entered is invalid.');

    $field_edit = [
      'default_value_input[default_date_type]' => 'relative',
      'default_value_input[default_date]' => '+1 day',
      'default_value_input[default_end_date_type]' => 'relative',
      'default_value_input[default_end_date]' => 'invalid date',
    ];
    $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name, $field_edit, t('Save settings'));
    $this->assertText('The relative end date value entered is invalid.');

    // Set a relative default_value.
    $field_edit = [
      'default_value_input[default_date_type]' => 'relative',
      'default_value_input[default_date]' => '+45 days',
      'default_value_input[default_end_date_type]' => 'relative',
      'default_value_input[default_end_date]' => '+90 days',
    ];
    $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name, $field_edit, t('Save settings'));

    // Check that default value is selected in default value form.
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->assertOptionSelected('edit-default-value-input-default-date-type', 'relative', 'The default start value is selected in instance settings page');
    $this->assertFieldByName('default_value_input[default_date]', '+45 days', 'The relative default start value is displayed in instance settings page');
    $this->assertOptionSelected('edit-default-value-input-default-end-date-type', 'relative', 'The default end value is selected in instance settings page');
    $this->assertFieldByName('default_value_input[default_end_date]', '+90 days', 'The relative default end value is displayed in instance settings page');

    // Check if default_date has been stored successfully.
    $config_entity = $this->config('field.field.node.date_content.' . $field_name)->get();
    $this->assertEqual($config_entity['default_value'][0], [
      'default_date_type' => 'relative',
      'default_date' => '+45 days',
      'default_end_date_type' => 'relative',
      'default_end_date' => '+90 days',
    ], 'Default value has been stored successfully');

    // Clear field cache in order to avoid stale cache values.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Create a new node to check that datetime field default value is +90 days.
    $new_node = Node::create(['type' => 'date_content']);
    $expected_start_date = new DrupalDateTime('+45 days', DATETIME_STORAGE_TIMEZONE);
    $expected_end_date = new DrupalDateTime('+90 days', DATETIME_STORAGE_TIMEZONE);
    $this->assertEqual($new_node->get($field_name)->offsetGet(0)->value, $expected_start_date->format(DATETIME_DATE_STORAGE_FORMAT));
    $this->assertEqual($new_node->get($field_name)->offsetGet(0)->end_value, $expected_end_date->format(DATETIME_DATE_STORAGE_FORMAT));

    // Remove default value.
    $field_edit = [
      'default_value_input[default_date_type]' => '',
      'default_value_input[default_end_date_type]' => '',
    ];
    $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name, $field_edit, t('Save settings'));

    // Check that default value is selected in default value form.
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->assertOptionSelected('edit-default-value-input-default-date-type', '', 'The default start value is selected in instance settings page');
    $this->assertFieldByName('default_value_input[default_date]', '', 'The relative default start value is empty in instance settings page');
    $this->assertOptionSelected('edit-default-value-input-default-end-date-type', '', 'The default end value is selected in instance settings page');
    $this->assertFieldByName('default_value_input[default_end_date]', '', 'The relative default end value is empty in instance settings page');

    // Check if default_date has been stored successfully.
    $config_entity = $this->config('field.field.node.date_content.' . $field_name)->get();
    $this->assertTrue(empty($config_entity['default_value']), 'Empty default value has been stored successfully');

    // Clear field cache in order to avoid stale cache values.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Create a new node to check that datetime field default value is not set.
    $new_node = Node::create(['type' => 'date_content']);
    $this->assertNull($new_node->get($field_name)->value, 'Default value is not set');

    // Set now as default_value for start date only.
    entity_get_form_display('node', 'date_content', 'default')
      ->setComponent($field_name, [
        'type' => 'datetime_default',
      ])
      ->save();

    $expected_date = new DrupalDateTime('now', DATETIME_STORAGE_TIMEZONE);

    $field_edit = [
      'default_value_input[default_date_type]' => 'now',
      'default_value_input[default_end_date_type]' => '',
    ];
    $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name, $field_edit, t('Save settings'));

    // Make sure only the start value is populated on node add page.
    $this->drupalGet('node/add/date_content');
    $this->assertFieldByName("{$field_name}[0][value][date]", $expected_date->format(DATETIME_DATE_STORAGE_FORMAT), 'Start date element populated.');
    $this->assertFieldByName("{$field_name}[0][end_value][date]", '', 'End date element empty.');

    // Set now as default_value for end date only.
    $field_edit = [
      'default_value_input[default_date_type]' => '',
      'default_value_input[default_end_date_type]' => 'now',
    ];
    $this->drupalPostForm('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name, $field_edit, t('Save settings'));

    // Make sure only the start value is populated on node add page.
    $this->drupalGet('node/add/date_content');
    $this->assertFieldByName("{$field_name}[0][value][date]", '', 'Start date element empty.');
    $this->assertFieldByName("{$field_name}[0][end_value][date]", $expected_date->format(DATETIME_DATE_STORAGE_FORMAT), 'End date element populated.');
  }

  /**
   * Test that invalid values are caught and marked as invalid.
   */
  public function testInvalidField() {
    // Change the field to a datetime field.
    $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_DATETIME);
    $this->fieldStorage->save();
    $field_name = $this->fieldStorage->getName();

    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value][date]", '', 'Start date element found.');
    $this->assertFieldByName("{$field_name}[0][value][time]", '', 'Start time element found.');
    $this->assertFieldByName("{$field_name}[0][end_value][date]", '', 'End date element found.');
    $this->assertFieldByName("{$field_name}[0][end_value][time]", '', 'End time element found.');

    // Submit invalid start dates and ensure they is not accepted.
    $date_value = '';
    $edit = [
      "{$field_name}[0][value][date]" => $date_value,
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', 'Empty start date value has been caught.');

    $date_value = 'aaaa-12-01';
    $edit = [
      "{$field_name}[0][value][date]" => $date_value,
      "{$field_name}[0][value][time]" => '00:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', new FormattableMarkup('Invalid start year value %date has been caught.', ['%date' => $date_value]));

    $date_value = '2012-75-01';
    $edit = [
      "{$field_name}[0][value][date]" => $date_value,
      "{$field_name}[0][value][time]" => '00:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', new FormattableMarkup('Invalid start month value %date has been caught.', ['%date' => $date_value]));

    $date_value = '2012-12-99';
    $edit = [
      "{$field_name}[0][value][date]" => $date_value,
      "{$field_name}[0][value][time]" => '00:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', new FormattableMarkup('Invalid start day value %date has been caught.', ['%date' => $date_value]));

    // Submit invalid start times and ensure they is not accepted.
    $time_value = '';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => $time_value,
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', 'Empty start time value has been caught.');

    $time_value = '49:00:00';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => $time_value,
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', new FormattableMarkup('Invalid start hour value %time has been caught.', ['%time' => $time_value]));

    $time_value = '12:99:00';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => $time_value,
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', new FormattableMarkup('Invalid start minute value %time has been caught.', ['%time' => $time_value]));

    $time_value = '12:15:99';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => $time_value,
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', new FormattableMarkup('Invalid start second value %time has been caught.', ['%time' => $time_value]));

    // Submit invalid end dates and ensure they is not accepted.
    $date_value = '';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => $date_value,
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', 'Empty end date value has been caught.');

    $date_value = 'aaaa-12-01';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => $date_value,
      "{$field_name}[0][end_value][time]" => '00:00:00',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', new FormattableMarkup('Invalid end year value %date has been caught.', ['%date' => $date_value]));

    $date_value = '2012-75-01';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => $date_value,
      "{$field_name}[0][end_value][time]" => '00:00:00',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', new FormattableMarkup('Invalid end month value %date has been caught.', ['%date' => $date_value]));

    $date_value = '2012-12-99';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => $date_value,
      "{$field_name}[0][end_value][time]" => '00:00:00',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', new FormattableMarkup('Invalid end day value %date has been caught.', ['%date' => $date_value]));

    // Submit invalid start times and ensure they is not accepted.
    $time_value = '';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => $time_value,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', 'Empty end time value has been caught.');

    $time_value = '49:00:00';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => $time_value,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', new FormattableMarkup('Invalid end hour value %time has been caught.', ['%time' => $time_value]));

    $time_value = '12:99:00';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => $time_value,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', new FormattableMarkup('Invalid end minute value %time has been caught.', ['%time' => $time_value]));

    $time_value = '12:15:99';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => $time_value,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('date is invalid', new FormattableMarkup('Invalid end second value %time has been caught.', ['%time' => $time_value]));

    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => '2010-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(new FormattableMarkup('The @title end date cannot be before the start date', ['@title' => $field_name]), 'End date before start date has been caught.');

    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '11:00:00',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(new FormattableMarkup('The @title end date cannot be before the start date', ['@title' => $field_name]), 'End time before start time has been caught.');
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
      'type' => 'daterange',
      'settings' => [
        'datetime_type' => DateRangeItem::DATETIME_TYPE_DATE,
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
      $field_name . '[0][end_value][date]' => '2016-04-02',
    ];
    $this->drupalPostForm('node/add/date_content', $edit, t('Save'));
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name . '/storage');
    $result = $this->xpath("//*[@id='edit-settings-datetime-type' and contains(@disabled, 'disabled')]");
    $this->assertEqual(count($result), 1, "Changing datetime setting is disabled.");
    $this->assertText('There is data for this field in the database. The field settings can no longer be changed.');
  }

}
