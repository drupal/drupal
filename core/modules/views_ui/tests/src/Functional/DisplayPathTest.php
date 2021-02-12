<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests the UI of generic display path plugin.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\display\PathPluginBase
 */
class DisplayPathTest extends UITestBase {

  use AssertPageCacheContextsAndTagsTrait;

  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->placeBlock('page_title_block');
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['menu_ui'];

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
  public function testPathUI() {
    $this->doBasicPathUITest();
    $this->doAdvancedPathsValidationTest();
    $this->doPathXssFilterTest();
  }

  /**
   * Tests basic functionality in configuring a view.
   */
  protected function doBasicPathUITest() {
    $this->drupalGet('admin/structure/views/view/test_view');

    // Add a new page display and check the appearing text.
    $this->submitForm([], 'Add Page');
    $this->assertText('No path is set');
    $this->assertSession()->linkNotExists('View page', 'No view page link found on the page.');

    // Save a path and make sure the summary appears as expected.
    $random_path = $this->randomMachineName();
    // @todo Once https://www.drupal.org/node/2351379 is resolved, Views will no
    //   longer use Url::fromUri(), and this path will be able to contain ':'.
    $random_path = str_replace(':', '', $random_path);

    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/page_1/path', ['path' => $random_path], 'Apply');
    $this->assertText('/' . $random_path);
    $display_link_text = t('View @display', ['@display' => 'Page']);
    $this->assertSession()->linkExists($display_link_text, 0, 'view page link found on the page.');
    $this->clickLink($display_link_text);
    $this->assertSession()->addressEquals($random_path);
  }

  /**
   * Tests that View paths are properly filtered for XSS.
   */
  public function doPathXssFilterTest() {
    $this->drupalGet('admin/structure/views/view/test_view');
    $this->submitForm([], 'Add Page');
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/page_2/path', ['path' => '<object>malformed_path</object>'], 'Apply');
    $this->submitForm([], 'Add Page');
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/page_3/path', ['path' => '<script>alert("hello");</script>'], 'Apply');
    $this->submitForm([], 'Add Page');
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/page_4/path', ['path' => '<script>alert("hello I have placeholders %");</script>'], 'Apply');
    $this->drupalPostForm('admin/structure/views/view/test_view', [], 'Save');
    $this->drupalGet('admin/structure/views');
    // The anchor text should be escaped.
    $this->assertSession()->assertEscaped('/<object>malformed_path</object>');
    $this->assertSession()->assertEscaped('/<script>alert("hello");</script>');
    $this->assertSession()->assertEscaped('/<script>alert("hello I have placeholders %");</script>');
    // Links should be url-encoded.
    $this->assertRaw('/%3Cobject%3Emalformed_path%3C/object%3E');
    $this->assertRaw('/%3Cscript%3Ealert%28%22hello%22%29%3B%3C/script%3E');
  }

  /**
   * Tests a couple of invalid path patterns.
   */
  protected function doAdvancedPathsValidationTest() {
    $url = 'admin/structure/views/nojs/display/test_view/page_1/path';

    $this->drupalPostForm($url, ['path' => '%/magrathea'], 'Apply');
    $this->assertSession()->addressEquals($url);
    $this->assertText('"%" may not be used for the first segment of a path.');

    $this->drupalPostForm($url, ['path' => 'user/%1/example'], 'Apply');
    $this->assertSession()->addressEquals($url);
    $this->assertText("Numeric placeholders may not be used. Please use plain placeholders (%).");
  }

  /**
   * Tests deleting a page display that has no path.
   */
  public function testDeleteWithNoPath() {
    $this->drupalGet('admin/structure/views/view/test_view');
    $this->submitForm([], 'Add Page');
    $this->submitForm([], 'Delete Page');
    $this->submitForm([], 'Save');
    $this->assertRaw(t('The view %view has been saved.', ['%view' => 'Test view']));
  }

  /**
   * Tests the menu and tab option form.
   */
  public function testMenuOptions() {
    $this->container->get('module_installer')->install(['menu_ui']);
    $this->drupalGet('admin/structure/views/view/test_view');

    // Add a new page display.
    $this->submitForm([], 'Add Page');

    // Add an invalid path (only fragment).
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/page_1/path', ['path' => '#foo'], 'Apply');
    $this->assertText('Path is empty');

    // Add an invalid path with a query.
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/page_1/path', ['path' => 'foo?bar'], 'Apply');
    $this->assertText('No query allowed.');

    // Add an invalid path with just a query.
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/page_1/path', ['path' => '?bar'], 'Apply');
    $this->assertText('Path is empty');

    // Provide a random, valid path string.
    $random_string = $this->randomMachineName();

    // Save a path.
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/page_1/path', ['path' => $random_string], 'Apply');
    $this->drupalGet('admin/structure/views/view/test_view');

    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/page_1/menu', ['menu[type]' => 'default tab', 'menu[title]' => 'Test tab title'], 'Apply');
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
  public function testDefaultMenuTabRegression() {
    $this->container->get('module_installer')->install(['menu_ui', 'menu_link_content', 'toolbar', 'system']);
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
    ]);
    $this->drupalLogin($admin_user);

    $edit = [
      'title[0][value]' => 'Menu title',
      'link[0][uri]' => '/admin/foo',
      'menu_parent' => 'admin:system.admin',
    ];
    $this->drupalPostForm('admin/structure/menu/manage/admin/add', $edit, 'Save');

    $menu_items = \Drupal::entityTypeManager()->getStorage('menu_link_content')->getQuery()
      ->sort('id', 'DESC')
      ->pager(1)
      ->execute();
    $menu_item = end($menu_items);
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link_content */
    $menu_link_content = MenuLinkContent::load($menu_item);

    $edit = [];
    $edit['label'] = $this->randomMachineName(16);
    $view_id = $edit['id'] = strtolower($this->randomMachineName(16));
    $edit['description'] = $this->randomMachineName(16);
    $edit['page[create]'] = TRUE;
    $edit['page[path]'] = 'admin/foo';

    $this->drupalPostForm('admin/structure/views/add', $edit, 'Save and edit');

    $parameters = new MenuTreeParameters();
    $parameters->addCondition('id', $menu_link_content->getPluginId());
    $result = \Drupal::menuTree()->load('admin', $parameters);
    $plugin_definition = end($result)->link->getPluginDefinition();
    $this->assertEqual('view.' . $view_id . '.page_1', $plugin_definition['route_name']);

    $this->clickLink(t('No menu'));

    $this->submitForm([
      'menu[type]' => 'default tab',
      'menu[title]' => 'Menu title',
    ], 'Apply');

    $this->assertText('Default tab options');

    $this->submitForm([
      'tab_options[type]' => 'normal',
      'tab_options[title]' => 'Parent title',
    ], 'Apply');

    $this->submitForm([], 'Save');
    // Assert that saving the view will not cause an exception.
    $this->assertSession()->statusCodeEquals(200);
  }

}
