<?php

/**
 * @file
 * Definition of Drupal\views\Tests\UiGroupByTest.
 */

namespace Drupal\views\Tests;

/**
 * Tests UI of aggregate functionality..
 */
class UiGroupByTest extends ViewsSqlTest {

  protected $profile = 'standard';

  function setUp() {
    parent::setUp();

    // Create and log in a user with administer views permission.
    $views_admin = $this->drupalCreateUser(array('administer views', 'administer blocks', 'bypass node access', 'access user profiles', 'view revisions'));
    $this->drupalLogin($views_admin);
  }

  public static function getInfo() {
    return array(
      'name' => 'Groupby UI',
      'description' => 'Tests UI of aggregate functionality.',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests whether basic saving works.
   *
   * @todo: this should check the change of the settings as well.
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
