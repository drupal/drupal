<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Functional;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests the UI of generic display path plugin.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\display\PathPluginBase
 */
class DisplayPathTest extends UITestBase {

  use AssertPageCacheContextsAndTagsTrait;
  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->placeBlock('page_title_block');
  }

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view', 'test_page_display_menu'];

  /**
   * Runs the tests.
   */
  public function testPathUI(): void {
    $this->doBasicPathUITest();
    $this->doAdvancedPathsValidationTest();
    $this->doPathXssFilterTest();
  }

  /**
   * Tests basic functionality in configuring a view.
   */
  protected function doBasicPathUITest(): void {
    $this->drupalGet('admin/structure/views/view/test_view');

    // Add a new page display and check the appearing text.
    $this->submitForm([], 'Add Page');
    $this->assertSession()->pageTextContains('No path is set');
    $this->assertSession()->linkNotExists('View page', 'No view page link found on the page.');

    // Save a path and make sure the summary appears as expected.
    $random_path = $this->randomMachineName();
    // @todo Once https://www.drupal.org/node/2351379 is resolved, Views will no
    //   longer use Url::fromUri(), and this path will be able to contain ':'.
    $random_path = str_replace(':', '', $random_path);

    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_1/path');
    $this->submitForm(['path' => $random_path], 'Apply');
    $this->assertSession()->pageTextContains('/' . $random_path);
    $this->clickLink('View Page');
    $this->assertSession()->addressEquals($random_path);
  }

  /**
   * Tests that View paths are properly filtered for XSS.
   */
  public function doPathXssFilterTest(): void {
    $this->drupalGet('admin/structure/views/view/test_view');
    $this->submitForm([], 'Add Page');
    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_2/path');
    $this->submitForm(['path' => '<object>malformed_path</object>'], 'Apply');
    $this->submitForm([], 'Add Page');
    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_3/path');
    $this->submitForm(['path' => '<script>alert("hello");</script>'], 'Apply');
    $this->submitForm([], 'Add Page');
    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_4/path');
    $this->submitForm(['path' => '<script>alert("hello I have placeholders %");</script>'], 'Apply');
    $this->drupalGet('admin/structure/views/view/test_view');
    $this->submitForm([], 'Save');
    $this->drupalGet('admin/structure/views');
    // The anchor text should be escaped.
    $this->assertSession()->assertEscaped('/<object>malformed_path</object>');
    $this->assertSession()->assertEscaped('/<script>alert("hello");</script>');
    $this->assertSession()->assertEscaped('/<script>alert("hello I have placeholders %");</script>');
    // Links should be URL-encoded.
    $this->assertSession()->responseContains('/%3Cobject%3Emalformed_path%3C/object%3E');
    $this->assertSession()->responseContains('/%3Cscript%3Ealert%28%22hello%22%29%3B%3C/script%3E');
  }

  /**
   * Tests a couple of invalid path patterns.
   */
  protected function doAdvancedPathsValidationTest(): void {
    $url = 'admin/structure/views/nojs/display/test_view/page_1/path';

    $this->drupalGet($url);
    $this->submitForm(['path' => '%/foo'], 'Apply');
    $this->assertSession()->addressEquals($url);
    $this->assertSession()->pageTextContains('"%" may not be used for the first segment of a path.');

    $this->drupalGet($url);
    $this->submitForm(['path' => 'user/%1/example'], 'Apply');
    $this->assertSession()->addressEquals($url);
    $this->assertSession()->pageTextContains("Numeric placeholders may not be used. Use plain placeholders (%).");
  }

  /**
   * Tests deleting a page display that has no path.
   */
  public function testDeleteWithNoPath(): void {
    $this->drupalGet('admin/structure/views/view/test_view');
    $this->submitForm([], 'Add Page');
    $this->submitForm([], 'Delete Page');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains("The view Test view has been saved.");
  }

  /**
   * Tests the menu and tab option form.
   */
  public function testMenuOptions(): void {
    $this->drupalGet('admin/structure/views/view/test_view');

    // Add a new page display.
    $this->submitForm([], 'Add Page');

    // Add an invalid path (only fragment).
    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_1/path');
    $this->submitForm(['path' => '#foo'], 'Apply');
    $this->assertSession()->pageTextContains('Path is empty');

    // Add an invalid path with a query.
    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_1/path');
    $this->submitForm(['path' => 'foo?bar'], 'Apply');
    $this->assertSession()->pageTextContains('No query allowed.');

    // Add an invalid path with just a query.
    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_1/path');
    $this->submitForm(['path' => '?bar'], 'Apply');
    $this->assertSession()->pageTextContains('Path is empty');

    // Provide a random, valid path string.
    $random_string = $this->randomMachineName();

    // Save a path.
    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_1/path');
    $this->submitForm(['path' => $random_string], 'Apply');
    $this->drupalGet('admin/structure/views/view/test_view');

    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_1/menu');
    $this->submitForm([
      'menu[type]' => 'default tab',
      'menu[title]' => 'Test tab title',
    ], 'Apply');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/structure/views/nojs/display/test_view/page_1/tab_options');

    $this->submitForm(['tab_options[type]' => 'tab', 'tab_options[title]' => $this->randomString()], 'Apply');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/structure/views/view/test_view/edit/page_1');

    $this->drupalGet('admin/structure/views/view/test_view');
    $this->assertSession()->linkExists('Tab: Test tab title');
    // If it's a default tab, it should also have an additional settings link.
    $this->assertSession()->linkByHrefExists('admin/structure/views/nojs/display/test_view/page_1/tab_options');

    // Ensure that you can select a parent in case the parent does not exist.
    $this->drupalGet('admin/structure/views/nojs/display/test_page_display_menu/page_5/menu');
    $this->assertSession()->statusCodeEquals(200);
    $menu_options = $this->assertSession()->selectExists('edit-menu-parent')->findAll('css', 'option');
    $menu_options = array_map(function ($element) {
      return $element->getText();
    }, $menu_options);

    $this->assertEquals([
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

    // The cache contexts associated with the (in)accessible menu links are
    // bubbled.
    $this->assertCacheContext('user.permissions');
  }

  /**
   * Tests the regression in https://www.drupal.org/node/2532490.
   */
  public function testDefaultMenuTabRegression(): void {
    $this->container->get('module_installer')->install(['menu_link_content', 'toolbar', 'system']);
    $this->resetAll();
    $admin_user = $this->drupalCreateUser([
      'administer views',
      'administer blocks',
      'bypass node access',
      'access user profiles',
      'view all revisions',
      'administer permissions',
      'administer menu',
      'link to any page',
      'access toolbar',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);

    $edit = [
      'title[0][value]' => 'Menu title',
      'link[0][uri]' => '/admin/foo',
      'menu_parent' => 'admin:system.admin',
    ];
    $this->drupalGet('admin/structure/menu/manage/admin/add');
    $this->submitForm($edit, 'Save');

    $menu_items = \Drupal::entityTypeManager()->getStorage('menu_link_content')->getQuery()
      ->accessCheck(FALSE)
      ->sort('id', 'DESC')
      ->pager(1)
      ->execute();
    $menu_item = end($menu_items);
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link_content */
    $menu_link_content = MenuLinkContent::load($menu_item);

    $edit = [];
    $edit['label'] = $this->randomMachineName(16);
    $view_id = $edit['id'] = $this->randomMachineName(16);
    $edit['description'] = $this->randomMachineName(16);
    $edit['page[create]'] = TRUE;
    $edit['page[path]'] = 'admin/foo';

    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($edit, 'Save and edit');

    $parameters = new MenuTreeParameters();
    $parameters->addCondition('id', $menu_link_content->getPluginId());
    $result = \Drupal::menuTree()->load('admin', $parameters);
    $plugin_definition = end($result)->link->getPluginDefinition();
    $this->assertEquals('view.' . $view_id . '.page_1', $plugin_definition['route_name']);

    $this->clickLink('No menu');

    $this->submitForm([
      'menu[type]' => 'default tab',
      'menu[title]' => 'Menu title',
    ], 'Apply');

    $this->assertSession()->pageTextContains('Default tab options');

    $this->submitForm([
      'tab_options[type]' => 'normal',
      'tab_options[title]' => 'Parent title',
    ], 'Apply');

    // Open the menu options again.
    $this->clickLink('Tab: Menu title');

    // Assert a menu can be selected as a parent.
    $this->assertSession()->optionExists('menu[parent]', 'admin:');

    // Assert a parent menu item can be selected from within a menu.
    $this->assertSession()->optionExists('menu[parent]', 'admin:system.admin');

    // Check that parent menu item can now be
    // added without the menu_ui module being enabled.
    $this->submitForm([
      'menu[type]' => 'normal',
      'menu[parent]' => 'admin:system.admin',
      'menu[title]' => 'Menu title',
    ], 'Apply');

    $this->submitForm([], 'Save');
    // Assert that saving the view will not cause an exception.
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the "Use the administration theme" configuration.
   *
   * @see \Drupal\Tests\views\Functional\Plugin\DisplayPageWebTest::testAdminTheme
   */
  public function testUseAdminTheme(): void {
    $this->drupalGet('admin/structure/views/view/test_view');

    // Add a new page display.
    $this->submitForm([], 'Add Page');
    $this->assertSession()->pageTextContains('No path is set');
    $this->assertSession()->pageTextContains('Administration theme: No');

    // Test with a path starting with "/admin".
    $admin_path = 'admin/test_admin_path';
    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_1/path');
    $this->submitForm(['path' => $admin_path], 'Apply');
    $this->assertSession()->pageTextContains('/' . $admin_path);
    $this->assertSession()->pageTextContains('Administration theme: Yes (admin path)');
    $this->submitForm([], 'Save');

    $this->assertConfigSchemaByName('views.view.test_view');
    $display_options = $this->config('views.view.test_view')->get('display.page_1.display_options');
    $this->assertArrayNotHasKey('use_admin_theme', $display_options);

    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_1/use_admin_theme');
    $this->assertSession()->elementExists('css', 'input[name="use_admin_theme"][disabled="disabled"][checked="checked"]');

    // Test with a non-administration path.
    $non_admin_path = 'kittens';
    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_1/path');
    $this->submitForm(['path' => $non_admin_path], 'Apply');
    $this->assertSession()->pageTextContains('/' . $non_admin_path);
    $this->assertSession()->pageTextContains('Administration theme: No');
    $this->submitForm([], 'Save');

    $this->assertConfigSchemaByName('views.view.test_view');
    $display_options = $this->config('views.view.test_view')->get('display.page_1.display_options');
    $this->assertArrayNotHasKey('use_admin_theme', $display_options);

    // Enable administration theme.
    $this->drupalGet('admin/structure/views/nojs/display/test_view/page_1/use_admin_theme');
    $this->submitForm(['use_admin_theme' => TRUE], 'Apply');
    $this->assertSession()->pageTextContains('Administration theme: Yes');
    $this->submitForm([], 'Save');

    $this->assertConfigSchemaByName('views.view.test_view');
    $display_options = $this->config('views.view.test_view')->get('display.page_1.display_options');
    $this->assertArrayHasKey('use_admin_theme', $display_options);
    $this->assertTrue($display_options['use_admin_theme']);
  }

}
