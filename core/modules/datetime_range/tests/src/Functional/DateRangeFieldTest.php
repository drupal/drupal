<?php

namespace Drupal\Tests\datetime_range\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Tests\datetime\Functional\DateTestBase;
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
  protected static $modules = ['datetime_range'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
    $field_label = $this->field->label();

    // Loop through defined timezones to test that date-only fields work at the
    // extremes.
    foreach (static::$timezones as $timezone) {

      $this->setSiteTimezone($timezone);
      $this->assertEquals($timezone, $this->config('system.date')->get('timezone.default'), 'Time zone set to ' . $timezone);

      // Ensure field is set to a date-only field.
      $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_DATE);
      $this->fieldStorage->save();

      // Display creation form.
      $this->drupalGet('entity_test/add');
      $this->assertSession()->fieldValueEquals("{$field_name}[0][value][date]", '');
      $this->assertSession()->fieldValueEquals("{$field_name}[0][end_value][date]", '');
      $this->assertSession()->elementExists('xpath', '//*[@id="edit-' . $field_name . '-wrapper"]//label[contains(@class, "js-form-required")]');
      $this->assertSession()->fieldNotExists("{$field_name}[0][value][time]");
      $this->assertSession()->fieldNotExists("{$field_name}[0][end_value][time]");
      $this->assertSession()->elementTextContains('xpath', '//fieldset[@id="edit-' . $field_name . '-0"]/legend', $field_label);
      $this->assertSession()->elementExists('xpath', '//fieldset[@aria-describedby="edit-' . $field_name . '-0--description"]');
      $this->assertSession()->elementExists('xpath', '//div[@id="edit-' . $field_name . '-0--description"]');

      // Build up dates in the UTC timezone.
      $value = '2012-12-31 00:00:00';
      $start_date = new DrupalDateTime($value, 'UTC');
      $end_value = '2013-06-06 00:00:00';
      $end_date = new DrupalDateTime($end_value, 'UTC');

      // Submit a valid date and ensure it is accepted.
      $date_format = DateFormat::load('html_date')->getPattern();
      $time_format = DateFormat::load('html_time')->getPattern();

      $edit = [
        "{$field_name}[0][value][date]" => $start_date->format($date_format),
        "{$field_name}[0][end_value][date]" => $end_date->format($date_format),
      ];
      $this->submitForm($edit, 'Save');
      preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
      $id = $match[1];
      $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');
      $this->assertSession()->responseContains($start_date->format($date_format));
      $this->assertSession()->responseNotContains($start_date->format($time_format));
      $this->assertSession()->responseContains($end_date->format($date_format));
      $this->assertSession()->responseNotContains($end_date->format($time_format));

      // Verify the date doesn't change when entity is edited through the form.
      $entity = EntityTest::load($id);
      $this->assertEquals('2012-12-31', $entity->{$field_name}->value);
      $this->assertEquals('2013-06-06', $entity->{$field_name}->end_value);
      $this->drupalGet('entity_test/manage/' . $id . '/edit');
      $this->submitForm([], 'Save');
      $this->drupalGet('entity_test/manage/' . $id . '/edit');
      $this->submitForm([], 'Save');
      $this->drupalGet('entity_test/manage/' . $id . '/edit');
      $this->submitForm([], 'Save');
      $entity = EntityTest::load($id);
      $this->assertEquals('2012-12-31', $entity->{$field_name}->value);
      $this->assertEquals('2013-06-06', $entity->{$field_name}->end_value);

      // Formats that display a time component for date-only fields will display
      // the default time, so that is applied before calculating the expected
      // value.
      $this->massageTestDate($start_date);
      $this->massageTestDate($end_date);

      // Reset display options since these get changed below.
      $this->displayOptions = [
        'type' => 'daterange_default',
        'label' => 'hidden',
        'settings' => [
          'format_type' => 'long',
          'separator' => 'THESEPARATOR',
        ] + $this->defaultSettings,
      ];

      /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
      $display_repository = \Drupal::service('entity_display.repository');

      // Verify that the default formatter works.
      $display_repository->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
        ->setComponent($field_name, $this->displayOptions)
        ->save();

      $start_expected = $this->dateFormatter->format($start_date->getTimestamp(), 'long', '', DateTimeItemInterface::STORAGE_TIMEZONE);
      $start_expected_iso = $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', DateTimeItemInterface::STORAGE_TIMEZONE);
      $start_expected_markup = '<time datetime="' . $start_expected_iso . '">' . $start_expected . '</time>';
      $end_expected = $this->dateFormatter->format($end_date->getTimestamp(), 'long', '', DateTimeItemInterface::STORAGE_TIMEZONE);
      $end_expected_iso = $this->dateFormatter->format($end_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', DateTimeItemInterface::STORAGE_TIMEZONE);
      $end_expected_markup = '<time datetime="' . $end_expected_iso . '">' . $end_expected . '</time>';
      $output = $this->renderTestEntity($id);
      $this->assertStringContainsString($start_expected_markup, $output, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute in %timezone.', [
        '%value' => 'long',
        '%expected' => $start_expected,
        '%expected_iso' => $start_expected_iso,
        '%timezone' => $timezone,
      ]));
      $this->assertStringContainsString($end_expected_markup, $output, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute in %timezone.', [
        '%value' => 'long',
        '%expected' => $end_expected,
        '%expected_iso' => $end_expected_iso,
        '%timezone' => $timezone,
      ]));
      $this->assertStringContainsString(' THESEPARATOR ', $output, 'Found proper separator');

      // Verify that hook_entity_prepare_view can add attributes.
      // @see entity_test_entity_prepare_view()
      $this->drupalGet('entity_test/' . $id);
      $this->assertSession()->elementExists('xpath', '//div[@data-field-item-attr="foobar"]');

      // Verify that the plain formatter works.
      $this->displayOptions['type'] = 'daterange_plain';
      $this->displayOptions['settings'] = $this->defaultSettings;
      $this->container->get('entity_display.repository')
        ->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
        ->setComponent($field_name, $this->displayOptions)
        ->save();
      $expected = $start_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT) . ' - ' . $end_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
      $output = $this->renderTestEntity($id);
      $this->assertStringContainsString($expected, $output, new FormattableMarkup('Formatted date field using plain format displayed as %expected in %timezone.', [
        '%expected' => $expected,
        '%timezone' => $timezone,
      ]));

      // Verify that the custom formatter works.
      $this->displayOptions['type'] = 'daterange_custom';
      $this->displayOptions['settings'] = ['date_format' => 'm/d/Y'] + $this->defaultSettings;
      $display_repository->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
        ->setComponent($field_name, $this->displayOptions)
        ->save();
      $expected = $start_date->format($this->displayOptions['settings']['date_format']) . ' - ' . $end_date->format($this->displayOptions['settings']['date_format']);
      $output = $this->renderTestEntity($id);
      $this->assertStringContainsString($expected, $output, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected in %timezone.', [
        '%expected' => $expected,
        '%timezone' => $timezone,
      ]));

      // Test that allowed markup in custom format is preserved and XSS is
      // removed.
      $this->displayOptions['settings']['date_format'] = '\\<\\s\\t\\r\\o\\n\\g\\>m/d/Y\\<\\/\\s\\t\\r\\o\\n\\g\\>\\<\\s\\c\\r\\i\\p\\t\\>\\a\\l\\e\\r\\t\\(\\S\\t\\r\\i\\n\\g\\.\\f\\r\\o\\m\\C\\h\\a\\r\\C\\o\\d\\e\\(\\8\\8\\,\\8\\3\\,\\8\\3\\)\\)\\<\\/\\s\\c\\r\\i\\p\\t\\>';
      $display_repository->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
        ->setComponent($field_name, $this->displayOptions)
        ->save();
      $expected = '<strong>' . $start_date->format('m/d/Y') . '</strong>alert(String.fromCharCode(88,83,83)) - <strong>' . $end_date->format('m/d/Y') . '</strong>alert(String.fromCharCode(88,83,83))';
      $output = $this->renderTestEntity($id);
      $this->assertStringContainsString($expected, $output, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected in %timezone.', [
        '%expected' => $expected,
        '%timezone' => $timezone,
      ]));

      // Test formatters when start date and end date are the same
      $this->drupalGet('entity_test/add');
      $value = '2012-12-31 00:00:00';
      $start_date = new DrupalDateTime($value, 'UTC');

      $date_format = DateFormat::load('html_date')->getPattern();
      $time_format = DateFormat::load('html_time')->getPattern();

      $edit = [
        "{$field_name}[0][value][date]" => $start_date->format($date_format),
        "{$field_name}[0][end_value][date]" => $start_date->format($date_format),
      ];

      $this->submitForm($edit, 'Save');
      preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
      $id = $match[1];
      $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');

      $this->massageTestDate($start_date);

      $this->displayOptions = [
        'type' => 'daterange_default',
        'label' => 'hidden',
        'settings' => [
          'format_type' => 'long',
          'separator' => 'THESEPARATOR',
        ] + $this->defaultSettings,
      ];

      $display_repository->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
        ->setComponent($field_name, $this->displayOptions)
        ->save();

      $start_expected = $this->dateFormatter->format($start_date->getTimestamp(), 'long', '', DateTimeItemInterface::STORAGE_TIMEZONE);
      $start_expected_iso = $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', DateTimeItemInterface::STORAGE_TIMEZONE);
      $start_expected_markup = '<time datetime="' . $start_expected_iso . '">' . $start_expected . '</time>';
      $output = $this->renderTestEntity($id);
      $this->assertStringContainsString($start_expected_markup, $output, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute in %timezone.', [
        '%value' => 'long',
        '%expected' => $start_expected,
        '%expected_iso' => $start_expected_iso,
        '%timezone' => $timezone,
      ]));
      $this->assertStringNotContainsString(' THESEPARATOR ', $output, 'Separator not found on page in ' . $timezone);

      // Verify that hook_entity_prepare_view can add attributes.
      // @see entity_test_entity_prepare_view()
      $this->drupalGet('entity_test/' . $id);
      $this->assertSession()->elementExists('xpath', '//time[@data-field-item-attr="foobar"]');

      $this->displayOptions['type'] = 'daterange_plain';
      $this->displayOptions['settings'] = $this->defaultSettings;
      $this->container->get('entity_display.repository')
        ->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
        ->setComponent($field_name, $this->displayOptions)
        ->save();
      $expected = $start_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
      $output = $this->renderTestEntity($id);
      $this->assertStringContainsString($expected, $output, new FormattableMarkup('Formatted date field using plain format displayed as %expected in %timezone.', [
        '%expected' => $expected,
        '%timezone' => $timezone,
      ]));
      $this->assertStringNotContainsString(' THESEPARATOR ', $output, 'Separator not found on page');

      $this->displayOptions['type'] = 'daterange_custom';
      $this->displayOptions['settings'] = ['date_format' => 'm/d/Y'] + $this->defaultSettings;
      $display_repository->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
        ->setComponent($field_name, $this->displayOptions)
        ->save();
      $expected = $start_date->format($this->displayOptions['settings']['date_format']);
      $output = $this->renderTestEntity($id);
      $this->assertStringContainsString($expected, $output, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected in %timezone.', [
        '%expected' => $expected,
        '%timezone' => $timezone,
      ]));
      $this->assertStringNotContainsString(' THESEPARATOR ', $output, 'Separator not found on page');
    }
  }

  /**
   * Tests date and time field.
   */
  public function testDatetimeRangeField() {
    $field_name = $this->fieldStorage->getName();
    $field_label = $this->field->label();

    // Ensure the field to a datetime field.
    $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_DATETIME);
    $this->fieldStorage->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][value][date]", '');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][value][time]", '');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][end_value][date]", '');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][end_value][time]", '');
    $this->assertSession()->elementTextContains('xpath', '//fieldset[@id="edit-' . $field_name . '-0"]/legend', $field_label);
    $this->assertSession()->elementExists('xpath', '//fieldset[@aria-describedby="edit-' . $field_name . '-0--description"]');
    $this->assertSession()->elementExists('xpath', '//div[@id="edit-' . $field_name . '-0--description"]');

    // Build up dates in the UTC timezone.
    $value = '2012-12-31 00:00:00';
    $start_date = new DrupalDateTime($value, 'UTC');
    $end_value = '2013-06-06 00:00:00';
    $end_date = new DrupalDateTime($end_value, 'UTC');

    // Update the timezone to the system default.
    $start_date->setTimezone(timezone_open(date_default_timezone_get()));
    $end_date->setTimezone(timezone_open(date_default_timezone_get()));

    // Submit a valid date and ensure it is accepted.
    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();

    $edit = [
      "{$field_name}[0][value][date]" => $start_date->format($date_format),
      "{$field_name}[0][value][time]" => $start_date->format($time_format),
      "{$field_name}[0][end_value][date]" => $end_date->format($date_format),
      "{$field_name}[0][end_value][time]" => $end_date->format($time_format),
    ];
    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');
    $this->assertSession()->responseContains($start_date->format($date_format));
    $this->assertSession()->responseContains($start_date->format($time_format));
    $this->assertSession()->responseContains($end_date->format($date_format));
    $this->assertSession()->responseContains($end_date->format($time_format));

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Verify that the default formatter works.
    $this->displayOptions['settings'] = [
      'format_type' => 'long',
      'separator' => 'THESEPARATOR',
    ] + $this->defaultSettings;
    $display_repository->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();

    $start_expected = $this->dateFormatter->format($start_date->getTimestamp(), 'long');
    $start_expected_iso = $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
    $start_expected_markup = '<time datetime="' . $start_expected_iso . '">' . $start_expected . '</time>';
    $end_expected = $this->dateFormatter->format($end_date->getTimestamp(), 'long');
    $end_expected_iso = $this->dateFormatter->format($end_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
    $end_expected_markup = '<time datetime="' . $end_expected_iso . '">' . $end_expected . '</time>';
    $output = $this->renderTestEntity($id);
    $this->assertStringContainsString($start_expected_markup, $output, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', ['%value' => 'long', '%expected' => $start_expected, '%expected_iso' => $start_expected_iso]));
    $this->assertStringContainsString($end_expected_markup, $output, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', ['%value' => 'long', '%expected' => $end_expected, '%expected_iso' => $end_expected_iso]));
    $this->assertStringContainsString(' THESEPARATOR ', $output, 'Found proper separator');

    // Verify that hook_entity_prepare_view can add attributes.
    // @see entity_test_entity_prepare_view()
    $this->drupalGet('entity_test/' . $id);
    $this->assertSession()->elementExists('xpath', '//div[@data-field-item-attr="foobar"]');

    // Verify that the plain formatter works.
    $this->displayOptions['type'] = 'daterange_plain';
    $this->displayOptions['settings'] = $this->defaultSettings;
    $this->container->get('entity_display.repository')
      ->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT) . ' - ' . $end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $output = $this->renderTestEntity($id);
    $this->assertStringContainsString($expected, $output, new FormattableMarkup('Formatted date field using plain format displayed as %expected.', ['%expected' => $expected]));

    // Verify that the 'datetime_custom' formatter works.
    $this->displayOptions['type'] = 'daterange_custom';
    $this->displayOptions['settings'] = ['date_format' => 'm/d/Y g:i:s A'] + $this->defaultSettings;
    $display_repository->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format($this->displayOptions['settings']['date_format']) . ' - ' . $end_date->format($this->displayOptions['settings']['date_format']);
    $output = $this->renderTestEntity($id);
    $this->assertStringContainsString($expected, $output, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected.', ['%expected' => $expected]));

    // Verify that the 'timezone_override' setting works.
    $this->displayOptions['type'] = 'daterange_custom';
    $this->displayOptions['settings'] = ['date_format' => 'm/d/Y g:i:s A', 'timezone_override' => 'America/New_York'] + $this->defaultSettings;
    $display_repository->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format($this->displayOptions['settings']['date_format'], ['timezone' => 'America/New_York']);
    $expected .= ' - ' . $end_date->format($this->displayOptions['settings']['date_format'], ['timezone' => 'America/New_York']);
    $output = $this->renderTestEntity($id);
    $this->assertStringContainsString($expected, $output, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected.', ['%expected' => $expected]));

    // Test formatters when start date and end date are the same
    $this->drupalGet('entity_test/add');
    $value = '2012-12-31 00:00:00';
    $start_date = new DrupalDateTime($value, 'UTC');
    $start_date->setTimezone(timezone_open(date_default_timezone_get()));

    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();

    $edit = [
      "{$field_name}[0][value][date]" => $start_date->format($date_format),
      "{$field_name}[0][value][time]" => $start_date->format($time_format),
      "{$field_name}[0][end_value][date]" => $start_date->format($date_format),
      "{$field_name}[0][end_value][time]" => $start_date->format($time_format),
    ];

    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');

    $this->displayOptions = [
      'type' => 'daterange_default',
      'label' => 'hidden',
      'settings' => [
        'format_type' => 'long',
        'separator' => 'THESEPARATOR',
      ] + $this->defaultSettings,
    ];

    $display_repository->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();

    $start_expected = $this->dateFormatter->format($start_date->getTimestamp(), 'long');
    $start_expected_iso = $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
    $start_expected_markup = '<time datetime="' . $start_expected_iso . '">' . $start_expected . '</time>';
    $output = $this->renderTestEntity($id);
    $this->assertStringContainsString($start_expected_markup, $output, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', ['%value' => 'long', '%expected' => $start_expected, '%expected_iso' => $start_expected_iso]));
    $this->assertStringNotContainsString(' THESEPARATOR ', $output, 'Separator not found on page');

    // Verify that hook_entity_prepare_view can add attributes.
    // @see entity_test_entity_prepare_view()
    $this->drupalGet('entity_test/' . $id);
    $this->assertSession()->elementExists('xpath', '//time[@data-field-item-attr="foobar"]');

    $this->displayOptions['type'] = 'daterange_plain';
    $this->displayOptions['settings'] = $this->defaultSettings;
    $this->container->get('entity_display.repository')
      ->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $output = $this->renderTestEntity($id);
    $this->assertStringContainsString($expected, $output, new FormattableMarkup('Formatted date field using plain format displayed as %expected.', ['%expected' => $expected]));
    $this->assertStringNotContainsString(' THESEPARATOR ', $output, 'Separator not found on page');

    $this->displayOptions['type'] = 'daterange_custom';
    $this->displayOptions['settings'] = ['date_format' => 'm/d/Y g:i:s A'] + $this->defaultSettings;
    $display_repository->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format($this->displayOptions['settings']['date_format']);
    $output = $this->renderTestEntity($id);
    $this->assertStringContainsString($expected, $output, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected.', ['%expected' => $expected]));
    $this->assertStringNotContainsString(' THESEPARATOR ', $output, 'Separator not found on page');
  }

  /**
   * Tests all-day field.
   */
  public function testAlldayRangeField() {
    $field_name = $this->fieldStorage->getName();
    $field_label = $this->field->label();

    // Ensure field is set to an all-day field.
    $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_ALLDAY);
    $this->fieldStorage->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][value][date]", '');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][end_value][date]", '');
    $this->assertSession()->elementExists('xpath', '//*[@id="edit-' . $field_name . '-wrapper"]//label[contains(@class, "js-form-required")]');
    $this->assertSession()->fieldNotExists("{$field_name}[0][value][time]");
    $this->assertSession()->fieldNotExists("{$field_name}[0][end_value][time]");
    $this->assertSession()->elementTextContains('xpath', '//fieldset[@id="edit-' . $field_name . '-0"]/legend', $field_label);
    $this->assertSession()->elementExists('xpath', '//fieldset[@aria-describedby="edit-' . $field_name . '-0--description"]');
    $this->assertSession()->elementExists('xpath', '//div[@id="edit-' . $field_name . '-0--description"]');

    // Build up dates in the proper timezone.
    $value = '2012-12-31 00:00:00';
    $start_date = new DrupalDateTime($value, timezone_open(date_default_timezone_get()));
    $end_value = '2013-06-06 23:59:59';
    $end_date = new DrupalDateTime($end_value, timezone_open(date_default_timezone_get()));

    // Submit a valid date and ensure it is accepted.
    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();

    $edit = [
      "{$field_name}[0][value][date]" => $start_date->format($date_format),
      "{$field_name}[0][end_value][date]" => $end_date->format($date_format),
    ];
    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');
    $this->assertSession()->responseContains($start_date->format($date_format));
    $this->assertSession()->responseNotContains($start_date->format($time_format));
    $this->assertSession()->responseContains($end_date->format($date_format));
    $this->assertSession()->responseNotContains($end_date->format($time_format));

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Verify that the default formatter works.
    $this->displayOptions['settings'] = [
      'format_type' => 'long',
      'separator' => 'THESEPARATOR',
    ] + $this->defaultSettings;
    $display_repository->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();

    $start_expected = $this->dateFormatter->format($start_date->getTimestamp(), 'long');
    $start_expected_iso = $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
    $start_expected_markup = '<time datetime="' . $start_expected_iso . '">' . $start_expected . '</time>';
    $end_expected = $this->dateFormatter->format($end_date->getTimestamp(), 'long');
    $end_expected_iso = $this->dateFormatter->format($end_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
    $end_expected_markup = '<time datetime="' . $end_expected_iso . '">' . $end_expected . '</time>';
    $output = $this->renderTestEntity($id);
    $this->assertStringContainsString($start_expected_markup, $output, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', ['%value' => 'long', '%expected' => $start_expected, '%expected_iso' => $start_expected_iso]));
    $this->assertStringContainsString($end_expected_markup, $output, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', ['%value' => 'long', '%expected' => $end_expected, '%expected_iso' => $end_expected_iso]));
    $this->assertStringContainsString(' THESEPARATOR ', $output, 'Found proper separator');

    // Verify that hook_entity_prepare_view can add attributes.
    // @see entity_test_entity_prepare_view()
    $this->drupalGet('entity_test/' . $id);
    $this->assertSession()->elementExists('xpath', '//div[@data-field-item-attr="foobar"]');

    // Verify that the plain formatter works.
    $this->displayOptions['type'] = 'daterange_plain';
    $this->displayOptions['settings'] = $this->defaultSettings;
    $this->container->get('entity_display.repository')
      ->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT) . ' - ' . $end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $output = $this->renderTestEntity($id);
    $this->assertStringContainsString($expected, $output, new FormattableMarkup('Formatted date field using plain format displayed as %expected.', ['%expected' => $expected]));

    // Verify that the custom formatter works.
    $this->displayOptions['type'] = 'daterange_custom';
    $this->displayOptions['settings'] = ['date_format' => 'm/d/Y'] + $this->defaultSettings;
    $display_repository->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format($this->displayOptions['settings']['date_format']) . ' - ' . $end_date->format($this->displayOptions['settings']['date_format']);
    $output = $this->renderTestEntity($id);
    $this->assertStringContainsString($expected, $output, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected.', ['%expected' => $expected]));

    // Verify that the 'timezone_override' setting works.
    $this->displayOptions['type'] = 'daterange_custom';
    $this->displayOptions['settings'] = ['date_format' => 'm/d/Y g:i:s A', 'timezone_override' => 'America/New_York'] + $this->defaultSettings;
    $display_repository->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format($this->displayOptions['settings']['date_format'], ['timezone' => 'America/New_York']);
    $expected .= ' - ' . $end_date->format($this->displayOptions['settings']['date_format'], ['timezone' => 'America/New_York']);
    $output = $this->renderTestEntity($id);
    $this->assertStringContainsString($expected, $output, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected.', ['%expected' => $expected]));

    // Test formatters when start date and end date are the same
    $this->drupalGet('entity_test/add');

    $value = '2012-12-31 00:00:00';
    $start_date = new DrupalDateTime($value, timezone_open(date_default_timezone_get()));
    $end_value = '2012-12-31 23:59:59';
    $end_date = new DrupalDateTime($end_value, timezone_open(date_default_timezone_get()));

    $date_format = DateFormat::load('html_date')->getPattern();
    $time_format = DateFormat::load('html_time')->getPattern();

    $edit = [
      "{$field_name}[0][value][date]" => $start_date->format($date_format),
      "{$field_name}[0][end_value][date]" => $start_date->format($date_format),
    ];
    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');

    $this->displayOptions = [
      'type' => 'daterange_default',
      'label' => 'hidden',
      'settings' => [
        'format_type' => 'long',
        'separator' => 'THESEPARATOR',
      ] + $this->defaultSettings,
    ];

    $display_repository->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();

    $start_expected = $this->dateFormatter->format($start_date->getTimestamp(), 'long');
    $start_expected_iso = $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
    $start_expected_markup = '<time datetime="' . $start_expected_iso . '">' . $start_expected . '</time>';
    $end_expected = $this->dateFormatter->format($end_date->getTimestamp(), 'long');
    $end_expected_iso = $this->dateFormatter->format($end_date->getTimestamp(), 'custom', 'Y-m-d\TH:i:s\Z', 'UTC');
    $end_expected_markup = '<time datetime="' . $end_expected_iso . '">' . $end_expected . '</time>';
    $output = $this->renderTestEntity($id);
    $this->assertStringContainsString($start_expected_markup, $output, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', ['%value' => 'long', '%expected' => $start_expected, '%expected_iso' => $start_expected_iso]));
    $this->assertStringContainsString($end_expected_markup, $output, new FormattableMarkup('Formatted date field using %value format displayed as %expected with %expected_iso attribute.', ['%value' => 'long', '%expected' => $end_expected, '%expected_iso' => $end_expected_iso]));
    $this->assertStringContainsString(' THESEPARATOR ', $output, 'Found proper separator');

    // Verify that hook_entity_prepare_view can add attributes.
    // @see entity_test_entity_prepare_view()
    $this->drupalGet('entity_test/' . $id);
    $this->assertSession()->elementExists('xpath', '//div[@data-field-item-attr="foobar"]');

    $this->displayOptions['type'] = 'daterange_plain';
    $this->container->get('entity_display.repository')
      ->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT) . ' THESEPARATOR ' . $end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $output = $this->renderTestEntity($id);
    $this->assertStringContainsString($expected, $output, new FormattableMarkup('Formatted date field using plain format displayed as %expected.', ['%expected' => $expected]));
    $this->assertStringContainsString(' THESEPARATOR ', $output, 'Found proper separator');

    $this->displayOptions['type'] = 'daterange_custom';
    $this->displayOptions['settings']['date_format'] = 'm/d/Y';
    $display_repository->getViewDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
    $expected = $start_date->format($this->displayOptions['settings']['date_format']) . ' THESEPARATOR ' . $end_date->format($this->displayOptions['settings']['date_format']);
    $output = $this->renderTestEntity($id);
    $this->assertStringContainsString($expected, $output, new FormattableMarkup('Formatted date field using daterange_custom format displayed as %expected.', ['%expected' => $expected]));
    $this->assertStringContainsString(' THESEPARATOR ', $output, 'Found proper separator');

  }

  /**
   * Tests Date Range List Widget functionality.
   */
  public function testDatelistWidget() {
    $field_name = $this->fieldStorage->getName();
    $field_label = $this->field->label();

    // Ensure field is set to a date only field.
    $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_DATE);
    $this->fieldStorage->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Change the widget to a datelist widget.
    $display_repository->getFormDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle())
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
    $this->assertSession()->elementTextContains('xpath', '//fieldset[@id="edit-' . $field_name . '-0"]/legend', $field_label);
    $this->assertSession()->elementExists('xpath', '//fieldset[@aria-describedby="edit-' . $field_name . '-0--description"]');
    $this->assertSession()->elementExists('xpath', '//div[@id="edit-' . $field_name . '-0--description"]');

    // Assert that Hour and Minute Elements do not appear on Date Only.
    $this->assertSession()->elementNotExists('xpath', "//*[@id=\"edit-$field_name-0-value-hour\"]");
    $this->assertSession()->elementNotExists('xpath', "//*[@id=\"edit-$field_name-0-value-minute\"]");
    $this->assertSession()->elementNotExists('xpath', "//*[@id=\"edit-$field_name-0-end-value-hour\"]");
    $this->assertSession()->elementNotExists('xpath', "//*[@id=\"edit-$field_name-0-end-value-minute\"]");

    // Go to the form display page to assert that increment option does not
    // appear on Date Only.
    $fieldEditUrl = 'entity_test/structure/entity_test/form-display';
    $this->drupalGet($fieldEditUrl);

    // Click on the widget settings button to open the widget settings form.
    $this->submitForm([], $field_name . "_settings_edit");
    $xpathIncr = "//select[starts-with(@id, \"edit-fields-$field_name-settings-edit-form-settings-increment\")]";
    $this->assertSession()->elementNotExists('xpath', $xpathIncr);

    // Change the field is set to an all day field.
    $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_ALLDAY);
    $this->fieldStorage->save();

    // Change the widget to a datelist widget.
    $display_repository->getFormDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle())
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
    $this->assertSession()->elementNotExists('xpath', "//*[@id=\"edit-$field_name-0-value-hour\"]");
    $this->assertSession()->elementNotExists('xpath', "//*[@id=\"edit-$field_name-0-value-minute\"]");
    $this->assertSession()->elementNotExists('xpath', "//*[@id=\"edit-$field_name-0-end-value-hour\"]");
    $this->assertSession()->elementNotExists('xpath', "//*[@id=\"edit-$field_name-0-end-value-minute\"]");

    // Go to the form display page to assert that increment option does not
    // appear on Date Only.
    $fieldEditUrl = 'entity_test/structure/entity_test/form-display';
    $this->drupalGet($fieldEditUrl);

    // Click on the widget settings button to open the widget settings form.
    $this->submitForm([], $field_name . "_settings_edit");
    $xpathIncr = "//select[starts-with(@id, \"edit-fields-$field_name-settings-edit-form-settings-increment\")]";
    $this->assertSession()->elementNotExists('xpath', $xpathIncr);

    // Change the field to a datetime field.
    $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_DATETIME);
    $this->fieldStorage->save();

    // Change the widget to a datelist widget.
    $display_repository->getFormDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle())
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
    $this->submitForm([], $field_name . "_settings_edit");
    $this->assertSession()->elementExists('xpath', $xpathIncr);

    // Display creation form.
    $this->drupalGet('entity_test/add');

    foreach (['value', 'end-value'] as $column) {
      foreach (['year', 'month', 'day', 'hour', 'minute', 'ampm'] as $element) {
        $this->assertSession()->elementExists('xpath', "//*[@id=\"edit-$field_name-0-$column-$element\"]");
        $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-$column-$element", '')->isSelected());
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

    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');

    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-value-year", '2012')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-value-month", '12')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-value-day", '31')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-value-hour", '5')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-value-minute", '15')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-value-ampm", 'am')->isSelected());

    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-end-value-year", '2013')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-end-value-month", '1')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-end-value-day", '15')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-end-value-hour", '3')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-end-value-minute", '30')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-end-value-ampm", 'pm')->isSelected());

    // Test the widget using increment other than 1 and 24 hour mode.
    $display_repository->getFormDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle())
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
    $this->assertSession()->elementExists('xpath', "//*[@id=\"edit-$field_name-0-value-hour\"]");
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-value-hour", '')->isSelected());
    $this->assertSession()->elementNotExists('xpath', "//*[@id=\"edit-$field_name-0-value-ampm\"]");
    $this->assertSession()->elementExists('xpath', "//*[@id=\"edit-$field_name-0-end-value-hour\"]");
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-end-value-hour", '')->isSelected());
    $this->assertSession()->elementNotExists('xpath', "//*[@id=\"edit-$field_name-0-end-value-ampm\"]");

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

    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');

    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-value-year", '2012')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-value-month", '12')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-value-day", '31')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-value-hour", '17')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-value-minute", '15')->isSelected());

    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-end-value-year", '2013')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-end-value-month", '1')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-end-value-day", '15')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-end-value-hour", '3')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-end-value-minute", '30')->isSelected());

    // Test the widget for partial completion of fields.
    $display_repository->getFormDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle())
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
      [$start_date_value, $end_date_value, $expected] = $data;

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

      $this->submitForm($edit, 'Save');
      $this->assertSession()->statusCodeEquals(200);
      foreach ($expected as $expected_text) {
        $this->assertSession()->pageTextContains($expected_text);
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

    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');

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

    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-value-minute", '0')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$field_name-0-end-value-minute", '0')->isSelected());
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
   * Tests default value functionality.
   */
  public function testDefaultValue() {
    // Create a test content type.
    $this->drupalCreateContentType(['type' => 'date_content']);

    // Create a field storage with settings to validate.
    $field_name = $this->randomMachineName();
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
      'set_default_value' => '1',
      'default_value_input[default_date_type]' => 'now',
      'default_value_input[default_end_date_type]' => 'now',
    ];
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->submitForm($field_edit, 'Save settings');

    // Check that default value is selected in default value form.
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->assertTrue($this->assertSession()->optionExists('edit-default-value-input-default-date-type', 'now')->isSelected());
    // Check that the relative start default value is empty.
    $this->assertSession()->fieldValueEquals('default_value_input[default_date]', '');
    $this->assertTrue($this->assertSession()->optionExists('edit-default-value-input-default-end-date-type', 'now')->isSelected());
    // Check that the relative end default value is empty.
    $this->assertSession()->fieldValueEquals('default_value_input[default_end_date]', '');

    // Check if default_date has been stored successfully.
    $config_entity = $this->config('field.field.node.date_content.' . $field_name)->get();
    $this->assertEquals(['default_date_type' => 'now', 'default_date' => 'now', 'default_end_date_type' => 'now', 'default_end_date' => 'now'], $config_entity['default_value'][0], 'Default value has been stored successfully');

    // Clear field cache in order to avoid stale cache values.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Create a new node to check that datetime field default value is today.
    $new_node = Node::create(['type' => 'date_content']);
    $expected_date = new DrupalDateTime('now', DateTimeItemInterface::STORAGE_TIMEZONE);
    $this->assertEquals($expected_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT), $new_node->get($field_name)->offsetGet(0)->value);
    $this->assertEquals($expected_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT), $new_node->get($field_name)->offsetGet(0)->end_value);

    // Set an invalid relative default_value to test validation.
    $field_edit = [
      'set_default_value' => '1',
      'default_value_input[default_date_type]' => 'relative',
      'default_value_input[default_date]' => 'invalid date',
      'default_value_input[default_end_date_type]' => 'relative',
      'default_value_input[default_end_date]' => '+1 day',
    ];
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->submitForm($field_edit, 'Save settings');
    $this->assertSession()->pageTextContains('The relative start date value entered is invalid.');

    $field_edit = [
      'set_default_value' => '1',
      'default_value_input[default_date_type]' => 'relative',
      'default_value_input[default_date]' => '+1 day',
      'default_value_input[default_end_date_type]' => 'relative',
      'default_value_input[default_end_date]' => 'invalid date',
    ];
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->submitForm($field_edit, 'Save settings');
    $this->assertSession()->pageTextContains('The relative end date value entered is invalid.');

    // Set a relative default_value.
    $field_edit = [
      'set_default_value' => '1',
      'default_value_input[default_date_type]' => 'relative',
      'default_value_input[default_date]' => '+45 days',
      'default_value_input[default_end_date_type]' => 'relative',
      'default_value_input[default_end_date]' => '+90 days',
    ];
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->submitForm($field_edit, 'Save settings');

    // Check that default value is selected in default value form.
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->assertTrue($this->assertSession()->optionExists('edit-default-value-input-default-date-type', 'relative')->isSelected());
    // Check that the relative start default value is displayed.
    $this->assertSession()->fieldValueEquals('default_value_input[default_date]', '+45 days');
    $this->assertTrue($this->assertSession()->optionExists('edit-default-value-input-default-end-date-type', 'relative')->isSelected());
    // Check that the relative end default value is displayed.
    $this->assertSession()->fieldValueEquals('default_value_input[default_end_date]', '+90 days');

    // Check if default_date has been stored successfully.
    $config_entity = $this->config('field.field.node.date_content.' . $field_name)->get();
    $this->assertEquals(['default_date_type' => 'relative', 'default_date' => '+45 days', 'default_end_date_type' => 'relative', 'default_end_date' => '+90 days'], $config_entity['default_value'][0], 'Default value has been stored successfully');

    // Clear field cache in order to avoid stale cache values.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Create a new node to check that datetime field default value is +90 days.
    $new_node = Node::create(['type' => 'date_content']);
    $expected_start_date = new DrupalDateTime('+45 days', DateTimeItemInterface::STORAGE_TIMEZONE);
    $expected_end_date = new DrupalDateTime('+90 days', DateTimeItemInterface::STORAGE_TIMEZONE);
    $this->assertEquals($expected_start_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT), $new_node->get($field_name)->offsetGet(0)->value);
    $this->assertEquals($expected_end_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT), $new_node->get($field_name)->offsetGet(0)->end_value);

    // Remove default value.
    $field_edit = [
      'set_default_value' => '',
      'default_value_input[default_date_type]' => '',
      'default_value_input[default_end_date_type]' => '',
    ];
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->submitForm($field_edit, 'Save settings');

    // Check that default value is selected in default value form.
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->assertTrue($this->assertSession()->optionExists('edit-default-value-input-default-date-type', '')->isSelected());
    // Check that the relative start default value is empty.
    $this->assertSession()->fieldValueEquals('default_value_input[default_date]', '');
    $this->assertTrue($this->assertSession()->optionExists('edit-default-value-input-default-end-date-type', '')->isSelected());
    // Check that the relative end default value is empty.
    $this->assertSession()->fieldValueEquals('default_value_input[default_end_date]', '');

    // Check if default_date has been stored successfully.
    $config_entity = $this->config('field.field.node.date_content.' . $field_name)->get();
    $this->assertEmpty($config_entity['default_value'], 'Empty default value has been stored successfully');

    // Clear field cache in order to avoid stale cache values.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Create a new node to check that datetime field default value is not set.
    $new_node = Node::create(['type' => 'date_content']);
    $this->assertNull($new_node->get($field_name)->value, 'Default value is not set');

    // Set now as default_value for start date only.
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', 'date_content')
      ->setComponent($field_name, [
        'type' => 'datetime_default',
      ])
      ->save();

    $expected_date = new DrupalDateTime('now', DateTimeItemInterface::STORAGE_TIMEZONE);

    $field_edit = [
      'set_default_value' => '1',
      'default_value_input[default_date_type]' => 'now',
      'default_value_input[default_end_date_type]' => '',
    ];
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->submitForm($field_edit, 'Save settings');

    // Make sure only the start value is populated on node add page.
    $this->drupalGet('node/add/date_content');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][value][date]", $expected_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT));
    $this->assertSession()->fieldValueEquals("{$field_name}[0][end_value][date]", '');

    // Set now as default_value for end date only.
    $field_edit = [
      'set_default_value' => '1',
      'default_value_input[default_date_type]' => '',
      'default_value_input[default_end_date_type]' => 'now',
    ];
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->submitForm($field_edit, 'Save settings');

    // Make sure only the start value is populated on node add page.
    $this->drupalGet('node/add/date_content');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][value][date]", '');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][end_value][date]", $expected_date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT));
  }

  /**
   * Tests that invalid values are caught and marked as invalid.
   */
  public function testInvalidField() {
    // Change the field to a datetime field.
    $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_DATETIME);
    $this->fieldStorage->save();
    $field_name = $this->fieldStorage->getName();
    $field_label = $this->field->label();

    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][value][date]", '');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][value][time]", '');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][end_value][date]", '');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][end_value][time]", '');

    // Submit invalid start dates and ensure they is not accepted.
    $date_value = '';
    $edit = [
      "{$field_name}[0][value][date]" => $date_value,
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('date is invalid');

    // Invalid year value.
    $date_value = 'aaaa-12-01';
    $edit = [
      "{$field_name}[0][value][date]" => $date_value,
      "{$field_name}[0][value][time]" => '00:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('date is invalid');

    // Invalid month value.
    $date_value = '2012-75-01';
    $edit = [
      "{$field_name}[0][value][date]" => $date_value,
      "{$field_name}[0][value][time]" => '00:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('date is invalid');

    // Invalid day value.
    $date_value = '2012-12-99';
    $edit = [
      "{$field_name}[0][value][date]" => $date_value,
      "{$field_name}[0][value][time]" => '00:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('date is invalid');

    // Submit invalid start times and ensure they is not accepted.
    $time_value = '';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => $time_value,
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('date is invalid');

    // Invalid hour value.
    $time_value = '49:00:00';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => $time_value,
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('date is invalid');

    // Invalid minutes value.
    $time_value = '12:99:00';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => $time_value,
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('date is invalid');

    // Invalid seconds value.
    $time_value = '12:15:99';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => $time_value,
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('date is invalid');

    // Submit invalid end dates and ensure they is not accepted.
    $date_value = '';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => $date_value,
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('date is invalid');

    // Invalid year value.
    $date_value = 'aaaa-12-01';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => $date_value,
      "{$field_name}[0][end_value][time]" => '00:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('date is invalid');

    // Invalid month value.
    $date_value = '2012-75-01';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => $date_value,
      "{$field_name}[0][end_value][time]" => '00:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('date is invalid');

    // Invalid day value.
    $date_value = '2012-12-99';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => $date_value,
      "{$field_name}[0][end_value][time]" => '00:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('date is invalid');

    // Submit invalid start times and ensure they is not accepted.
    $time_value = '';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => $time_value,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('date is invalid');

    // Invalid hour value.
    $time_value = '49:00:00';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => $time_value,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('date is invalid');

    // Invalid minutes value.
    $time_value = '12:99:00';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => $time_value,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('date is invalid');

    // Invalid seconds value.
    $time_value = '12:15:99';
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => $time_value,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('date is invalid');

    // End date before start date.
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => '2010-12-01',
      "{$field_name}[0][end_value][time]" => '12:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The ' . $field_label . ' end date cannot be before the start date');

    // End date before start date.
    $edit = [
      "{$field_name}[0][value][date]" => '2012-12-01',
      "{$field_name}[0][value][time]" => '12:00:00',
      "{$field_name}[0][end_value][date]" => '2012-12-01',
      "{$field_name}[0][end_value][time]" => '11:00:00',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The ' . $field_label . ' end date cannot be before the start date');
  }

  /**
   * Tests that 'Date' field storage setting form is disabled if field has data.
   */
  public function testDateStorageSettings() {
    // Create a test content type.
    $this->drupalCreateContentType(['type' => 'date_content']);

    // Create a field storage with settings to validate.
    $field_name = $this->randomMachineName();
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

    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', 'date_content')
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
    $this->drupalGet('node/add/date_content');
    $this->submitForm($edit, 'Save');
    $this->drupalGet('admin/structure/types/manage/date_content/fields/node.date_content.' . $field_name);
    $this->assertSession()->elementsCount('xpath', "//*[@name='field_storage[subform][settings][datetime_type]' and contains(@disabled, 'disabled')]", 1);
  }

}
