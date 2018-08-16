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
    $this->drupalGet('admin/structure/views/view/content_recent');
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Use the 'And/Or Rearrange' link for fields to open a dialog.
    $dropbutton = $page->find('css', '.views-ui-display-tab-bucket.filter .dropbutton-toggle button');
    $dropbutton->click();
    $add_link = $page->findById('views-rearrange-filter');
    $this->assertTrue($add_link->isVisible(), 'And/Or Rearrange button found.');
    $add_link->click();
    $assert_session->assertWaitOnAjaxRequest();

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

    // Checks that the admin summary is not double escaped.
    $this->drupalGet('admin/structure/views/view/who_s_online');
    $page = $this->getSession()->getPage();
    $this->assertNotNull($page->findLink('User: Last access (>= -15 minutes)'));
  }

}
