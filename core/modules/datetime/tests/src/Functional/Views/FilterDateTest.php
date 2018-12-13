<?php

namespace Drupal\Tests\datetime\Functional\Views;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests Views filters for datetime fields.
 *
 * @group datetime
 */
class FilterDateTest extends BrowserTestBase {

  /**
   * Name of the field.
   *
   * Note, this is used in the default test view.
   *
   * @var string
   */
  protected $fieldName = 'field_date';

  /**
   * Nodes to test.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes = [];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'datetime',
    'datetime_test',
    'node',
    'views',
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
  protected function setUp() {
    parent::setUp();

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
    $dates = [
      // Tomorrow.
      DrupalDateTime::createFromTimestamp($now + 86400, DateTimeItemInterface::STORAGE_TIMEZONE)->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
      // Today.
      DrupalDateTime::createFromTimestamp($now, DateTimeItemInterface::STORAGE_TIMEZONE)->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
      // Yesterday.
      DrupalDateTime::createFromTimestamp($now - 86400, DateTimeItemInterface::STORAGE_TIMEZONE)->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
    ];

    $this->nodes = [];
    foreach ($dates as $date) {
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
    ViewTestData::createTestViews(get_class($this), ['datetime_test']);
  }

  /**
   * Tests exposed grouped filters.
   */
  public function testExposedGroupedFilters() {
    // Expose the empty and not empty operators in a grouped filter.
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_filter_datetime/default/filter/' . $this->fieldName . '_value', [], t('Expose filter'));
    $this->drupalPostForm(NULL, [], 'Grouped filters');

    $edit = [];
    $edit['options[group_info][group_items][1][title]'] = 'empty';
    $edit['options[group_info][group_items][1][operator]'] = 'empty';
    $edit['options[group_info][group_items][2][title]'] = 'not empty';
    $edit['options[group_info][group_items][2][operator]'] = 'not empty';

    $this->drupalPostForm(NULL, $edit, 'Apply');

    // Test that the exposed filter works as expected.
    $path = 'test_filter_datetime-path';
    $this->drupalPostForm('admin/structure/views/view/test_filter_datetime/edit', [], 'Add Page');
    $this->drupalPostForm('admin/structure/views/nojs/display/test_filter_datetime/page_1/path', ['path' => $path], 'Apply');
    $this->drupalPostForm(NULL, [], t('Save'));

    $this->drupalGet($path);

    // Filter the Preview by 'empty'.
    $this->getSession()->getPage()->findField($this->fieldName . '_value')->selectOption(1);
    $this->getSession()->getPage()->pressButton('Apply');
    $results = $this->cssSelect('.view-content .field-content');
    $this->assertEquals(1, count($results));

    // Filter the Preview by 'not empty'.
    $this->getSession()->getPage()->findField($this->fieldName . '_value')->selectOption(2);
    $this->getSession()->getPage()->pressButton('Apply');
    $results = $this->cssSelect('.view-content .field-content');
    $this->assertEquals(3, count($results));
  }

}
