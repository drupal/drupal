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
class DateTimeFieldDatelistWidgetTest extends DateTestBase {

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
   * Tests the datelist widget with a date-only field.
   */
  public function testDatelistDateonlyWidget() {
    // Create a field with settings to validate.
    $this->createField(
      'datetime', ['datetime_type' => DateTimeItem::DATETIME_TYPE_DATE],
      'datetime_datelist', ['date_order' => 'YMD'],
      'datetime_default', []
    );

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->elementTextContains('xpath', '//fieldset[@id="edit-' . $this->field_name . '-0"]/legend', $this->field_label);
    $this->assertSession()->elementExists('xpath', '//fieldset[@aria-describedby="edit-' . $this->field_name . '-0--description"]');
    $this->assertSession()->elementExists('xpath', '//div[@id="edit-' . $this->field_name . '-0--description"]');

    // Assert that Hour and Minute Elements do not appear on Date Only
    $this->assertSession()->elementNotExists('xpath', "//*[@id=\"edit-$this->field_name-0-value-hour\"]");
    $this->assertSession()->elementNotExists('xpath', "//*[@id=\"edit-$this->field_name-0-value-minute\"]");

    // Go to the form display page to assert that increment option does not appear on Date Only
    $fieldEditUrl = 'entity_test/structure/entity_test/form-display';
    $this->drupalGet($fieldEditUrl);

    // Click on the widget settings button to open the widget settings form.
    $this->submitForm([], $this->field_name . "_settings_edit");
    $xpathIncr = "//select[starts-with(@id, \"edit-fields-$this->field_name-settings-edit-form-settings-increment\")]";
    $this->assertSession()->elementNotExists('xpath', $xpathIncr);
    $this->fail();
  }

