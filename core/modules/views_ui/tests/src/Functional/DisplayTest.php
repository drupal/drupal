<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\views\Entity\View;
use Drupal\views\Views;

/**
 * Tests the display UI.
 *
 * @group views_ui
 */
class DisplayTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_display'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['contextual'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests adding a display.
   */
  public function testAddDisplay() {
    $view = $this->randomView();
    $this->assertNoText('Block');
    $this->assertNoText('Block 2');

    $this->submitForm([], 'Add Block');
    $this->assertText('Block');
    $this->assertNoText('Block 2');
  }

  /**
   * Tests reordering of displays.
   */
  public function testReorderDisplay() {
    $view = [
      'block[create]' => TRUE,
    ];
    $view = $this->randomView($view);

    $this->clickLink(t('Reorder displays'));
    $this->assertNotEmpty($this->xpath('//tr[@id="display-row-default"]'), 'Make sure the default display appears on the reorder listing');
    $this->assertNotEmpty($this->xpath('//tr[@id="display-row-page_1"]'), 'Make sure the page display appears on the reorder listing');
    $this->assertNotEmpty($this->xpath('//tr[@id="display-row-block_1"]'), 'Make sure the block display appears on the reorder listing');

    // Ensure the view displays are in the expected order in configuration.
    $expected_display_order = ['default', 'block_1', 'page_1'];
    $this->assertEqual(array_keys(Views::getView($view['id'])->storage->get('display')), $expected_display_order, 'The correct display names are present.');
    // Put the block display in front of the page display.
    $edit = [
      'displays[page_1][weight]' => 2,
      'displays[block_1][weight]' => 1,
    ];
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');

    $view = Views::getView($view['id']);
    $displays = $view->storage->get('display');
    $this->assertEqual($displays['default']['position'], 0, 'Make sure the master display comes first.');
    $this->assertEqual($displays['block_1']['position'], 1, 'Make sure the block display comes before the page display.');
    $this->assertEqual($displays['page_1']['position'], 2, 'Make sure the page display comes after the block display.');

    // Ensure the view displays are in the expected order in configuration.
    $this->assertEqual(array_keys($view->storage->get('display')), $expected_display_order, 'The correct display names are present.');
  }

  /**
   * Tests disabling of a display.
   */
  public function testDisableDisplay() {
    $view = $this->randomView();
    $path_prefix = 'admin/structure/views/view/' . $view['id'] . '/edit';

    $this->drupalGet($path_prefix);
    $this->assertEmpty($this->xpath('//div[contains(@class, :class)]', [':class' => 'views-display-disabled']), 'Make sure the disabled display css class does not appear after initial adding of a view.');

    $this->assertSession()->buttonExists('edit-displays-settings-settings-content-tab-content-details-top-actions-disable');
    $this->assertSession()->buttonNotExists('edit-displays-settings-settings-content-tab-content-details-top-actions-enable');
    $this->submitForm([], 'Disable Page');
    $this->assertNotEmpty($this->xpath('//div[contains(@class, :class)]', [':class' => 'views-display-disabled']), 'Make sure the disabled display css class appears once the display is marked as such.');

    $this->assertSession()->buttonNotExists('edit-displays-settings-settings-content-tab-content-details-top-actions-disable');
    $this->assertSession()->buttonExists('edit-displays-settings-settings-content-tab-content-details-top-actions-enable');
    $this->submitForm([], 'Enable Page');
    $this->assertEmpty($this->xpath('//div[contains(@class, :class)]', [':class' => 'views-display-disabled']), 'Make sure the disabled display css class does not appears once the display is enabled again.');
  }

  /**
   * Tests views_ui_views_plugins_display_alter is altering plugin definitions.
   */
  public function testDisplayPluginsAlter() {
    $definitions = Views::pluginManager('display')->getDefinitions();

    $expected = [
      'route_name' => 'entity.view.edit_form',
      'route_parameters_names' => ['view' => 'id'],
    ];

    // Test the expected views_ui array exists on each definition.
    foreach ($definitions as $definition) {
      $this->assertIdentical($definition['contextual links']['entity.view.edit_form'], $expected, 'Expected views_ui array found in plugin definition.');
    }
  }

  /**
   * Tests display areas.
   */
  public function testDisplayAreas() {
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
      $element = $this->xpath('//div[contains(@class, :class)]/div', [':class' => $type]);
      $this->assertEqual($element[0]->getHtml(), new FormattableMarkup('The selected display type does not use @type plugins', ['@type' => $type]));
    }
  }

  /**
   * Tests the link-display setting.
   */
  public function testLinkDisplay() {
    // Test setting the link display in the UI form.
    $path = 'admin/structure/views/view/test_display/edit/block_1';
    $link_display_path = 'admin/structure/views/nojs/display/test_display/block_1/link_display';

    // Test the link text displays 'None' and not 'Block 1'
    $this->drupalGet($path);
    $result = $this->xpath("//a[contains(@href, :path)]", [':path' => $link_display_path]);
    $this->assertEqual($result[0]->getHtml(), t('None'), 'Make sure that the link option summary shows "None" by default.');

    $this->drupalGet($link_display_path);
    $this->assertSession()->checkboxChecked('edit-link-display-0');

    // Test the default radio option on the link display form.
    $this->drupalPostForm($link_display_path, ['link_display' => 'page_1'], 'Apply');
    // The form redirects to the master display.
    $this->drupalGet($path);

    $result = $this->xpath("//a[contains(@href, :path)]", [':path' => $link_display_path]);
    $this->assertEqual($result[0]->getHtml(), 'Page', 'Make sure that the link option summary shows the right linked display.');

    $this->drupalPostForm($link_display_path, ['link_display' => 'custom_url', 'link_url' => 'a-custom-url'], 'Apply');
    // The form redirects to the master display.
    $this->drupalGet($path);

    $this->assertSession()->linkExists('Custom URL', 0, 'The link option has custom URL as summary.');

    // Test the default link_url value for new display
    $this->submitForm([], 'Add Block');
    $this->assertSession()->addressEquals('admin/structure/views/view/test_display/edit/block_2');
    $this->clickLink(t('Custom URL'));
    $this->assertSession()->fieldValueEquals('link_url', 'a-custom-url');
  }

  /**
   * Tests that the view status is correctly reflected on the edit form.
   */
  public function testViewStatus() {
    $view = $this->randomView();
    $id = $view['id'];

    // The view should initially have the enabled class on its form wrapper.
    $this->drupalGet('admin/structure/views/view/' . $id);
    $elements = $this->xpath('//div[contains(@class, :edit) and contains(@class, :status)]', [':edit' => 'views-edit-view', ':status' => 'enabled']);
    $this->assertNotEmpty($elements, 'The enabled class was found on the form wrapper');

    $view = Views::getView($id);
    $view->storage->disable()->save();

    $this->drupalGet('admin/structure/views/view/' . $id);
    $elements = $this->xpath('//div[contains(@class, :edit) and contains(@class, :status)]', [':edit' => 'views-edit-view', ':status' => 'disabled']);
    $this->assertNotEmpty($elements, 'The disabled class was found on the form wrapper.');
  }

  /**
   * Ensures that no XSS is possible for buttons.
   */
  public function testDisplayTitleInButtonsXss() {
    $xss_markup = '"><script>alert(123)</script>';
    $view = $this->randomView();
    $view = View::load($view['id']);
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.show.master_display', TRUE)->save();

    foreach ([$xss_markup, '&quot;><script>alert(123)</script>'] as $input) {
      $display =& $view->getDisplay('page_1');
      $display['display_title'] = $input;
      $view->save();

      $this->drupalGet("admin/structure/views/view/{$view->id()}");
      $escaped = views_ui_truncate($input, 25);
      $this->assertSession()->assertEscaped($escaped);
      $this->assertNoRaw($xss_markup);

      $this->drupalGet("admin/structure/views/view/{$view->id()}/edit/page_1");
      $this->assertSession()->assertEscaped("View $escaped");
      $this->assertNoRaw("View $xss_markup");
      $this->assertSession()->assertEscaped("Duplicate $escaped");
      $this->assertNoRaw("Duplicate $xss_markup");
      $this->assertSession()->assertEscaped("Delete $escaped");
      $this->assertNoRaw("Delete $xss_markup");
    }
  }

  /**
   * Tests the action links on the edit display UI.
   */
  public function testActionLinks() {
    // Change the display title of a display so it contains characters that will
    // be escaped when rendered.
    $display_title = "'<test>'";
    $this->drupalGet('admin/structure/views/view/test_display');
    $display_title_path = 'admin/structure/views/nojs/display/test_display/block_1/display_title';
    $this->drupalPostForm($display_title_path, ['display_title' => $display_title], 'Apply');

    // Ensure that the title is escaped as expected.
    $this->assertSession()->assertEscaped($display_title);
    $this->assertNoRaw($display_title);

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
    $this->assertNoRaw($display_title);
  }

  /**
   * Tests that the override option is hidden when it's not needed.
   */
  public function testHideDisplayOverride() {
    // Test that the override option appears with two displays.
    $this->drupalGet('admin/structure/views/nojs/handler/test_display/page_1/field/title');
    $this->assertText('All displays');

    // Remove a display and test if the override option is hidden.
    $this->drupalPostForm('admin/structure/views/view/test_display/edit/block_1', [], 'Delete Block');
    $this->submitForm([], 'Save');

    $this->drupalGet('admin/structure/views/nojs/handler/test_display/page_1/field/title');
    $this->assertNoText('All displays');

    // Test that the override option is shown when display master is on.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.show.master_display', TRUE)->save();
    $this->drupalGet('admin/structure/views/nojs/handler/test_display/page_1/field/title');
    $this->assertText('All displays');

    // Test that the override option is shown if the current display is
    // overridden so that the option to revert is available.
    $this->submitForm(['override[dropdown]' => 'page_1'], 'Apply');
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.show.master_display', FALSE)->save();
    $this->drupalGet('admin/structure/views/nojs/handler/test_display/page_1/field/title');
    $this->assertText('Revert to default');
  }

}
