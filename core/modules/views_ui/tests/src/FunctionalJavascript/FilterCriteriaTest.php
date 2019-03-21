<?php

namespace Drupal\Tests\views_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the View UI filter criteria group dialog.
 *
 * @group views_ui
 */
class FilterCriteriaTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'views', 'views_ui'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer views',
      'administer nodes',
      'access content overview',
    ]);

    // Disable automatic live preview to make the sequence of calls clearer.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.always_live_preview', FALSE)->save();
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests dialog for filter criteria.
   */
  public function testFilterCriteriaDialog() {
    // Checks that the admin summary is not double escaped.
    $this->drupalGet('admin/structure/views/view/who_s_online');
    $page = $this->getSession()->getPage();
    $this->assertNotNull($page->findLink('User: Last access (>= -15 minutes)'));

    $this->drupalGet('admin/structure/views/view/content_recent');
    $assert_session = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    $this->openFilterDialog();

    // Add a new filter group.
    $create_new_filter_group = $page->findById('views-add-group-link');
    $this->assertTrue($create_new_filter_group->isVisible(), 'Add group link found.');
    $create_new_filter_group->click();
    $assert_session->assertWaitOnAjaxRequest();

    // Assert the existence of the new filter group by checking the remove group
    // link.
    $remove_link = $page->findLink('Remove group');
    $this->assertTrue($remove_link->isVisible(), 'New group found.');

    // Remove the group again and assert the group is not present anymore.
    $remove_link->click();
    $assert_session->assertWaitOnAjaxRequest();
    $remove_link = $page->findLink('Remove group');
    $this->assertEmpty($remove_link, 'Remove button not available');

    // Add group again to test drag-n-drop.
    $create_new_filter_group = $page->findById('views-add-group-link');
    $this->assertTrue($create_new_filter_group->isVisible(), 'Add group link found.');
    $create_new_filter_group->click();
    $assert_session->assertWaitOnAjaxRequest();

    // Validate dragging behaviors.

    // First get relevant elements and the current values.
    $status_extra_row = $page->findById("views-row-status_extra");
    $langcode_row = $page->findById("views-row-langcode");

    $status_extra_group_field = $status_extra_row->findField('filters[status_extra][group]');
    $langcode_group_field = $langcode_row->findField('filters[langcode][group]');

    $status_extra_original_value = $status_extra_group_field->getValue();
    $langcode_original_value = $langcode_group_field->getValue();

    // Validate dragging the first filter works correctly but checking the
    // remove group link is not visible anymore.
    $drag_handle = $status_extra_row->find('css', '.tabledrag-handle');
    $target = $page->find('css', '.filter-group-operator-row');
    $drag_handle->dragTo($target);

    // Assert dragging a filter works.
    $remove_link = $page->findLink('Remove group');
    $this->assertFalse($remove_link->isVisible(), 'Remove group should be invisible after drag.');

    // Drag another filter to the end of the last filter group to verify the
    // group is set correctly.
    $drag_handle = $langcode_row->find('css', '.tabledrag-handle');
    $drag_handle->dragTo($status_extra_row);

    // Both rows must be in the same group.
    $this->assertNotEquals($status_extra_original_value, $status_extra_group_field->getValue(), 'Status extra group should be changed');
    $this->assertNotEquals($langcode_original_value, $langcode_group_field->getValue(), 'Langcode group should be changed');

    $this->assertSession()->waitForLink('Create new filter group', 20000);
    $create_new_filter_group = $page->findLink('Create new filter group');
    $this->assertTrue($create_new_filter_group->isVisible(), 'Add group link found.');
    $create_new_filter_group->click();
    $assert_session->assertWaitOnAjaxRequest();

    // Validate dragging works correctly and the new group will contain the new
    // filter.
    $dragged = $page->find('css', ".tabledrag-handle");
    $target = $page->find('css', '.filter-group-operator-row');
    $dragged->dragTo($target);

    $remove_link = $page->findLink('Remove group');
    $this->assertFalse($remove_link->isVisible(), 'Remove group should be invisible after drag.');
  }

  /**
   * Uses the 'And/Or Rearrange' link for filters to open a dialog.
   */
  protected function openFilterDialog() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $dropbutton = $page->find('css', '.views-ui-display-tab-bucket.filter .dropbutton-toggle button');
    $dropbutton->click();
    $add_link = $page->findById('views-rearrange-filter');
    $this->assertTrue($add_link->isVisible(), 'And/Or Rearrange button found.');
    $add_link->click();
    $assert_session->assertWaitOnAjaxRequest();
  }

}
