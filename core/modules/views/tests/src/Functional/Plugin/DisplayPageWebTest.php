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
  protected static $modules = ['menu_ui', 'block', 'views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->enableViewsTestModule();
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests arguments.
   */
  public function testArguments() {
    $this->drupalGet('test_route_without_arguments');
    $this->assertSession()->statusCodeEquals(200);
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertCount(5, $result, 'All entries was returned');

    $this->drupalGet('test_route_without_arguments/1');
    $this->assertSession()->statusCodeEquals(404);

    $this->drupalGet('test_route_with_argument/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCacheContexts(['languages:language_interface', 'route', 'theme', 'url']);
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertCount(1, $result, 'Ensure that just the filtered entry was returned.');
    $this->assertEqual(1, $result[0]->getText(), 'The passed ID was returned.');

    $this->drupalGet('test_route_with_suffix/1/suffix');
    $this->assertSession()->statusCodeEquals(200);
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertCount(1, $result, 'Ensure that just the filtered entry was returned.');
    $this->assertEqual(1, $result[0]->getText(), 'The passed ID was returned.');

    $this->drupalGet('test_route_with_suffix_and_argument/1/suffix/2');
    $this->assertSession()->statusCodeEquals(200);
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertCount(0, $result, 'No result was returned.');

    $this->drupalGet('test_route_with_suffix_and_argument/1/suffix/1');
    $this->assertSession()->statusCodeEquals(200);
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertCount(1, $result, 'Ensure that just the filtered entry was returned.');
    $this->assertEqual(1, $result[0]->getText(), 'The passed ID was returned.');

    $this->drupalGet('test_route_with_long_argument/1');
    $this->assertSession()->statusCodeEquals(200);
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertCount(1, $result, 'Ensure that just the filtered entry was returned.');
    $this->assertEqual(1, $result[0]->getText(), 'The passed ID was returned.');
  }

  /**
   * Tests menu settings of page displays.
   */
  public function testPageDisplayMenu() {
    // Check local tasks.
    $this->drupalGet('test_page_display_menu');
    $this->assertSession()->statusCodeEquals(200);
    $element = $this->xpath('//ul[contains(@class, :ul_class)]//a[contains(@class, :a_class)]/child::text()', [
      ':ul_class' => 'tabs primary',
      ':a_class' => 'is-active',
    ]);
    $this->assertEqual(t('Test default tab'), $element[0]->getText());
    $this->assertSession()->titleEquals('Test default page | Drupal');

    $this->drupalGet('test_page_display_menu/default');
    $this->assertSession()->statusCodeEquals(404);

    $this->drupalGet('test_page_display_menu/local');
    $this->assertSession()->statusCodeEquals(200);
    $element = $this->xpath('//ul[contains(@class, :ul_class)]//a[contains(@class, :a_class)]/child::text()', [
      ':ul_class' => 'tabs primary',
      ':a_class' => 'is-active',
    ]);
    $this->assertEqual(t('Test local tab'), $element[0]->getText());
    $this->assertSession()->titleEquals('Test local page | Drupal');

    // Check an ordinary menu link.
    $admin_user = $this->drupalCreateUser(['administer menu']);
    $this->drupalLogin($admin_user);
    $this->drupalPlaceBlock('system_menu_block:tools');
    $this->drupalGet('<front>');

    $menu_link = $this->cssSelect('nav.block-menu ul.menu a');
    $this->assertEqual('Test menu link', $menu_link[0]->getText());

    // Update the menu link.
    $this->drupalPostForm("admin/structure/menu/link/views_view:views.test_page_display_menu.page_3/edit", [
      'title' => 'New title',
    ], 'Save');

    $this->drupalGet('<front>');
    $menu_link = $this->cssSelect('nav.block-menu ul.menu a');
    $this->assertEqual('New title', $menu_link[0]->getText());
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
  }

  /**
   * Tests that we can successfully change a view page display path.
   *
   * @param string $path
   *   Path that will be set as the view page display path.
   *
   * @return bool
   *   Assertion result.
   */
  public function assertPagePath($path) {
    $view = Views::getView('test_page_display_path');
    $view->initDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('path', $path);
    $view->save();
    $this->container->get('router.builder')->rebuild();
    // Check if we successfully changed the path.
    $this->drupalGet($path);
    $success = $this->assertSession()->statusCodeEquals(200);
    // Check if we don't get any error on the view edit page.
    $this->drupalGet('admin/structure/views/view/test_page_display_path');
    return $success && $this->assertSession()->statusCodeEquals(200);
  }

}
