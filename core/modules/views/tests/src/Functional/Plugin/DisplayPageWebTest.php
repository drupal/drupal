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
  public static $modules = ['menu_ui', 'block', 'views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->enableViewsTestModule();
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests arguments.
   */
  public function testArguments() {
    $this->drupalGet('test_route_without_arguments');
    $this->assertResponse(200);
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertEqual(count($result), 5, 'All entries was returned');

    $this->drupalGet('test_route_without_arguments/1');
    $this->assertResponse(404);

    $this->drupalGet('test_route_with_argument/1');
    $this->assertResponse(200);
    $this->assertCacheContexts(['languages:language_interface', 'route', 'theme', 'url']);
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertEqual(count($result), 1, 'Ensure that just the filtered entry was returned.');
    $this->assertEqual($result[0]->getText(), 1, 'The passed ID was returned.');

    $this->drupalGet('test_route_with_suffix/1/suffix');
    $this->assertResponse(200);
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertEqual(count($result), 1, 'Ensure that just the filtered entry was returned.');
    $this->assertEqual($result[0]->getText(), 1, 'The passed ID was returned.');

    $this->drupalGet('test_route_with_suffix_and_argument/1/suffix/2');
    $this->assertResponse(200);
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertEqual(count($result), 0, 'No result was returned.');

    $this->drupalGet('test_route_with_suffix_and_argument/1/suffix/1');
    $this->assertResponse(200);
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertEqual(count($result), 1, 'Ensure that just the filtered entry was returned.');
    $this->assertEqual($result[0]->getText(), 1, 'The passed ID was returned.');

    $this->drupalGet('test_route_with_long_argument/1');
    $this->assertResponse(200);
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertEqual(count($result), 1, 'Ensure that just the filtered entry was returned.');
    $this->assertEqual($result[0]->getText(), 1, 'The passed ID was returned.');
  }

  /**
   * Tests menu settings of page displays.
   */
  public function testPageDisplayMenu() {
    // Check local tasks.
    $this->drupalGet('test_page_display_menu');
    $this->assertResponse(200);
    $element = $this->xpath('//ul[contains(@class, :ul_class)]//a[contains(@class, :a_class)]/child::text()', [
      ':ul_class' => 'tabs primary',
      ':a_class' => 'is-active',
    ]);
    $this->assertEqual($element[0]->getText(), t('Test default tab'));
    $this->assertTitle('Test default page | Drupal');

    $this->drupalGet('test_page_display_menu/default');
    $this->assertResponse(404);

    $this->drupalGet('test_page_display_menu/local');
    $this->assertResponse(200);
    $element = $this->xpath('//ul[contains(@class, :ul_class)]//a[contains(@class, :a_class)]/child::text()', [
      ':ul_class' => 'tabs primary',
      ':a_class' => 'is-active',
    ]);
    $this->assertEqual($element[0]->getText(), t('Test local tab'));
    $this->assertTitle('Test local page | Drupal');

    // Check an ordinary menu link.
    $admin_user = $this->drupalCreateUser(['administer menu']);
    $this->drupalLogin($admin_user);
    $this->drupalPlaceBlock('system_menu_block:tools');
    $this->drupalGet('<front>');

    $menu_link = $this->cssSelect('nav.block-menu ul.menu a');
    $this->assertEqual($menu_link[0]->getText(), 'Test menu link');

    // Update the menu link.
    $this->drupalPostForm("admin/structure/menu/link/views_view:views.test_page_display_menu.page_3/edit", [
      'title' => 'New title',
    ], t('Save'));

    $this->drupalGet('<front>');
    $menu_link = $this->cssSelect('nav.block-menu ul.menu a');
    $this->assertEqual($menu_link[0]->getText(), 'New title');
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
    $success = $this->assertResponse(200);
    // Check if we don't get any error on the view edit page.
    $this->drupalGet('admin/structure/views/view/test_page_display_path');
    return $success && $this->assertResponse(200);
  }

}
