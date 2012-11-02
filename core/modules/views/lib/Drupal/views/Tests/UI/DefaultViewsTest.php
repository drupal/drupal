<?php

/**
 * @file
 * Definition of Drupal\views\Tests\UI\DefaultViewsTest.
 */

namespace Drupal\views\Tests\UI;

/**
 * Tests enabling, disabling, and reverting default views via the listing page.
 */
class DefaultViewsTest extends UITestBase {

  public static function getInfo() {
    return array(
      'name' => 'Default views functionality',
      'description' => 'Test enabling, disabling, and reverting default views via the listing page.',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests default views.
   */
  function testDefaultViews() {
    // Make sure the front page view starts off as disabled (does not appear on
    // the listing page).
    $edit_href = 'admin/structure/views/view/frontpage/edit';
    $this->drupalGet('admin/structure/views');
    // @todo Disabled default views do now appear on the front page. Test this
    // behavior with templates instead.
    // $this->assertNoLinkByHref($edit_href);

    // Enable the front page view, and make sure it is now visible on the main
    // listing page.
    $this->drupalGet('admin/structure/views/templates');
    $this->clickViewsOperationLink(t('Enable'), '/frontpage/');
    $this->assertUrl('admin/structure/views');
    $this->assertLinkByHref($edit_href);

    // It should not be possible to revert the view yet.
    // @todo Figure out how to handle this with the new configuration system.
    // $this->assertNoLink(t('Revert'));
    // $revert_href = 'admin/structure/views/view/frontpage/revert';
    // $this->assertNoLinkByHref($revert_href);

    // Edit the view and change the title. Make sure that the new title is
    // displayed.
    $new_title = $this->randomName(16);
    $edit = array('title' => $new_title);
    $this->drupalPost('admin/structure/views/nojs/display/frontpage/page_1/title', $edit, t('Apply'));
    $this->drupalPost('admin/structure/views/view/frontpage/edit/page_1', array(), t('Save'));
    $this->drupalGet('frontpage');
    $this->assertResponse(200);
    $this->assertText($new_title);

    // Save another view in the UI.
    $this->drupalPost('admin/structure/views/nojs/display/archive/page_1/title', array(), t('Apply'));
    $this->drupalPost('admin/structure/views/view/archive/page_1', array(), t('Save'));

    // Check there is an enable link. i.e. The view has not been enabled after
    // editing.
    $this->drupalGet('admin/structure/views');
    $this->assertLinkByHref('admin/structure/views/view/archive/enable');

    // It should now be possible to revert the view. Do that, and make sure the
    // view title we added above no longer is displayed.
    // $this->drupalGet('admin/structure/views');
    // $this->assertLink(t('Revert'));
    // $this->assertLinkByHref($revert_href);
    // $this->drupalPost($revert_href, array(), t('Revert'));
    // $this->drupalGet('frontpage');
    // $this->assertNoText($new_title);

    // Now disable the view, and make sure it stops appearing on the main view
    // listing page but instead goes back to displaying on the disabled views
    // listing page.
    // @todo Test this behavior with templates instead.
    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink(t('Disable'), '/frontpage/');
    // $this->assertUrl('admin/structure/views');
    // $this->assertNoLinkByHref($edit_href);
    // The easiest way to verify it appears on the disabled views listing page
    // is to try to click the "enable" link from there again.
    $this->drupalGet('admin/structure/views/templates');
    $this->clickViewsOperationLink(t('Enable'), '/frontpage/');
    $this->assertUrl('admin/structure/views');
    $this->assertLinkByHref($edit_href);

    // Test deleting a view.
    $this->drupalGet('admin/structure/views');
    $this->clickViewsOperationLink(t('Delete'), '/frontpage/');
    // Submit the confirmation form.
    $this->drupalPost(NULL, array(), t('Delete'));
    // Ensure the view is no longer listed.
    $this->assertUrl('admin/structure/views');
    $this->assertNoLinkByHref($edit_href);
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
   *   "admin/structure/views/view/frontpage/...", then "/frontpage/" could be
   *   passed as the expected unique string.
   *
   * @return
   *   The page content that results from clicking on the link, or FALSE on
   *   failure. Failure also results in a failed assertion.
   */
  function clickViewsOperationLink($label, $unique_href_part) {
    $links = $this->xpath('//a[normalize-space(text())=:label]', array(':label' => $label));
    foreach ($links as $link_index => $link) {
      $position = strpos($link['href'], $unique_href_part);
      if ($position !== FALSE) {
        $index = $link_index;
        break;
      }
    }
    $this->assertTrue(isset($index), t('Link to "@label" containing @part found.', array('@label' => $label, '@part' => $unique_href_part)));
    if (isset($index)) {
      return $this->clickLink($label, $index);
    }
    else {
      return FALSE;
    }
  }

}
