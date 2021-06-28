<?php

namespace Drupal\Tests\views_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests views creation wizard.
 *
 * @see core/modules/views_ui/js/views-admin.js
 * @group views_ui
 */
class ViewsWizardTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views', 'views_ui', 'block', 'user'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer views',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests creating a View using the wizard.
   */
  public function testCreateViewWizard() {
    $this->drupalGet('admin/structure/views/add');
    $page = $this->getSession()->getPage();

    // Set a view name, this should be used to prepopulate a number of other
    // fields when creating displays.
    $label_value = 'test view';
    $search_input = $page->findField('label');
    $search_input->setValue($label_value);

    $page->findField('page[create]')->click();

    // Test if the title and path have been populated.
    $this->assertEquals($label_value, $page->findField('page[title]')->getValue());
    $this->assertEquals(str_replace(' ', '-', $label_value), $page->findField('page[path]')->getValue());

    // Create a menu item.
    $page->findField('page[link]')->click();
    $this->assertEquals($label_value, $page->findField('page[link_properties][title]')->getValue());

    // Wait for conditional field to show.
    $this->assertSession()->waitForElementVisible('named', ['select', 'page[link_properties][parent]']);

    // Assert a menu can be selected as a parent.
    $this->assertSession()->optionExists('page[link_properties][parent]', 'admin:');

    // Assert a parent menu item can be selected from within a menu.
    $this->assertSession()->optionExists('page[link_properties][parent]', 'admin:entity.view.collection');

    // Add a block display.
    $page->findField('block[create]')->click();
    $this->assertEquals($label_value, $page->findField('block[title]')->getValue());

    // Select the entity type to display and test that the type selector is
    // shown when expected.
    $page->selectFieldOption('show[wizard_key]', 'node');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertNull($page->findField('show[type]'), 'The "of type" filter is not added for nodes when there are no node types.');
    $this->assertEquals('teasers', $page->findField('page[style][row_plugin]')->getValue(), 'The page display format shows the expected default value.');
    $this->assertEquals('titles_linked', $page->findField('block[style][row_plugin]')->getValue(), 'The block display format shows the expected default value.');

    $page->selectFieldOption('show[wizard_key]', 'users');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertNull($page->findField('show[type]'), 'The "of type" filter is not added for users.');
    $this->assertEquals('fields', $page->findField('page[style][row_plugin]')->getValue(), 'The page display format was updated to a valid value.');
    $this->assertEquals('fields', $page->findField('block[style][row_plugin]')->getValue(), 'The block display format was updated to a valid value.');

    $this->drupalCreateContentType(['type' => 'page']);
    $page->selectFieldOption('show[wizard_key]', 'node');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertNotNull($page->findField('show[type]'), 'The "of type" filter is added for nodes when there is at least one node type.');
    $this->assertEquals('fields', $page->findField('page[style][row_plugin]')->getValue(), 'The page display format was not changed from a valid value.');
    $this->assertEquals('fields', $page->findField('block[style][row_plugin]')->getValue(), 'The block display format was not changed from a valid value.');
  }

}
