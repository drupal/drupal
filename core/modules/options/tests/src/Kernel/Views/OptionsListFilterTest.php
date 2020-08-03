<?php

namespace Drupal\Tests\options\Kernel\Views;

use Drupal\views\Views;

/**
 * Tests options list filter for views.
 *
 * @see \Drupal\field\Plugin\views\filter\ListField.
 * @group views
 */
class OptionsListFilterTest extends OptionsTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_options_list_filter'];

  /**
   * Tests options list field filter.
   */
  public function testViewsTestOptionsListFilter() {
    $view = Views::getView('test_options_list_filter');
    $this->executeView($view);

    $resultset = [
      ['nid' => $this->nodes[0]->nid->value],
      ['nid' => $this->nodes[1]->nid->value],
    ];

    $column_map = ['nid' => 'nid'];
    $this->assertIdenticalResultset($view, $resultset, $column_map);
  }

  /**
   * Tests options list field filter when grouped.
   */
  public function testViewsTestOptionsListGroupedFilter() {
    $view = Views::getView('test_options_list_filter');

    $filters = [
      'field_test_list_string_value' => [
        'id' => 'field_test_list_string_value',
        'table' => 'field_data_field_test_list_string',
        'field' => 'field_test_list_string_value',
        'relationship' => 'none',
        'group_type' => 'group',
        'admin_label' => '',
        'operator' => 'or',
        'value' => [
          'man' => 'man',
          'woman' => 'woman',
        ],
        'group' => '1',
        'exposed' => TRUE,
        'expose' => [
          'operator_id' => 'field_test_list_string_value_op',
          'label' => 'list-text',
          'description' => '',
          'identifier' => 'field_test_list_string_value',
        ],
        'is_grouped' => TRUE,
        'group_info' => [
          'label' => 'list-text (field_list_text)',
          'description' => '',
          'identifier' => 'field_test_list_string_value',
          'optional' => TRUE,
          'widget' => 'radios',
          'multiple' => TRUE,
          'remember' => FALSE,
          'default_group' => '1',
          'group_items' => [
            1 => [
              'title' => 'First',
              'operator' => 'or',
              'value' => [
                $this->fieldValues[0] => $this->fieldValues[0],
              ],
            ],
            2 => [
              'title' => 'Second',
              'operator' => 'or',
              'value' => [
                $this->fieldValues[1] => $this->fieldValues[1],
              ],
            ],
          ],
        ],
        'reduce_duplicates' => '',
        'plugin_id' => 'list_field',
      ],
    ];
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', $filters);

    $view->storage->save();

    $this->executeView($view);

    $resultset = [
      ['nid' => $this->nodes[0]->nid->value],
      ['nid' => $this->nodes[1]->nid->value],
    ];

    $column_map = ['nid' => 'nid'];
    $this->assertIdenticalResultset($view, $resultset, $column_map);
  }

}
