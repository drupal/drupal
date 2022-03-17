<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;

/**
 * Tests the views page display plugin as webtest.
 *
 * @group views
 */
class DisplayPageWebTest extends ViewTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_page_display', 'test_page_display_arguments', 'test_page_display_menu', 'test_page_display_path'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests arguments.
   */
  public function testArguments() {
    $xpath = '//span[@class="field-content"]';

    // Ensure that all the entries are returned.
    $this->drupalGet('test_route_without_arguments');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementsCount('xpath', $xpath, 5);

    $this->drupalGet('test_route_without_arguments/1');
    $this->assertSession()->statusCodeEquals(404);

    // Ensure that just the filtered entry is returned.
    $this->drupalGet('test_route_with_argument/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCacheContexts(['languages:language_interface', 'route', 'theme', 'url']);
    $this->assertSession()->elementsCount('xpath', $xpath, 1);
    $this->assertSession()->elementTextEquals('xpath', $xpath, 1);

    // Ensure that just the filtered entry is returned.
    $this->drupalGet('test_route_with_suffix/1/suffix');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementsCount('xpath', $xpath, 1);
    $this->assertSession()->elementTextEquals('xpath', $xpath, 1);

    // Ensure that no result is returned.
    $this->drupalGet('test_route_with_suffix_and_argument/1/suffix/2');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementNotExists('xpath', $xpath);

    // Ensure that just the filtered entry is returned.
    $this->drupalGet('test_route_with_suffix_and_argument/1/suffix/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementsCount('xpath', $xpath, 1);
    $this->assertSession()->elementTextEquals('xpath', $xpath, 1);

    // Ensure that just the filtered entry is returned.
    $this->drupalGet('test_route_with_long_argument/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementsCount('xpath', $xpath, 1);
    $this->assertSession()->elementTextEquals('xpath', $xpath, 1);
  }

  /**
   * Tests menu settings of page displays.
   */
  public function testPageDisplayMenu() {
    // Check local tasks.
    $this->drupalGet('test_page_display_menu');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementTextEquals('xpath', "//ul[contains(@class, 'tabs primary')]//a[contains(@class, 'is-active')]/child::text()", 'Test default tab');
    $this->assertSession()->titleEquals('Test default page | Drupal');

    $this->drupalGet('test_page_display_menu/default');
    $this->assertSession()->statusCodeEquals(404);

    $this->drupalGet('test_page_display_menu/local');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementTextEquals('xpath', "//ul[contains(@class, 'tabs primary')]//a[contains(@class, 'is-active')]/child::text()", 'Test local tab');
    $this->assertSession()->titleEquals('Test local page | Drupal');

    // Check an ordinary menu link.
    $admin_user = $this->drupalCreateUser(['administer menu']);
    $this->drupalLogin($admin_user);
    $this->drupalPlaceBlock('system_menu_block:tools');
    $this->drupalGet('<front>');

    $menu_link = $this->cssSelect('nav.block-menu ul.menu a');
    $this->assertEquals('Test menu link', $menu_link[0]->getText());
    $this->container->get('module_installer')->install(['menu_ui', 'menu_link_content']);

    // Update the menu link.
    $this->drupalGet("admin/structure/menu/link/views_view:views.test_page_display_menu.page_3/edit");
    $this->submitForm(['title' => 'New title'], 'Save');

    $this->drupalGet('<front>');
    $menu_link = $this->cssSelect('nav.block-menu ul.menu a');
    $this->assertEquals('New title', $menu_link[0]->getText());
  }

  /**
   * Tests the title is not displayed in the output.
   */
  public function testTitleOutput() {
    $this->drupalGet('test_page_display_200');

    $view = Views::getView('test_page_display');
    $xpath = $this->cssSelect('div.view:contains("' . $view->getTitle() . '")');
    $this->assertEmpty($xpath, 'The view title was not displayed in the view markup.');
  }

  /**
   * Tests the views page path functionality.
   */
  public function testPagePaths() {
    $this->drupalLogin($this->rootUser);
    $this->assertPagePath('0');
    $this->assertPagePath('9999');
    $this->assertPagePath('☺');
  }

  /**
   * Tests that we can successfully change a view page display path.
   *
   * @param string $path
   *   Path that will be set as the view page display path.
   *
   * @internal
   */
  public function assertPagePath(string $path): void {
    $view = Views::getView('test_page_display_path');
    $view->initDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('path', $path);
    $view->save();
    $this->container->get('router.builder')->rebuild();
    // Check if we successfully changed the path.
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);
    // Check if we don't get any error on the view edit page.
    $this->drupalGet('admin/structure/views/view/test_page_display_path');
    $this->assertSession()->statusCodeEquals(200);
  }

}
