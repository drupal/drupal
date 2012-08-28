<?php

/**
 * @file
 * Definition of Drupal\views\Tests\UI\GroupByTest.
 */

namespace Drupal\views\Tests\UI;

/**
 * Tests UI of aggregate functionality..
 */
class GroupByTest extends UITestBase {

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

    $edit = array(
      'group_by' => TRUE,
    );
    $this->drupalPost('admin/structure/views/nojs/display/test_views_groupby_save/default/group_by', $edit, t('Apply'));

    $this->drupalGet('admin/structure/views/view/test_views_groupby_save/edit');
    $this->drupalPost('admin/structure/views/view/test_views_groupby_save/edit', array(), t('Save'));

    $this->drupalGet('admin/structure/views/nojs/display/test_views_groupby_save/default/group_by');
  }

}
