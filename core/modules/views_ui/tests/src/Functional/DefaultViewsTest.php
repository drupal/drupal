<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\Core\Url;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests enabling, disabling, and reverting default views via the listing page.
 *
 * @group views_ui
 */
class DefaultViewsTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view_status', 'test_page_display_menu', 'test_page_display_arguments'];

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->placeBlock('page_title_block');
  }

  /**
   * Tests default views.
   */
  public function testDefaultViews() {
    // Make sure the view starts off as disabled (does not appear on the listing
    // page).
    $edit_href = 'admin/structure/views/view/glossary';
    $this->drupalGet('admin/structure/views');
    // @todo Disabled default views do now appear on the front page. Test this
    // behavior with templates instead.
    // $this->assertNoLinkByHref($edit_href);

    // Enable the view, and make sure it is now visible on the main listing
    // page.
    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink(t('Enable'), '/glossary/');
    $this->assertUrl('admin/structure/views');
    $this->assertLinkByHref($edit_href);

    // It should not be possible to revert the view yet.
    // @todo Figure out how to handle this with the new configuration system.
    // $this->assertNoLink(t('Revert'));
    // $revert_href = 'admin/structure/views/view/glossary/revert';
    // $this->assertNoLinkByHref($revert_href);

    // Edit the view and change the title. Make sure that the new title is
    // displayed.
    $new_title = $this->randomMachineName(16);
    $edit = ['title' => $new_title];
    $this->drupalPostForm('admin/structure/views/nojs/display/glossary/page_1/title', $edit, t('Apply'));
    $this->drupalPostForm('admin/structure/views/view/glossary/edit/page_1', [], t('Save'));
    $this->drupalGet('glossary');
    $this->assertResponse(200);
    $this->assertText($new_title);

    // Save another view in the UI.
    $this->drupalPostForm('admin/structure/views/nojs/display/archive/page_1/title', [], t('Apply'));
    $this->drupalPostForm('admin/structure/views/view/archive/edit/page_1', [], t('Save'));

    // Check there is an enable link. i.e. The view has not been enabled after
    // editing.
    $this->drupalGet('admin/structure/views');
    $this->assertLinkByHref('admin/structure/views/view/archive/enable');
    // Enable it again so it can be tested for access permissions.
    $this->clickViewsOperationLink(t('Enable'), '/archive/');

    // It should now be possible to revert the view. Do that, and make sure the
    // view title we added above no longer is displayed.
    // $this->drupalGet('admin/structure/views');
    // $this->assertLink(t('Revert'));
    // $this->assertLinkByHref($revert_href);
    // $this->drupalPostForm($revert_href, array(), t('Revert'));
    // $this->drupalGet('glossary');
    // $this->assertNoText($new_title);

    // Duplicate the view and check that the normal schema of duplicated views is used.
    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink(t('Duplicate'), '/glossary');
    $edit = [
      'id' => 'duplicate_of_glossary',
    ];
    $this->assertTitle(t('Duplicate of @label | @site-name', ['@label' => 'Glossary', '@site-name' => $this->config('system.site')->get('name')]));
    $this->drupalPostForm(NULL, $edit, t('Duplicate'));
    $this->assertUrl('admin/structure/views/view/duplicate_of_glossary', [], 'The normal duplicating name schema is applied.');

    // Duplicate a view and set a custom name.
    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink(t('Duplicate'), '/glossary');
    $random_name = strtolower($this->randomMachineName());
    $this->drupalPostForm(NULL, ['id' => $random_name], t('Duplicate'));
    $this->assertUrl("admin/structure/views/view/$random_name", [], 'The custom view name got saved.');

    // Now disable the view, and make sure it stops appearing on the main view
    // listing page but instead goes back to displaying on the disabled views
    // listing page.
    // @todo Test this behavior with templates instead.
    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink(t('Disable'), '/glossary/');
    // $this->assertUrl('admin/structure/views');
    // $this->assertNoLinkByHref($edit_href);
    // The easiest way to verify it appears on the disabled views listing page
    // is to try to click the "enable" link from there again.
    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink(t('Enable'), '/glossary/');
    $this->assertUrl('admin/structure/views');
    $this->assertLinkByHref($edit_href);

    // Clear permissions for anonymous users to check access for default views.
    Role::load(RoleInterface::ANONYMOUS_ID)->revokePermission('access content')->save();

    // Test the default views disclose no data by default.
    $this->drupalLogout();
    $this->drupalGet('glossary');
    $this->assertResponse(403);
    $this->drupalGet('archive');
    $this->assertResponse(403);

    // Test deleting a view.
    $this->drupalLogin($this->fullAdminUser);
    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink(t('Delete'), '/glossary/');
    // Submit the confirmation form.
    $this->drupalPostForm(NULL, [], t('Delete'));
    // Ensure the view is no longer listed.
    $this->assertUrl('admin/structure/views');
    $this->assertNoLinkByHref($edit_href);
    // Ensure the view is no longer available.
    $this->drupalGet($edit_href);
    $this->assertResponse(404);
    $this->assertText('Page not found');

    // Delete all duplicated Glossary views.
    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink(t('Delete'), 'duplicate_of_glossary');
    // Submit the confirmation form.
    $this->drupalPostForm(NULL, [], t('Delete'));

    $this->drupalGet('glossary');
    $this->assertResponse(200);

    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink(t('Delete'), $random_name);
    // Submit the confirmation form.
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->drupalGet('glossary');
    $this->assertResponse(404);
    $this->assertText('Page not found');
  }

  /**
   * Tests that enabling views moves them to the correct table.
   */
  public function testSplitListing() {
    // Build a re-usable xpath query.
    $xpath = '//div[@id="views-entity-list"]/div[@class = :status]/table//td/text()[contains(., :title)]';

    $arguments = [
      ':status' => 'views-list-section enabled',
      ':title' => 'test_view_status',
    ];

    $this->drupalGet('admin/structure/views');

    $elements = $this->xpath($xpath, $arguments);
    $this->assertIdentical(count($elements), 0, 'A disabled view is not found in the enabled views table.');

    $arguments[':status'] = 'views-list-section disabled';
    $elements = $this->xpath($xpath, $arguments);
    $this->assertIdentical(count($elements), 1, 'A disabled view is found in the disabled views table.');

    // Enable the view.
    $this->clickViewsOperationLink(t('Enable'), '/test_view_status/');

    $elements = $this->xpath($xpath, $arguments);
    $this->assertIdentical(count($elements), 0, 'After enabling a view, it is not found in the disabled views table.');

    $arguments[':status'] = 'views-list-section enabled';
    $elements = $this->xpath($xpath, $arguments);
    $this->assertIdentical(count($elements), 1, 'After enabling a view, it is found in the enabled views table.');

    // Attempt to disable the view by path directly, with no token.
    $this->drupalGet('admin/structure/views/view/test_view_status/disable');
    $this->assertResponse(403);
  }

  /**
   * Tests that page displays show the correct path.
   */
  public function testPathDestination() {
    $this->drupalGet('admin/structure/views');

    // Check that links to views on default tabs are rendered correctly.
    $this->assertLinkByHref('test_page_display_menu');
    $this->assertNoLinkByHref('test_page_display_menu/default');
    $this->assertLinkByHref('test_page_display_menu/local');

    // Check that a dynamic path is shown as text.
    $this->assertRaw('test_route_with_suffix/%/suffix');
    $this->assertNoLinkByHref(Url::fromUri('base:test_route_with_suffix/%/suffix')->toString());
  }

  /**
   * Click a link to perform an operation on a view.
   *
   * In general, we expect lots of links titled "enable" or "disable" on the
   * various views listing pages, and they might have tokens in them. So we
   * need special code to find the correct one to click.
   *
   * @param $label
   *   Text between the anchor tags of the desired link.
   * @param $unique_href_part
   *   A unique string that is expected to occur within the href of the desired
   *   link. For example, if the link URL is expected to look like
   *   "admin/structure/views/view/glossary/*", then "/glossary/" could be
   *   passed as the expected unique string.
   *
   * @return
   *   The page content that results from clicking on the link, or FALSE on
   *   failure. Failure also results in a failed assertion.
   */
  public function clickViewsOperationLink($label, $unique_href_part) {
    $links = $this->xpath('//a[normalize-space(text())=:label]', [':label' => (string) $label]);
    foreach ($links as $link_index => $link) {
      $position = strpos($link->getAttribute('href'), $unique_href_part);
      if ($position !== FALSE) {
        $index = $link_index;
        break;
      }
    }
    $this->assertTrue(isset($index), format_string('Link to "@label" containing @part found.', ['@label' => $label, '@part' => $unique_href_part]));
    if (isset($index)) {
      return $this->clickLink((string) $label, $index);
    }
    else {
      return FALSE;
    }
  }

}
