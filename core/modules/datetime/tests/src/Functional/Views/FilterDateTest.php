<?php

namespace Drupal\Tests\datetime\Functional\Views;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests Views filters for datetime fields.
 *
 * @group datetime
 */
class FilterDateTest extends ViewTestBase {

  /**
   * Name of the field.
   *
   * Note, this is used in the default test view.
   *
   * @var string
   */
  protected $fieldName = 'field_date';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Nodes to test.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes = [];

  /**
   * Dates of test nodes in date storage format.
   *
   * @var string[]
   */
  protected $dates;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime',
    'datetime_test',
    'node',
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_filter_datetime'];

  /**
   * {@inheritdoc}
   *
   * Create nodes with relative dates of yesterday, today, and tomorrow.
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $now = \Drupal::time()->getRequestTime();

    $admin_user = $this->drupalCreateUser(['administer views']);
    $this->drupalLogin($admin_user);

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Add a date field to page nodes.
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => ['datetime_type' => DateTimeItem::DATETIME_TYPE_DATETIME],
    ]);
    $fieldStorage->save();
    $field = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'page',
      'required' => TRUE,
    ]);
    $field->save();

    // Create some nodes.
    $this->dates = [
      // Tomorrow.
      DrupalDateTime::createFromTimestamp($now + 86400, DateTimeItemInterface::STORAGE_TIMEZONE)->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
      // Today.
      DrupalDateTime::createFromTimestamp($now, DateTimeItemInterface::STORAGE_TIMEZONE)->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
      // Yesterday.
      DrupalDateTime::createFromTimestamp($now - 86400, DateTimeItemInterface::STORAGE_TIMEZONE)->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
    ];

    $this->nodes = [];
    foreach ($this->dates as $date) {
      $this->nodes[] = $this->drupalCreateNode([
        $this->fieldName => [
          'value' => $date,
        ],
      ]);
    }
    // Add a node where the date field is empty.
    $this->nodes[] = $this->drupalCreateNode();

    // Views needs to be aware of the new field.
    $this->container->get('views.views_data')->clear();

    // Load test views.
    ViewTestData::createTestViews(static::class, ['datetime_test']);
  }

  /**
   * Tests exposed grouped filters.
   */
  public function testExposedGroupedFilters() {
    $filter_identifier = $this->fieldName . '_value';
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_datetime/default/filter/' . $filter_identifier);
    $this->submitForm([], 'Expose filter');
    $this->submitForm([], 'Grouped filters');

    // Create groups with different amount of expected values.
    $edit = [];
    // No values are required.
    $edit['options[group_info][group_items][1][title]'] = 'empty';
    $edit['options[group_info][group_items][1][operator]'] = 'empty';
    $edit['options[group_info][group_items][2][title]'] = 'not empty';
    $edit['options[group_info][group_items][2][operator]'] = 'not empty';

    // One value is required.
    $edit['options[group_info][group_items][3][title]'] = 'less than';
    $edit['options[group_info][group_items][3][operator]'] = '<';
    $edit['options[group_info][group_items][3][value][value]'] = $this->dates[0];

    // Two values are required (min and max).
    $this->submitForm($edit, 'Add another item');
    $edit['options[group_info][group_items][4][title]'] = 'between';
    $edit['options[group_info][group_items][4][operator]'] = 'between';
    $edit['options[group_info][group_items][4][value][type]'] = 'offset';
    $edit['options[group_info][group_items][4][value][min]'] = '-2 hours';
    $edit['options[group_info][group_items][4][value][max]'] = '+2 hours';
    $this->submitForm($edit, 'Apply');

    // Test that the exposed filter works as expected.
    $path = 'test_filter_datetime-path';
    $this->drupalGet('admin/structure/views/view/test_filter_datetime/edit');
    $this->submitForm([], 'Add Page');
    $this->drupalGet('admin/structure/views/nojs/display/test_filter_datetime/page_1/path');
    $this->submitForm(['path' => $path], 'Apply');
    $this->submitForm([], 'Save');

    $this->drupalGet($path);

    // Filter the Preview by 'empty'.
    $this->getSession()->getPage()->findField($filter_identifier)->selectOption('1');
    $this->getSession()->getPage()->pressButton('Apply');
    $this->assertIds([4]);

    // Filter the Preview by 'not empty'.
    $this->getSession()->getPage()->findField($filter_identifier)->selectOption('2');
    $this->getSession()->getPage()->pressButton('Apply');
    $this->assertIds([1, 2, 3]);

    // Filter the Preview by 'less than'.
    $this->getSession()->getPage()->findField($filter_identifier)->selectOption('3');
    $this->getSession()->getPage()->pressButton('Apply');
    $this->assertIds([2, 3]);

    // Filter the Preview by 'between'.
    $this->getSession()->getPage()->findField($filter_identifier)->selectOption('4');
    $this->getSession()->getPage()->pressButton('Apply');
    $this->assertIds([2]);

    // Change the identifier for grouped exposed filter.
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_datetime/default/filter/' . $filter_identifier);
    $filter_identifier = 'date';
    $edit['options[group_info][identifier]'] = $filter_identifier;
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');

    // Filter results again using a new filter identifier.
    $this->drupalGet($path);
    $this->getSession()->getPage()->findField($filter_identifier)->selectOption('2');
    $this->getSession()->getPage()->pressButton('Apply');
    $this->assertIds([1, 2, 3]);
  }

