<?php

namespace Drupal\Tests\datetime\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests Datetime widgets functionality.
 *
 * @group datetime
 */
class DateTimeWidgetTest extends DateTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The default display settings to use for the formatters.
   *
   * @var array
   */
  protected $defaultSettings = ['timezone_override' => ''];

  /**
   * {@inheritdoc}
   */
  protected function getTestFieldType() {
    return 'datetime';
  }

  /**
   * Test default value functionality.
   */
  public function testDateonlyDefaultValue() {
    // Create a test content type.
    $this->drupalCreateContentType(['type' => 'dateonly_content']);

    // Create a field storage with settings to validate.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_dateonly',
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => ['datetime_type' => 'date'],
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'dateonly_content',
    ]);
    $field->save();

    $edit = [
      'fields[field_dateonly][region]' => 'content',
      'fields[field_dateonly][type]' => 'datetime_default',
    ];
    $this->drupalGet('admin/structure/types/manage/dateonly_content/form-display');
    $this->submitForm($edit, 'Save');
    $this->drupalGet('admin/structure/types/manage/dateonly_content/display');
    $this->submitForm($edit, 'Save');

    // Set now as default_value.
    $edit = [
      'set_default_value' => '1',
      'default_value_input[default_date_type]' => 'now',
    ];
    $this->drupalGet('admin/structure/types/manage/dateonly_content/fields/node.dateonly_content.field_dateonly');
    $this->submitForm($edit, 'Save settings');

    // Check that default value is selected in default value form.
    $this->drupalGet('admin/structure/types/manage/dateonly_content/fields/node.dateonly_content.field_dateonly');
    $option_field = $this->assertSession()->optionExists('edit-default-value-input-default-date-type', 'now');
    $this->assertTrue($option_field->hasAttribute('selected'));
    $this->assertSession()->fieldValueEquals('default_value_input[default_date]', '');

    // Loop through defined timezones to test that date-only defaults work at
    // the extremes.
    foreach (static::$timezones as $timezone) {
      $this->setSiteTimezone($timezone);
      $this->assertEquals($timezone, $this->config('system.date')->get('timezone.default'), 'Time zone set to ' . $timezone);

      // The time of the request is determined very early on in the request so
      // use the current time prior to making a request.
      $request_time = $this->container->get('datetime.time')->getCurrentTime();
      $this->drupalGet('node/add/dateonly_content');

      $today = $this->dateFormatter->format($request_time, 'html_date', NULL, $timezone);
      $this->assertSession()->fieldValueEquals('field_dateonly[0][value][date]', $today);

      $edit = [
        'title[0][value]' => $timezone,
      ];
      $this->submitForm($edit, 'Save');
      $this->assertSession()->pageTextContains('dateonly_content ' . $timezone . ' has been created');

      $node = $this->drupalGetNodeByTitle($timezone);
      $today_storage = $this->dateFormatter->format($request_time, 'html_date', NULL, $timezone);
      $this->assertEquals($today_storage, $node->field_dateonly->value);
    }
  }

}
