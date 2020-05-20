<?php

namespace Drupal\Tests\views_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JavaScript filtering on the Views listing page.
 *
 * @see core/modules/views_ui/js/views_ui.listing.js
 * @group views_ui
 */
class ViewsListingTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views', 'views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer views',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the filtering on the Views listing page.
   */
  public function testFilterViewsListing() {
    $enabled_views_count = 6;
    $disabled_views_count = 2;
    $content_views_count = 3;

    $this->drupalGet('admin/structure/views');

    $session = $this->assertSession();

    $page = $this->getSession()->getPage();

    // Test that we search in both the enabled and disabled rows.
    $enabled_rows = $page->findAll('css', 'tr.views-ui-list-enabled');
    $enabled_rows = $this->filterVisibleElements($enabled_rows);
    $disabled_rows = $page->findAll('css', 'tr.views-ui-list-disabled');
    $disabled_rows = $this->filterVisibleElements($disabled_rows);

    // Test that we see some rows of views in both tables.
    $this->assertCount($enabled_views_count, $enabled_rows);
    $this->assertCount($disabled_views_count, $disabled_rows);

    // Filter on the string 'people'. This should only show the people view.
    $search_input = $page->find('css', '.views-filter-text.form-search');
    $search_input->setValue('people');

    $enabled_rows = $page->findAll('css', 'tr.views-ui-list-enabled');
    $enabled_rows = $this->filterVisibleElements($enabled_rows);
    $disabled_rows = $page->findAll('css', 'tr.views-ui-list-disabled');
    $disabled_rows = $this->filterVisibleElements($disabled_rows);

    $this->assertCount(1, $enabled_rows);
    $this->assertCount(0, $disabled_rows);

    // Filter on a string that also appears in the description.
    $search_input->setValue('content');

    $enabled_rows = $page->findAll('css', 'tr.views-ui-list-enabled');
    $enabled_rows = $this->filterVisibleElements($enabled_rows);
    $disabled_rows = $page->findAll('css', 'tr.views-ui-list-disabled');
    $disabled_rows = $this->filterVisibleElements($disabled_rows);

    $this->assertCount($content_views_count, $enabled_rows);
    $this->assertCount($disabled_views_count, $disabled_rows);

    // Reset the search string and check that we are back to the initial stage.
    $search_input->setValue('');
    // Add a backspace to trigger the keyUp event.
    $search_input->keyUp(8);

    $enabled_rows = $page->findAll('css', 'tr.views-ui-list-enabled');
    $enabled_rows = $this->filterVisibleElements($enabled_rows);
    $disabled_rows = $page->findAll('css', 'tr.views-ui-list-disabled');
    $disabled_rows = $this->filterVisibleElements($disabled_rows);

    $this->assertCount($enabled_views_count, $enabled_rows);
    $this->assertCount($disabled_views_count, $disabled_rows);

    // Disable a View and see if it moves to the disabled listing.
    $enabled_view = $page->find('css', 'tr.views-ui-list-enabled');
    $view_description = $enabled_view->find('css', '.views-ui-view-name h3')->getText();
    // Open the dropdown with additional actions.
    $enabled_view->find('css', 'li.dropbutton-toggle button')->click();
    $disable_button = $enabled_view->find('css', 'li.disable.dropbutton-action a');
    // Check that the disable button is visible now.
    $this->assertTrue($disable_button->isVisible());
    $disable_button->click();

    $session->assertWaitOnAjaxRequest();

    $enabled_rows = $page->findAll('css', 'tr.views-ui-list-enabled');
    $enabled_rows = $this->filterVisibleElements($enabled_rows);
    $disabled_rows = $page->findAll('css', 'tr.views-ui-list-disabled');
    $disabled_rows = $this->filterVisibleElements($disabled_rows);

    // Test that one enabled View has been moved to the disabled list.
    $this->assertCount($enabled_views_count - 1, $enabled_rows);
    $this->assertCount($disabled_views_count + 1, $disabled_rows);

    // Test that the keyboard focus is on the dropdown button of the View we
    // just disabled.
    $this->assertTrue($this->getSession()->evaluateScript("jQuery(document.activeElement).parent().is('li.enable.dropbutton-action')"));
    $this->assertEquals($view_description, $this->getSession()->evaluateScript("jQuery(document.activeElement).parents('tr').find('h3').text()"));

    // Enable the view again and ensure we have the focus on the edit button.
    $this->getSession()->evaluateScript('jQuery(document.activeElement).click()');
    $session->assertWaitOnAjaxRequest();

    $this->assertTrue($this->getSession()->evaluateScript("jQuery(document.activeElement).parent().is('li.edit.dropbutton-action')"));
    $this->assertEquals($view_description, $this->getSession()->evaluateScript("jQuery(document.activeElement).parents('tr').find('h3').text()"));
  }

  /**
   * Removes any non-visible elements from the passed array.
   *
   * @param array $elements
   *
   * @return array
   */
  protected function filterVisibleElements($elements) {
    $elements = array_filter($elements, function ($element) {
      return $element->isVisible();
    });
    return $elements;
  }

}
