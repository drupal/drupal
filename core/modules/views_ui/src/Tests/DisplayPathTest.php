<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\DisplayPathTest.
 */

namespace Drupal\views_ui\Tests;

/**
 * Tests the UI of generic display path plugin.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\display\PathPluginBase
 */
class DisplayPathTest extends UITestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('menu_ui');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view', 'test_page_display_menu');

  /**
   * Runs the tests.
   */
  public function testPathUI() {
    $this->doBasicPathUITest();
    $this->doAdvancedPathsValidationTest();
  }

  /**
   * Tests basic functionality in configuring a view.
   */
  protected function doBasicPathUITest() {
    $this->drupalGet('admin/structure/views/view/test_view');

    // Add a new page display and check the appearing text.
    $this->drupalPostForm(NULL, array(), 'Add Page');
    $this->assertText(t('No path is set'), 'The right text appears if no path was set.');
    $this->assertNoLink(t('View @display', array('@display' => 'page')), 'No view page link found on the page.');

    // Save a path and make sure the summary appears as expected.
    $random_path = $this->randomMachineName();
    // @todo Once https://www.drupal.org/node/2351379 is resolved, Views will no
    //   longer use Url::fromUri(), and this path will be able to contain ':'.
    $random_path = str_replace(':', '', $random_path);

    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/page_1/path', array('path' => $random_path), t('Apply'));
    $this->assertText('/' . $random_path, 'The custom path appears in the summary.');
    $this->assertLink(t('View @display', array('@display' => 'Page')), 0, 'view page link found on the page.');
  }

  /**
   * Tests a couple of invalid path patterns.
   */
  protected function doAdvancedPathsValidationTest() {
    $url = 'admin/structure/views/nojs/display/test_view/page_1/path';

    $this->drupalPostForm($url, array('path' => '%/magrathea'), t('Apply'));
    $this->assertUrl($url);
    $this->assertText('"%" may not be used for the first segment of a path.');

    $this->drupalPostForm($url, array('path' => 'user/%1/example'), t('Apply'));
    $this->assertUrl($url);
    $this->assertText("Numeric placeholders may not be used. Please use plain placeholders (%).");
  }

  /**
   * Tests deleting a page display that has no path.
   */
  public function testDeleteWithNoPath() {
    $this->drupalGet('admin/structure/views/view/test_view');
    $this->drupalPostForm(NULL, array(), t('Add Page'));
    $this->drupalPostForm(NULL, array(), t('Delete Page'));
    $this->drupalPostForm(NULL, array(), t('Save'));
    $this->assertRaw(t('The view %view has been saved.', array('%view' => 'Test view')));
  }

  /**
   * Tests the menu and tab option form.
   */
  public function testMenuOptions() {
    $this->container->get('module_handler')->install(array('menu_ui'));
    $this->drupalGet('admin/structure/views/view/test_view');

    // Add a new page display.
    $this->drupalPostForm(NULL, array(), 'Add Page');

    // Add an invalid path (only fragment).
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/page_1/path', array('path' => '#foo'), t('Apply'));
    $this->assertText('Path is empty');

    // Add an invalid path with a query.
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/page_1/path', array('path' => 'foo?bar'), t('Apply'));
    $this->assertText('No query allowed.');

    // Add an invalid path with just a query.
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/page_1/path', array('path' => '?bar'), t('Apply'));
    $this->assertText('Path is empty');

    // Add an invalid path from a random test failure.
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/page_1/path', array('path' => 'AKI@&hO@'), t('Apply'));
    $this->assertText('Invalid path');

    // Provide a random, valid path string.
    $random_string = $this->randomMachineName();

    // Save a path.
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/page_1/path', array('path' => $random_string), t('Apply'));
    $this->drupalGet('admin/structure/views/view/test_view');

    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/page_1/menu', array('menu[type]' => 'default tab', 'menu[title]' => 'Test tab title'), t('Apply'));
    $this->assertResponse(200);
    $this->assertUrl('admin/structure/views/nojs/display/test_view/page_1/tab_options');

    $this->drupalPostForm(NULL, array('tab_options[type]' => 'tab', 'tab_options[title]' => $this->randomString()), t('Apply'));
    $this->assertResponse(200);
    $this->assertUrl('admin/structure/views/view/test_view/edit/page_1');

    $this->drupalGet('admin/structure/views/view/test_view');
    $this->assertLink(t('Tab: @title', array('@title' => 'Test tab title')));
    // If it's a default tab, it should also have an additional settings link.
    $this->assertLinkByHref('admin/structure/views/nojs/display/test_view/page_1/tab_options');

    // Ensure that you can select a parent in case the parent does not exist.
    $this->drupalGet('admin/structure/views/nojs/display/test_page_display_menu/page_5/menu');
    $this->assertResponse(200);
    $menu_parent = $this->xpath('//select[@id="edit-menu-parent"]');
    $menu_options = (array) $menu_parent[0]->option;
    unset($menu_options['@attributes']);

    $this->assertEqual([
      '<User account menu>',
      '-- My account',
      '-- Log out',
      '<Administration>',
      '<Footer>',
      '<Main navigation>',
      '<Tools>',
      '-- Compose tips (disabled)',
      '-- Test menu link',
    ], $menu_options);
  }

}
