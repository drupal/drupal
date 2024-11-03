<?php

declare(strict_types=1);

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

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->placeBlock('page_title_block');
  }

  /**
   * Tests default views.
   */
  public function testDefaultViews(): void {
    // Make sure the view starts off as disabled (does not appear on the listing
    // page).
    $edit_href = 'admin/structure/views/view/glossary';
    $this->drupalGet('admin/structure/views');
    // @todo Disabled default views do now appear on the front page. Test this
    // behavior with templates instead.
    // $this->assertSession()->linkByHrefNotExists($edit_href);

    // Enable the view, and make sure it is now visible on the main listing
    // page.
    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink('Enable', '/glossary/');
    $this->assertSession()->addressEquals('admin/structure/views');
    $this->assertSession()->linkByHrefExists($edit_href);

    // It should not be possible to revert the view yet.
    // @todo Figure out how to handle this with the new configuration system.
    // $this->assertSession()->linkNotExists('Revert');
    // $revert_href = 'admin/structure/views/view/glossary/revert';
    // $this->assertSession()->linkByHrefNotExists($revert_href);

    // Edit the view and change the title. Make sure that the new title is
    // displayed.
    $new_title = $this->randomMachineName(16);
    $edit = ['title' => $new_title];
    $this->drupalGet('admin/structure/views/nojs/display/glossary/page_1/title');
    $this->submitForm($edit, 'Apply');
    $this->drupalGet('admin/structure/views/view/glossary/edit/page_1');
    $this->submitForm([], 'Save');
    $this->drupalGet('glossary');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($new_title);

    // Save another view in the UI.
    $this->drupalGet('admin/structure/views/nojs/display/archive/page_1/title');
    $this->submitForm([], 'Apply');
    $this->drupalGet('admin/structure/views/view/archive/edit/page_1');
    $this->submitForm([], 'Save');

    // Check there is an enable link. i.e. The view has not been enabled after
    // editing.
    $this->drupalGet('admin/structure/views');
    $this->assertSession()->linkByHrefExists('admin/structure/views/view/archive/enable');
    // Enable it again so it can be tested for access permissions.
    $this->clickViewsOperationLink('Enable', '/archive/');

    // It should now be possible to revert the view. Do that, and make sure the
    // view title we added above no longer is displayed.
    // $this->drupalGet('admin/structure/views');
    // $this->assertSession()->linkExists('Revert');
    // $this->assertSession()->linkByHrefExists($revert_href);
    // $this->drupalGet($revert_href);
    // $this->submitForm(array(), 'Revert');
    // $this->drupalGet('glossary');
    // $this->assertSession()->pageTextNotContains($new_title);

    // Duplicate the view and check that the normal schema of duplicated views is used.
    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink('Duplicate', '/glossary');
    $edit = [
      'id' => 'duplicate_of_glossary',
    ];
    $this->assertSession()->titleEquals('Duplicate of Glossary | Drupal');
    $this->submitForm($edit, 'Duplicate');
    $this->assertSession()->addressEquals('admin/structure/views/view/duplicate_of_glossary');

    // Duplicate a view and set a custom name.
    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink('Duplicate', '/glossary');
    $random_name = $this->randomMachineName();
    $this->submitForm(['id' => $random_name], 'Duplicate');
    $this->assertSession()->addressEquals("admin/structure/views/view/$random_name");

    // Now disable the view, and make sure it stops appearing on the main view
    // listing page but instead goes back to displaying on the disabled views
    // listing page.
    // @todo Test this behavior with templates instead.
    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink('Disable', '/glossary/');
    // $this->assertSession()->addressEquals('admin/structure/views');
    // $this->assertSession()->linkByHrefNotExists($edit_href);
    // The easiest way to verify it appears on the disabled views listing page
    // is to try to click the "enable" link from there again.
    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink('Enable', '/glossary/');
    $this->assertSession()->addressEquals('admin/structure/views');
    $this->assertSession()->linkByHrefExists($edit_href);

    // Clear permissions for anonymous users to check access for default views.
    Role::load(RoleInterface::ANONYMOUS_ID)->revokePermission('access content')->save();

    // Test the default views disclose no data by default.
    $this->drupalLogout();
    $this->drupalGet('glossary');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('archive');
    $this->assertSession()->statusCodeEquals(403);

    // Test deleting a view.
    $this->drupalLogin($this->fullAdminUser);
    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink('Delete', '/glossary/');
    // Submit the confirmation form.
    $this->submitForm([], 'Delete');
    // Ensure the view is no longer listed.
    $this->assertSession()->addressEquals('admin/structure/views');
    $this->assertSession()->linkByHrefNotExists($edit_href);
    // Ensure the view is no longer available.
    $this->drupalGet($edit_href);
    $this->assertSession()->statusCodeEquals(404);
    $this->assertSession()->pageTextContains('Page not found');

    // Delete all duplicated Glossary views.
    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink('Delete', 'duplicate_of_glossary');
    // Submit the confirmation form.
    $this->submitForm([], 'Delete');

    $this->drupalGet('glossary');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink('Delete', $random_name);
    // Submit the confirmation form.
    $this->submitForm([], 'Delete');
    $this->drupalGet('glossary');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertSession()->pageTextContains('Page not found');
  }

  /**
   * Tests that enabling views moves them to the correct table.
   */
  public function testSplitListing(): void {
    $this->drupalGet('admin/structure/views');
    $this->assertSession()->elementNotExists('xpath', '//div[@id="views-entity-list"]/div[@class = "views-list-section enabled"]/table//td/text()[contains(., "test_view_status")]');
    $this->assertSession()->elementsCount('xpath', '//div[@id="views-entity-list"]/div[@class = "views-list-section disabled"]/table//td/text()[contains(., "test_view_status")]', 1);

    // Enable the view.
    $this->clickViewsOperationLink('Enable', '/test_view_status/');
    $this->assertSession()->elementNotExists('xpath', '//div[@id="views-entity-list"]/div[@class = "views-list-section disabled"]/table//td/text()[contains(., "test_view_status")]');
    $this->assertSession()->elementsCount('xpath', '//div[@id="views-entity-list"]/div[@class = "views-list-section enabled"]/table//td/text()[contains(., "test_view_status")]', 1);

    // Attempt to disable the view by path directly, with no token.
    $this->drupalGet('admin/structure/views/view/test_view_status/disable');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that page displays show the correct path.
   */
  public function testPathDestination(): void {
    $this->drupalGet('admin/structure/views');

    // Check that links to views on default tabs are rendered correctly.
    $this->assertSession()->linkByHrefExists('test_page_display_menu');
    $this->assertSession()->linkByHrefNotExists('test_page_display_menu/default');
    $this->assertSession()->linkByHrefExists('test_page_display_menu/local');

    // Check that a dynamic path is shown as text.
    $this->assertSession()->responseContains('test_route_with_suffix/%/suffix');
    $this->assertSession()->linkByHrefNotExists(Url::fromUri('base:test_route_with_suffix/%/suffix')->toString());
  }

  /**
   * Click a link to perform an operation on a view.
   *
   * In general, we expect lots of links titled "enable" or "disable" on the
   * various views listing pages, and they might have tokens in them. So we
   * need special code to find the correct one to click.
   *
   * @param string $label
   *   Text between the anchor tags of the desired link.
   * @param string $unique_href_part
   *   A unique string that is expected to occur within the href of the desired
   *   link. For example, if the link URL is expected to look like
   *   "admin/structure/views/view/glossary/*", then "/glossary/" could be
   *   passed as the expected unique string.
   */
  public function clickViewsOperationLink($label, $unique_href_part): void {
    $this->assertSession()->elementExists('xpath', "//a[normalize-space(text())='$label' and contains(@href, '$unique_href_part')]")->click();
  }

}
