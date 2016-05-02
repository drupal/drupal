<?php

namespace Drupal\Tests\views_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests the JavaScript library caching on consecutive requests.
 *
 * @group views_ui
 */
class LibraryCachingTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'views', 'views_ui'];

  /**
   * Tests if the Views UI dialogs open on consecutive requests.
   */
  public function testConsecutiveDialogRequests() {
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer views',
      'administer nodes',
      'access content overview',
    ]);

    // Disable automatic live preview to make the sequence of calls clearer.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.always_live_preview', FALSE)->save();
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/structure/views/view/content');
    $page = $this->getSession()->getPage();

    // Use the 'Add' link for fields to open a dialog. This will load the proper
    // dialog libraries.
    $add_link = $page->findById('views-add-field');
    $this->assertTrue($add_link->isVisible(), 'Add fields button found.');
    $add_link->click();
    $this->getSession()->wait(5000, "jQuery('.ui-dialog-titlebar').length > 0");
    // Close the dialog and open it again. No no libraries will be loaded, but a
    // cache entry will be made for not loading any libraries.
    $page->pressButton('Close');
    $add_link->click();
    $this->getSession()->wait(5000, "jQuery('.ui-dialog-titlebar').length > 0");
    $page->pressButton('Close');

    // Reload the page.
    $this->drupalGet('admin/structure/views/view/content');
    $page = $this->getSession()->getPage();

    // Now use the 'Update preview' button to load libraries.
    $preview = $page->findById('preview-submit');
    // The first click will load all the libraries.
    $preview->click();
    $this->getSession()->wait(5000, "jQuery('.ajax-progress').length === 0");
    // The second click will not load any new libraries.
    $preview->click();
    $this->getSession()->wait(5000, "jQuery('.ajax-progress').length === 0");
    // Check to see if the dialogs still open.
    $add_link = $page->findById('views-add-field');
    $add_link->click();
    $this->getSession()->wait(5000, "jQuery('.ui-dialog-titlebar').length > 0");
    $page->pressButton('Close');
  }

}
