<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Functional;

use Drupal\Component\Utility\Unicode;
use Drupal\views\Entity\View;
use Drupal\views\Views;

/**
 * Tests the display UI.
 *
 * @group views_ui
 * @group #slow
 */
class DisplayTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_display'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['contextual'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests adding a display.
   */
  public function testAddDisplay(): void {
    $this->randomView();
    $this->assertSession()->elementNotExists('xpath', '//li[@data-drupal-selector="edit-displays-top-tabs-block-1"]');
    $this->assertSession()->elementNotExists('xpath', '//li[@data-drupal-selector="edit-displays-top-tabs-block-2"]');
    $this->assertSession()->pageTextMatchesCount(0, '/Block name:/');

    $this->submitForm([], 'Add Block');
    $this->assertSession()->elementTextContains('xpath', '//li[@data-drupal-selector="edit-displays-top-tabs-block-1"]', 'Block*');
    $this->assertSession()->elementNotExists('xpath', '//li[@data-drupal-selector="edit-displays-top-tabs-block-2"]');
    $this->assertSession()->pageTextMatchesCount(1, '/Block name:/');
  }

  /**
   * Tests reordering of displays.
   */
  public function testReorderDisplay(): void {
    $view = [
      'block[create]' => TRUE,
    ];
    $view = $this->randomView($view);

    $this->clickLink('Reorder displays');
    $this->assertSession()->elementExists('xpath', '//tr[@id="display-row-default"]');
    $this->assertSession()->elementExists('xpath', '//tr[@id="display-row-page_1"]');
    $this->assertSession()->elementExists('xpath', '//tr[@id="display-row-block_1"]');

    // Ensure the view displays are in the expected order in configuration.
    $expected_display_order = ['default', 'block_1', 'page_1'];
    $this->assertEquals($expected_display_order, array_keys(Views::getView($view['id'])->storage->get('display')), 'The correct display names are present.');
    // Put the block display in front of the page display.
    $edit = [
      'displays[page_1][weight]' => 2,
      'displays[block_1][weight]' => 1,
    ];
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');

    $view = Views::getView($view['id']);
    $displays = $view->storage->get('display');
    $this->assertEquals(0, $displays['default']['position'], 'Make sure the default display comes first.');
    $this->assertEquals(1, $displays['block_1']['position'], 'Make sure the block display comes before the page display.');
    $this->assertEquals(2, $displays['page_1']['position'], 'Make sure the page display comes after the block display.');

    // Ensure the view displays are in the expected order in configuration.
    $this->assertEquals($expected_display_order, array_keys($view->storage->get('display')), 'The correct display names are present.');
  }

  /**
   * Tests disabling of a display.
   */
  public function testDisableDisplay(): void {
    $view = $this->randomView();
    $path_prefix = 'admin/structure/views/view/' . $view['id'] . '/edit';

    // Verify that the disabled display css class does not appear after initial
    // adding of a view.
    $this->drupalGet($path_prefix);
    $this->assertSession()->elementNotExists('xpath', "//div[contains(@class, 'views-display-disabled')]");
    $this->assertSession()->buttonExists('edit-displays-settings-settings-content-tab-content-details-top-actions-disable');
    $this->assertSession()->buttonNotExists('edit-displays-settings-settings-content-tab-content-details-top-actions-enable');

    // Verify that the disabled display css class appears once the display is
    // marked as such.
    $this->submitForm([], 'Disable Page');
    $this->assertSession()->elementExists('xpath', "//div[contains(@class, 'views-display-disabled')]");
    $this->assertSession()->buttonNotExists('edit-displays-settings-settings-content-tab-content-details-top-actions-disable');
    $this->assertSession()->buttonExists('edit-displays-settings-settings-content-tab-content-details-top-actions-enable');

    // Verify that the disabled display css class does not appears once the
    // display is enabled again.
    $this->submitForm([], 'Enable Page');
    $this->assertSession()->elementNotExists('xpath', "//div[contains(@class, 'views-display-disabled')]");
  }

  /**
   * Tests views_ui_views_plugins_display_alter is altering plugin definitions.
   */
  public function testDisplayPluginsAlter(): void {
    $definitions = Views::pluginManager('display')->getDefinitions();

    $expected = [
      'route_name' => 'entity.view.edit_form',
      'route_parameters_names' => ['view' => 'id'],
    ];

    // Test the expected views_ui array exists on each definition.
    foreach ($definitions as $definition) {
      $this->assertSame($expected, $definition['contextual links']['entity.view.edit_form'], 'Expected views_ui array found in plugin definition.');
    }
  }

  /**
   * Tests display areas.
   */
  public function testDisplayAreas(): void {
    // Show the advanced column.
    $this->config('views.settings')->set('ui.show.advanced_column', TRUE)->save();

    // Add a new data display to the view.
    $view = Views::getView('test_display');
    $view->storage->addDisplay('display_no_area_test');
    $view->save();

    $this->drupalGet('admin/structure/views/view/test_display/edit/display_no_area_test_1');

    $areas = [
      'header',
      'footer',
      'empty',
    ];

    // Assert that the expected text is found in each area category.
    foreach ($areas as $type) {
      $this->assertSession()->elementTextEquals('xpath', "//div[contains(@class, '$type')]/div", "The selected display type does not use $type plugins");
    }
  }

  /**
   * Tests the link-display setting.
   */
  public function testLinkDisplay(): void {
    // Test setting the link display in the UI form.
    $path = 'admin/structure/views/view/test_display/edit/block_1';
    $link_display_path = 'admin/structure/views/nojs/display/test_display/block_1/link_display';

    // Test the link text displays 'None' and not 'Block 1'
    $this->drupalGet($path);
    $this->assertSession()->elementTextEquals('xpath', "//a[contains(@href, '{$link_display_path}')]", 'None');

    $this->drupalGet($link_display_path);
    $this->assertSession()->checkboxChecked('edit-link-display-0');

    // Test the default radio option on the link display form.
    $this->drupalGet($link_display_path);
    $this->submitForm(['link_display' => 'page_1'], 'Apply');
    // The form redirects to the default display.
    $this->drupalGet($path);

    // Test that the link option summary shows the right linked display.
    $this->assertSession()->elementTextEquals('xpath', "//a[contains(@href, '{$link_display_path}')]", 'Page');

    $this->drupalGet($link_display_path);
    $this->submitForm([
      'link_display' => 'custom_url',
      'link_url' => 'a-custom-url',
    ], 'Apply');
    // The form redirects to the default display.
    $this->drupalGet($path);

    $this->assertSession()->linkExists('Custom URL', 0, 'The link option has custom URL as summary.');

    // Test the default link_url value for new display
    $this->submitForm([], 'Add Block');
    $this->assertSession()->addressEquals('admin/structure/views/view/test_display/edit/block_2');
    $this->clickLink('Custom URL');
    $this->assertSession()->fieldValueEquals('link_url', 'a-custom-url');
  }

  /**
   * Tests that the view status is correctly reflected on the edit form.
   */
  public function testViewStatus(): void {
    $view = $this->randomView();
    $id = $view['id'];

    // The view should initially have the enabled class on its form wrapper.
    $this->drupalGet('admin/structure/views/view/' . $id);
    $this->assertSession()->elementExists('xpath', "//div[contains(@class, 'views-edit-view') and contains(@class, 'enabled')]");

    $view = Views::getView($id);
    $view->storage->disable()->save();

    // The view should now have the disabled class on its form wrapper.
    $this->drupalGet('admin/structure/views/view/' . $id);
    $this->assertSession()->elementExists('xpath', "//div[contains(@class, 'views-edit-view') and contains(@class, 'disabled')]");
  }

  /**
   * Ensures that no XSS is possible for buttons.
   */
  public function testDisplayTitleInButtonsXss(): void {
    $xss_markup = '"><script>alert(123)</script>';
    $view = $this->randomView();
    $view = View::load($view['id']);
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.show.default_display', TRUE)->save();

    foreach ([$xss_markup, '&quot;><script>alert(123)</script>'] as $input) {
      $display =& $view->getDisplay('page_1');
      $display['display_title'] = $input;
      $view->save();

      $this->drupalGet("admin/structure/views/view/{$view->id()}");
      $escaped = Unicode::truncate($input, 25, FALSE, TRUE);
      $this->assertSession()->assertEscaped($escaped);
      $this->assertSession()->responseNotContains($xss_markup);

      $this->drupalGet("admin/structure/views/view/{$view->id()}/edit/page_1");
      $this->assertSession()->assertEscaped("View $escaped");
      $this->assertSession()->responseNotContains("View $xss_markup");
      $this->assertSession()->assertEscaped("Duplicate $escaped");
      $this->assertSession()->responseNotContains("Duplicate $xss_markup");
      $this->assertSession()->assertEscaped("Delete $escaped");
      $this->assertSession()->responseNotContains("Delete $xss_markup");
    }
  }

  /**
   * Tests the action links on the edit display UI.
   */
  public function testActionLinks(): void {
    // Change the display title of a display so it contains characters that will
    // be escaped when rendered.
    $display_title = "'<test>'";
    $this->drupalGet('admin/structure/views/view/test_display');
    $display_title_path = 'admin/structure/views/nojs/display/test_display/block_1/display_title';
    $this->drupalGet($display_title_path);
    $this->submitForm(['display_title' => $display_title], 'Apply');

    // Ensure that the title is escaped as expected.
    $this->assertSession()->assertEscaped($display_title);
    $this->assertSession()->responseNotContains($display_title);

    // Ensure that the dropdown buttons are displayed correctly.
    $this->assertSession()->buttonExists('Duplicate ' . $display_title);
    $this->assertSession()->buttonExists('Delete ' . $display_title);
    $this->assertSession()->buttonExists('Disable ' . $display_title);
    $this->assertSession()->buttonNotExists('Enable ' . $display_title);

    // Disable the display so we can test the rendering of the "Enable" button.
    $this->submitForm([], 'Disable ' . $display_title);
    $this->assertSession()->buttonExists('Enable ' . $display_title);
    $this->assertSession()->buttonNotExists('Disable ' . $display_title);

    // Ensure that the title is escaped as expected.
    $this->assertSession()->assertEscaped($display_title);
    $this->assertSession()->responseNotContains($display_title);
  }

  /**
   * Tests that the override option is hidden when it's not needed.
   */
  public function testHideDisplayOverride(): void {
    // Test that the override option appears with two displays.
    $this->drupalGet('admin/structure/views/nojs/handler/test_display/page_1/field/title');
    $this->assertSession()->pageTextContains('All displays');

    // Remove a display and test if the override option is hidden.
    $this->drupalGet('admin/structure/views/view/test_display/edit/block_1');
    $this->submitForm([], 'Delete Block');
    $this->submitForm([], 'Save');

    $this->drupalGet('admin/structure/views/nojs/handler/test_display/page_1/field/title');
    $this->assertSession()->pageTextNotContains('All displays');

    // Test that the override option is shown when default display is on.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.show.default_display', TRUE)->save();
    $this->drupalGet('admin/structure/views/nojs/handler/test_display/page_1/field/title');
    $this->assertSession()->pageTextContains('All displays');

    // Test that the override option is shown if the current display is
    // overridden so that the option to revert is available.
    $this->submitForm(['override[dropdown]' => 'page_1'], 'Apply');
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.show.default_display', FALSE)->save();
    $this->drupalGet('admin/structure/views/nojs/handler/test_display/page_1/field/title');
    $this->assertSession()->pageTextContains('Revert to default');
  }

}
