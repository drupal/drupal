<?php

namespace Drupal\Tests\views_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests views creation wizard.
 *
 * @see core/modules/views_ui/js/views-admin.js
 * @group views_ui
 */
class ViewsWizardTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'views', 'views_ui', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
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

    // Add a block display.
    $page->findField('block[create]')->click();
    $this->assertEquals($label_value, $page->findField('block[title]')->getValue());
  }

}
