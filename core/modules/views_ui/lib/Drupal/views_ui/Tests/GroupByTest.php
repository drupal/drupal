<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\GroupByTest.
 */

namespace Drupal\views_ui\Tests;

/**
 * Tests UI of aggregate functionality..
 */
class GroupByTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_views_groupby_save');

  public static function getInfo() {
    return array(
      'name' => 'Group By functionality',
      'description' => 'Tests UI of aggregate functionality.',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests whether basic saving works.
   *
   * @todo This should check the change of the settings as well.
   */
  function testGroupBySave() {
    $this->drupalGet('admin/structure/views/view/test_views_groupby_save/edit');

    $edit_groubpy_url = 'admin/structure/views/nojs/handler-group/test_views_groupby_save/default/field/id';
    $this->assertNoLinkByHref($edit_groubpy_url, 0, 'No aggregation link found.');

    // Enable aggregation on the view.
    $edit = array(
      'group_by' => TRUE,
    );
    $this->drupalPostForm('admin/structure/views/nojs/display/test_views_groupby_save/default/group_by', $edit, t('Apply'));

    $this->assertLinkByHref($edit_groubpy_url, 0, 'Aggregation link found.');

    // Change the groupby type in the UI.
    $this->drupalPostForm($edit_groubpy_url, array('options[group_type]' => 'count'), t('Apply'));
    $this->assertLink('COUNT(Views test: ID) (ID)', 0, 'The count setting is displayed in the UI');

    $this->drupalPostForm(NULL, array(), t('Save'));

    $view = $this->container->get('entity.manager')->getStorageController('view')->load('test_views_groupby_save');
    $display = $view->getDisplay('default');
    $this->assertTrue($display['display_options']['group_by'], 'The groupby setting was saved on the view.');
    $this->assertEqual($display['display_options']['fields']['id']['group_type'], 'count', 'Count groupby_type was saved on the view.');
  }

}
