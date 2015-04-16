<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\DisplayPageWebTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Views;

/**
 * Tests the views page display plugin as webtest.
 *
 * @group views
 */
class DisplayPageWebTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_page_display', 'test_page_display_arguments', 'test_page_display_menu');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['menu_ui', 'block'];

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
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
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertEqual(count($result), 1, 'Ensure that just the filtered entry was returned.');
    $this->assertEqual((string) $result[0], 1, 'The passed ID was returned.');

    $this->drupalGet('test_route_with_suffix/1/suffix');
    $this->assertResponse(200);
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertEqual(count($result), 1, 'Ensure that just the filtered entry was returned.');
    $this->assertEqual((string) $result[0], 1, 'The passed ID was returned.');

    $this->drupalGet('test_route_with_suffix_and_argument/1/suffix/2');
    $this->assertResponse(200);
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertEqual(count($result), 0, 'No result was returned.');

    $this->drupalGet('test_route_with_suffix_and_argument/1/suffix/1');
    $this->assertResponse(200);
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertEqual(count($result), 1, 'Ensure that just the filtered entry was returned.');
    $this->assertEqual((string) $result[0], 1, 'The passed ID was returned.');

    $this->drupalGet('test_route_with_long_argument/1');
    $this->assertResponse(200);
    $result = $this->xpath('//span[@class="field-content"]');
    $this->assertEqual(count($result), 1, 'Ensure that just the filtered entry was returned.');
    $this->assertEqual((string) $result[0], 1, 'The passed ID was returned.');
  }

  /**
   * Tests menu settings of page displays.
   */
  public function testPageDisplayMenu() {
    // Check local tasks.
    $this->drupalGet('test_page_display_menu');
    $this->assertResponse(200);
    $element = $this->xpath('//ul[contains(@class, :ul_class)]//a[contains(@class, :a_class)]', array(
      ':ul_class' => 'tabs primary',
      ':a_class' => 'is-active',
    ));
    $this->assertEqual((string) $element[0], t('Test default tab'));
    $this->assertTitle(t('Test default page | Drupal'));

    $this->drupalGet('test_page_display_menu/default');
    $this->assertResponse(404);

    $this->drupalGet('test_page_display_menu/local');
    $this->assertResponse(200);
    $element = $this->xpath('//ul[contains(@class, :ul_class)]//a[contains(@class, :a_class)]', array(
      ':ul_class' => 'tabs primary',
      ':a_class' => 'is-active',
    ));
    $this->assertEqual((string) $element[0], t('Test local tab'));
    $this->assertTitle(t('Test local page | Drupal'));

    // Check an ordinary menu link.
    $admin_user = $this->drupalCreateUser(['administer menu']);
    $this->drupalLogin($admin_user);
    $this->drupalPlaceBlock('system_menu_block:tools');
    $this->drupalGet('<front>');

    $menu_link = $this->cssSelect('nav.block-menu ul.menu a');
    $this->assertEqual((string) $menu_link[0], 'Test menu link');

    // Update the menu link.
    $this->drupalPostForm("admin/structure/menu/link/views_view:views.test_page_display_menu.page_3/edit", [
      'title' => 'New title',
    ], t('Save'));

    $this->drupalGet('<front>');
    $menu_link = $this->cssSelect('nav.block-menu ul.menu a');
    $this->assertEqual((string) $menu_link[0], 'New title');
  }

  /**
   * Tests the title is not displayed in the output.
   */
  public function testTitleOutput() {
    $this->drupalGet('test_page_display_200');

    $view = Views::getView('test_page_display');
    $xpath = $this->cssSelect('div.view:contains("' . $view->getTitle() . '")');
    $this->assertFalse($xpath, 'The view title was not displayed in the view markup.');
  }

}