  /**
   * Ensures that a given list of items appear on the view result.
   *
   * @param array $expected_ids
   *   An array of IDs.
   */
  protected function assertIds(array $expected_ids = []): void {
    // First verify the count.
    $elements = $this->cssSelect('.views-row .field-content');
    $this->assertCount(count($expected_ids), $elements);

    $actual_ids = [];
    foreach ($elements as $element) {
      $actual_ids[] = (int) $element->getText();
    }
    $this->assertEquals($expected_ids, $actual_ids);
  }

  /**
   * Tests exposed date filters with a pager.
   */
  public function testExposedFilterWithPager() {
    // Expose the empty and not empty operators in a grouped filter.
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_datetime/default/filter/' . $this->fieldName . '_value');
    $this->submitForm([], t('Expose filter'));

    $edit = [];
    $edit['options[operator]'] = '>';

    $this->submitForm($edit, 'Apply');

    // Expose the view and set the pager to 2 items.
    $path = 'test_filter_datetime-path';
    $this->drupalGet('admin/structure/views/view/test_filter_datetime/edit');
    $this->submitForm([], 'Add Page');
    $this->drupalGet('admin/structure/views/nojs/display/test_filter_datetime/page_1/path');
    $this->submitForm(['path' => $path], 'Apply');
    $this->drupalGet('admin/structure/views/nojs/display/test_filter_datetime/default/pager_options');
    $this->submitForm(['pager_options[items_per_page]' => 2], 'Apply');
    $this->submitForm([], t('Save'));

    // Assert the page without filters.
    $this->drupalGet($path);
    $results = $this->cssSelect('.views-row');
    $this->assertCount(2, $results);
    $this->assertSession()->pageTextContains('Next');

    // Assert the page with filter in the future, one results without pager.
    $page = $this->getSession()->getPage();
    $now = \Drupal::time()->getRequestTime();
    $page->fillField($this->fieldName . '_value', DrupalDateTime::createFromTimestamp($now + 1)->format('Y-m-d H:i:s'));
    $page->pressButton('Apply');

    $results = $this->cssSelect('.views-row');
    $this->assertCount(1, $results);
    $this->assertSession()->pageTextNotContains('Next');

    // Assert the page with filter in the past, 3 results with pager.
    $page->fillField($this->fieldName . '_value', DrupalDateTime::createFromTimestamp($now - 1000000)->format('Y-m-d H:i:s'));
    $this->getSession()->getPage()->pressButton('Apply');
    $results = $this->cssSelect('.views-row');
    $this->assertCount(2, $results);
    $this->assertSession()->pageTextContains('Next');
    $page->clickLink('2');
    $results = $this->cssSelect('.views-row');
    $this->assertCount(1, $results);

  }

}