  /**
   * Tests the datelist widget with a datetime field.
   */
  public function testDatelistDatetimeWidget() {
    $display_repository = \Drupal::service('entity_display.repository');

    // Create a field with settings to validate.
    $this->createField(
      'datetime', ['datetime_type' => DateTimeItem::DATETIME_TYPE_DATETIME],
      'datetime_datelist', [
        'increment' => 1,
        'date_order' => 'YMD',
        'time_type' => '12',
      ],
      'datetime_default', []
    );

    // Go to the form display page to assert that increment option does appear on Date Time
    $fieldEditUrl = 'entity_test/structure/entity_test/form-display';
    $this->drupalGet($fieldEditUrl);

    // Click on the widget settings button to open the widget settings form.
    $this->submitForm([], $this->field_name . "_settings_edit");
    $xpathIncr = "//select[starts-with(@id, \"edit-fields-$this->field_name-settings-edit-form-settings-increment\")]";
    $this->assertSession()->elementExists('xpath', $xpathIncr);

    // Display creation form.
    $this->drupalGet('entity_test/add');

    // Year element.
    $this->assertSession()->elementExists('xpath', "//*[@id=\"edit-$this->field_name-0-value-year\"]");
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-year", '')->isSelected());
    $this->assertSession()->optionExists("edit-$this->field_name-0-value-year", 'Year');
    // Month element.
    $this->assertSession()->elementExists('xpath', "//*[@id=\"edit-$this->field_name-0-value-month\"]");
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-month", '')->isSelected());
    $this->assertSession()->optionExists("edit-$this->field_name-0-value-month", 'Month');
    // Day element.
    $this->assertSession()->elementExists('xpath', "//*[@id=\"edit-$this->field_name-0-value-day\"]");
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-day", '')->isSelected());
    $this->assertSession()->optionExists("edit-$this->field_name-0-value-day", 'Day');
    // Hour element.
    $this->assertSession()->elementExists('xpath', "//*[@id=\"edit-$this->field_name-0-value-hour\"]");
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-hour", '')->isSelected());
    $this->assertSession()->optionExists("edit-$this->field_name-0-value-hour", 'Hour');
    // Minute element.
    $this->assertSession()->elementExists('xpath', "//*[@id=\"edit-$this->field_name-0-value-minute\"]");
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-minute", '')->isSelected());
    $this->assertSession()->optionExists("edit-$this->field_name-0-value-minute", 'Minute');
    // No Second element.
    $this->assertSession()->elementNotExists('xpath', "//*[@id=\"edit-$this->field_name-0-value-second\"]");
    // AMPM element.
    $this->assertSession()->elementExists('xpath', "//*[@id=\"edit-$this->field_name-0-value-ampm\"]");
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-ampm", '')->isSelected());
    $this->assertSession()->optionExists("edit-$this->field_name-0-value-ampm", 'AM/PM');

    // Submit a valid date and ensure it is accepted.
    $date_value = ['year' => 2012, 'month' => 12, 'day' => 31, 'hour' => 5, 'minute' => 15];

    $edit = [];
    // Add the ampm indicator since we are testing 12 hour time.
    $date_value['ampm'] = 'am';
    foreach ($date_value as $part => $value) {
      $edit["{$this->field_name}[0][value][$part]"] = $value;
    }

    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText('entity_test ' . $id . ' has been created.');

    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-year", '2012')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-month", '12')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-day", '31')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-hour", '5')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-minute", '15')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-ampm", 'am')->isSelected());

    // Test the widget using increment other than 1 and 24 hour mode.
    $display_repository->getFormDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle())
      ->setComponent($this->field_name, [
        'type' => 'datetime_datelist',
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
    $this->assertSession()->elementExists('xpath', "//*[@id=\"edit-$this->field_name-0-value-hour\"]");
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-hour", '')->isSelected());
    $this->assertSession()->elementNotExists('xpath', "//*[@id=\"edit-$this->field_name-0-value-ampm\"]");
    // Submit a valid date and ensure it is accepted.
    $date_value = ['year' => 2012, 'month' => 12, 'day' => 31, 'hour' => 17, 'minute' => 15];

    $edit = [];
    foreach ($date_value as $part => $value) {
      $edit["{$this->field_name}[0][value][$part]"] = $value;
    }

    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText('entity_test ' . $id . ' has been created.');

    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-year", '2012')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-month", '12')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-day", '31')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-hour", '17')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-minute", '15')->isSelected());

    // Test the widget for partial completion of fields.
    $display_repository->getFormDisplay($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle())
      ->setComponent($this->field_name, [
        'type' => 'datetime_datelist',
        'settings' => [
          'increment' => 1,
          'date_order' => 'YMD',
          'time_type' => '24',
        ],
      ])
      ->save();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Test the widget for validation notifications.
    foreach ($this->datelistDataProvider($this->field_label) as $data) {
      [$date_value, $expected] = $data;

      // Display creation form.
      $this->drupalGet('entity_test/add');

      // Submit a partial date and ensure and error message is provided.
      $edit = [];
      foreach ($date_value as $part => $value) {
        $edit["{$this->field_name}[0][value][$part]"] = $value;
      }

      $this->submitForm($edit, 'Save');
      $this->assertSession()->statusCodeEquals(200);
      foreach ($expected as $expected_text) {
        $this->assertText($expected_text);
      }
    }

    // Test the widget for complete input with zeros as part of selections.
    $this->drupalGet('entity_test/add');

    $date_value = ['year' => 2012, 'month' => '12', 'day' => '31', 'hour' => '0', 'minute' => '0'];
    $edit = [];
    foreach ($date_value as $part => $value) {
      $edit["{$this->field_name}[0][value][$part]"] = $value;
    }

    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText('entity_test ' . $id . ' has been created.');

    // Test the widget to ensure zeros are not deselected on validation.
    $this->drupalGet('entity_test/add');

    $date_value = ['year' => 2012, 'month' => '12', 'day' => '31', 'hour' => '', 'minute' => '0'];
    $edit = [];
    foreach ($date_value as $part => $value) {
      $edit["{$this->field_name}[0][value][$part]"] = $value;
    }

    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($this->assertSession()->optionExists("edit-$this->field_name-0-value-minute", '0')->isSelected());
  }

  /**
   * The data provider for testing the validation of the datelist widget.
   *
   * @param string $field_label
   *   The label of the field being tested.
   *
   * @return array
   *   An array of datelist input permutations to test.
   */
  protected function datelistDataProvider($field_label) {
    return [
      // Nothing selected.
      [
        ['year' => '', 'month' => '', 'day' => '', 'hour' => '', 'minute' => ''],
        ["The $field_label date is required."],
      ],
      // Year only selected, validation error on Month, Day, Hour, Minute.
      [
        ['year' => 2012, 'month' => '', 'day' => '', 'hour' => '', 'minute' => ''],
        [
          "The $field_label date is incomplete.",
          'A value must be selected for month.',
          'A value must be selected for day.',
          'A value must be selected for hour.',
          'A value must be selected for minute.',
        ],
      ],
      // Year and Month selected, validation error on Day, Hour, Minute.
      [
        ['year' => 2012, 'month' => '12', 'day' => '', 'hour' => '', 'minute' => ''],
        [
          "The $field_label date is incomplete.",
          'A value must be selected for day.',
          'A value must be selected for hour.',
          'A value must be selected for minute.',
        ],
      ],
      // Year, Month and Day selected, validation error on Hour, Minute.
      [
        ['year' => 2012, 'month' => '12', 'day' => '31', 'hour' => '', 'minute' => ''],
        [
          "The $field_label date is incomplete.",
          'A value must be selected for hour.',
          'A value must be selected for minute.',
        ],
      ],
      // Year, Month, Day and Hour selected, validation error on Minute only.
      [
        ['year' => 2012, 'month' => '12', 'day' => '31', 'hour' => '0', 'minute' => ''],
        [
          "The $field_label date is incomplete.",
          'A value must be selected for minute.',
        ],
      ],
    ];
  }

}
