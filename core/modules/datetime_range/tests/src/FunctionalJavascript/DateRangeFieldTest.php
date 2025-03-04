<?php

declare(strict_types=1);

namespace Drupal\Tests\datetime_range\FunctionalJavascript;

use Drupal\datetime_range\DateTimeRangeDisplayOptions;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Daterange field.
 *
 * @group datetime
 */
class DateRangeFieldTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'entity_test', 'field_ui', 'datetime', 'datetime_range'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
      'administer content types',
      'administer node fields',
      'administer node display',
      'bypass node access',
      'administer entity_test fields',
    ]));
  }

  /**
   * Tests the conditional visibility of the 'Date separator' field.
   */
  public function testFromToSeparatorState(): void {
    $field_name = $this->randomMachineName();
    $this->drupalCreateContentType(['type' => 'date_content']);
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
    \Drupal::service('entity_display.repository')->getViewDisplay('node', 'date_content')
      ->setComponent($field_name, [
        'type' => 'daterange_default',
        'label' => 'hidden',
        'settings' => [
          'format_type' => 'short',
          'separator' => 'THE_SEPARATOR',
        ],
      ])
      ->save();
    $this->drupalGet("admin/structure/types/manage/date_content/display");

    $page = $this->getSession()->getPage();
    $page->pressButton("{$field_name}_settings_edit");
    $this->assertSession()->waitForElement('css', '.ajax-new-content');

    $from_to_locator = 'fields[' . $field_name . '][settings_edit_form][settings][from_to]';
    $separator = $page->findField('Date separator');

    // Assert that date separator field is visible if 'from_to' is set to
    // BOTH.
    $this->assertSession()->fieldValueEquals($from_to_locator, DateTimeRangeDisplayOptions::Both->value);
    $this->assertTrue($separator->isVisible());
    // Assert that the date separator is not visible if 'from_to' is set to
    // START_DATE or END_DATE.
    $page->selectFieldOption($from_to_locator, DateTimeRangeDisplayOptions::StartDate->value);
    $this->assertFalse($separator->isVisible());
    $page->selectFieldOption($from_to_locator, DateTimeRangeDisplayOptions::EndDate->value);
    $this->assertFalse($separator->isVisible());
  }

}
