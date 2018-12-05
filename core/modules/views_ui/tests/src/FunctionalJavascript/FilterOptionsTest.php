<?php

namespace Drupal\Tests\views_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JavaScript filtering of options in add handler form.
 *
 * @group views_ui
 */
class FilterOptionsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'views',
    'views_ui',
    'views_ui_test_field',
    'toolbar',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Install additional themes Seven & Bartik.
    $this->container->get('theme_installer')->install(['seven', 'bartik']);

    $admin_user = $this->drupalCreateUser([
      'administer views',
      'access toolbar',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests filtering options in the 'Add fields' dialog.
   */
  public function testFilterOptionsAddFields() {
    $this->drupalGet('admin/structure/views/view/content');

    $session = $this->getSession();
    $web_assert = $this->assertSession();
    $page = $session->getPage();

    // Open the dialog.
    $page->clickLink('views-add-field');

    // Wait for the popup to open and the search field to be available.
    $options_search = $web_assert->waitForField('override[controls][options_search]');

    // Test that the both special fields are visible.
    $this->assertTrue($page->findField('name[views.views_test_field_1]')->isVisible());
    $this->assertTrue($page->findField('name[views.views_test_field_2]')->isVisible());

    // Test the ".title" field in search.
    $options_search->setValue('FIELD_1_TITLE');
    $page->waitFor(10, function () use ($page) {
      return !$page->findField('name[views.views_test_field_2]')->isVisible();
    });
    $this->assertTrue($page->findField('name[views.views_test_field_1]')->isVisible());
    $this->assertFalse($page->findField('name[views.views_test_field_2]')->isVisible());

    // Test the ".description" field in search.
    $options_search->setValue('FIELD_2_DESCRIPTION');
    $page->waitFor(10, function () use ($page) {
      return !$page->findField('name[views.views_test_field_1]')->isVisible();
    });
    $this->assertTrue($page->findField('name[views.views_test_field_2]')->isVisible());
    $this->assertFalse($page->findField('name[views.views_test_field_1]')->isVisible());

    // Test the "label" field not in search.
    $options_search->setValue('FIELD_1_LABEL');
    $page->waitFor(10, function () use ($page) {
      return !$page->findField('name[views.views_test_field_2]')->isVisible();
    });
    $this->assertFalse($page->findField('name[views.views_test_field_2]')->isVisible());
    $this->assertFalse($page->findField('name[views.views_test_field_1]')->isVisible());
  }

  /**
   * Test the integration of the dialog with the toolbar module.
   *
   * @dataProvider themeDataProvider
   */
  public function testDialogOverlayWithHorizontalToolbar($theme) {
    // Switch to the provided theme.
    $this->container->get('config.factory')->getEditable('system.theme')
      ->set('default', $theme)->save();
    $this->container->get('router.builder')->rebuildIfNeeded();

    $session = $this->getSession();

    // Set size for horizontal toolbar.
    $this->getSession()->resizeWindow(1200, 600);
    $this->drupalGet('admin/structure/views/view/content');

    $web_assert = $this->assertSession();
    $page = $session->getPage();

    $this->assertNotEmpty($web_assert->waitForElement('css', 'body.toolbar-horizontal'));
    $this->assertNotEmpty($web_assert->waitForElementVisible('css', '.toolbar-tray'));

    // Toggle the Toolbar in Horizontal mode to asserts the checkboxes are not
    // covered by the toolbar.
    $page->pressButton('Vertical orientation');

    // Open the dialog.
    $page->clickLink('views-add-field');

    // Wait for the popup to open and the search field to be available.
    $options_search = $web_assert->waitForField('override[controls][options_search]');

    $options_search->setValue('FIELD_1_TITLE');
    // Assert the element is clickable and on top of toolbar.
    $web_assert->waitForElement('css', 'input[name="name[views.views_test_field_1]"]')->click();
  }

  /**
   * Dataprovider that returns theme name as the sole argument.
   */
  public function themeDataProvider() {
    return [
      [
        'classy',
      ],
      [
        'seven',
      ],
      [
        'bartik',
      ],
    ];
  }

}
