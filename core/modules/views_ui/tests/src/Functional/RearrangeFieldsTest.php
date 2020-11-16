<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\views\Views;

/**
 * Tests the reordering of fields via AJAX.
 *
 * @group views_ui
 * @see \Drupal\views_ui\Form\Ajax\Rearrange
 */
class RearrangeFieldsTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Gets the fields from the View.
   */
  protected function getViewFields($view_name = 'test_view', $display_id = 'default') {
    $view = Views::getView($view_name);
    $view->setDisplay($display_id);
    $fields = [];
    foreach ($view->displayHandlers->get('default')->getHandlers('field') as $field => $handler) {
      $fields[] = $field;
    }
    return $fields;
  }

  /**
   * Check if the fields are in the correct order.
   *
   * @param $view_name
   *   The name of the view.
   * @param $fields
   *   Array of field names.
   */
  protected function assertFieldOrder($view_name, $fields) {
    $this->drupalGet('admin/structure/views/nojs/rearrange/' . $view_name . '/default/field');

    foreach ($fields as $idx => $field) {
      $this->assertSession()->fieldValueEquals('edit-fields-' . $field . '-weight', $idx + 1);
    }
  }

  /**
   * Tests field sorting.
   */
  public function testRearrangeFields() {
    $view_name = 'test_view';

    // Checks that the order on the rearrange form matches the creation order.
    $this->assertFieldOrder($view_name, $this->getViewFields($view_name));

    // Checks that a field is not deleted if a value is not passed back.
    $fields = [];
    $this->drupalPostForm('admin/structure/views/nojs/rearrange/' . $view_name . '/default/field', $fields, 'Apply');
    $this->assertFieldOrder($view_name, $this->getViewFields($view_name));

    // Checks that revers the new field order is respected.
    $reversedFields = array_reverse($this->getViewFields($view_name));
    $fields = [];
    foreach ($reversedFields as $delta => $field) {
      $fields['fields[' . $field . '][weight]'] = $delta;
    }
    $fields_count = count($fields);
    $this->drupalPostForm('admin/structure/views/nojs/rearrange/' . $view_name . '/default/field', $fields, 'Apply');
    $this->assertFieldOrder($view_name, $reversedFields);

    // Checks that there is a remove link for each field.
    $this->assertCount($fields_count, $this->cssSelect('a.views-remove-link'));
  }

}
