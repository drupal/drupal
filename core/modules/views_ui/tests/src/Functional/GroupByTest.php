<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests UI of aggregate functionality.
 */
#[Group('views_ui')]
#[RunTestsInSeparateProcesses]
class GroupByTest extends UITestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = [
    'test_aggregate_count',
    'test_views_group_by_save',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests whether basic saving works.
   *
   * @todo This should check the change of the settings as well.
   */
  public function testGroupBySave(): void {
    $this->drupalGet('admin/structure/views/view/test_views_group_by_save/edit');

    $edit_group_by_url = 'admin/structure/views/nojs/handler-group/test_views_group_by_save/default/field/id';
    $this->assertSession()->linkByHrefNotExists($edit_group_by_url, 0, 'No aggregation link found.');

    // Enable aggregation on the view.
    $edit = [
      'group_by' => TRUE,
    ];
    $this->drupalGet('admin/structure/views/nojs/display/test_views_group_by_save/default/group_by');
    $this->submitForm($edit, 'Apply');

    $this->assertSession()->linkByHrefExists($edit_group_by_url, 0, 'Aggregation link found.');

    // Change the group by type in the UI.
    $this->drupalGet($edit_group_by_url);
    $this->submitForm(['options[group_type]' => 'count'], 'Apply');
    $this->assertSession()->linkExists('COUNT(Views test: ID)', 0, 'The count setting is displayed in the UI');

    $this->submitForm([], 'Save');

    $view = $this->container->get('entity_type.manager')->getStorage('view')->load('test_views_group_by_save');
    $display = $view->getDisplay('default');
    $this->assertTrue($display['display_options']['group_by'], 'The group by setting was saved on the view.');
    $this->assertEquals('count', $display['display_options']['fields']['id']['group_type'], 'Count group_by_type was saved on the view.');
  }

  /**
   * Tests that the handler that builds the form also submits it.
   */
  public function testAggregationHandlerIsReusedOnSubmit(): void {
    $edit_handler_url = 'admin/structure/views/nojs/handler-group/test_aggregate_count/default/field/id';
    $this->drupalGet($edit_handler_url);
    // Without aggregation applied yet the entity field handler builds the
    // form, which includes the group column selects.
    $this->assertSession()->fieldValueEquals('options[group_type]', 'group');
    $this->assertSession()->fieldExists('options[group_column]');
    $this->assertSession()->fieldExists('options[group_columns][bundle]');
    $this->submitForm(['options[group_type]' => 'count'], 'Apply');
    $this->assertSession()->statusCodeEquals(200);

    // With aggregation applied the numeric handler builds the form, which
    // has no group column selects.
    $this->drupalGet($edit_handler_url);
    $this->assertSession()->fieldValueEquals('options[group_type]', 'count');
    $this->assertSession()->fieldNotExists('options[group_column]');
    $this->assertSession()->fieldNotExists('options[group_columns][bundle]');
    $this->submitForm(['options[group_type]' => 'count'], 'Apply');
    // Ensure that there is no error after submitting the form.
    $this->assertSession()->statusCodeEquals(200);

    // Switching back to "Group results together" restores the entity field
    // handler and its group column selects.
    $this->drupalGet($edit_handler_url);
    $this->assertSession()->fieldValueEquals('options[group_type]', 'count');
    $this->assertSession()->fieldNotExists('options[group_column]');
    $this->assertSession()->fieldNotExists('options[group_columns][bundle]');
    $this->submitForm(['options[group_type]' => 'group'], 'Apply');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($edit_handler_url);
    $this->assertSession()->fieldValueEquals('options[group_type]', 'group');
    $this->assertSession()->fieldExists('options[group_column]');
    $this->assertSession()->fieldExists('options[group_columns][bundle]');
  }

}
