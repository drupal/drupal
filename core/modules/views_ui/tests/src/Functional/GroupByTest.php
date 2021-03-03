<?php

namespace Drupal\Tests\views_ui\Functional;

/**
 * Tests UI of aggregate functionality..
 *
 * @group views_ui
 */
class GroupByTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_views_groupby_save'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests whether basic saving works.
   *
   * @todo This should check the change of the settings as well.
   */
  public function testGroupBySave() {
    $this->drupalGet('admin/structure/views/view/test_views_groupby_save/edit');

    $edit_groupby_url = 'admin/structure/views/nojs/handler-group/test_views_groupby_save/default/field/id';
    $this->assertSession()->linkByHrefNotExists($edit_groupby_url, 0, 'No aggregation link found.');

    // Enable aggregation on the view.
    $edit = [
      'group_by' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/views/nojs/display/test_views_groupby_save/default/group_by', $edit, 'Apply');

    $this->assertSession()->linkByHrefExists($edit_groupby_url, 0, 'Aggregation link found.');

    // Change the groupby type in the UI.
    $this->drupalPostForm($edit_groupby_url, ['options[group_type]' => 'count'], 'Apply');
    $this->assertSession()->linkExists('COUNT(Views test: ID)', 0, 'The count setting is displayed in the UI');

    $this->submitForm([], 'Save');

    $view = $this->container->get('entity_type.manager')->getStorage('view')->load('test_views_groupby_save');
    $display = $view->getDisplay('default');
    $this->assertTrue($display['display_options']['group_by'], 'The groupby setting was saved on the view.');
    $this->assertEqual('count', $display['display_options']['fields']['id']['group_type'], 'Count groupby_type was saved on the view.');
  }

}
