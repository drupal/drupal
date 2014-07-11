<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\DisplayTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\String;

use Drupal\views\Views;
use Drupal\Core\Template\Attribute;

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
  public static $testViews = array('test_display');

  /**
   * Modules to enable
   *
   * @var array
   */
  public static $modules = array('contextual');

  /**
   * Tests reordering of displays.
   */
  public function testReorderDisplay() {
    $view = array(
      'block[create]' => TRUE
    );
    $view = $this->randomView($view);

    $this->clickLink(t('Reorder displays'));
    $this->assertTrue($this->xpath('//tr[@id="display-row-default"]'), 'Make sure the default display appears on the reorder listing');
    $this->assertTrue($this->xpath('//tr[@id="display-row-page_1"]'), 'Make sure the page display appears on the reorder listing');
    $this->assertTrue($this->xpath('//tr[@id="display-row-block_1"]'), 'Make sure the block display appears on the reorder listing');

    // Put the block display in front of the page display.
    $edit = array(
      'displays[page_1][weight]' => 2,
      'displays[block_1][weight]' => 1
    );
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->drupalPostForm(NULL, array(), t('Save'));

    $view = Views::getView($view['id']);
    $displays = $view->storage->get('display');
    $this->assertEqual($displays['default']['position'], 0, 'Make sure the master display comes first.');
    $this->assertEqual($displays['block_1']['position'], 1, 'Make sure the block display comes before the page display.');
    $this->assertEqual($displays['page_1']['position'], 2, 'Make sure the page display comes after the block display.');
  }

  /**
   * Tests disabling of a display.
   */
  public function testDisableDisplay() {
    $view = $this->randomView();
    $path_prefix = 'admin/structure/views/view/' . $view['id'] .'/edit';

    $this->drupalGet($path_prefix);
    $this->assertFalse($this->xpath('//div[contains(@class, :class)]', array(':class' => 'views-display-disabled')), 'Make sure the disabled display css class does not appear after initial adding of a view.');

    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-disable', '', 'Make sure the disable button is visible.');
    $this->assertNoFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-enable', '', 'Make sure the enable button is not visible.');
    $this->drupalPostForm(NULL, array(), 'Disable Page');
    $this->assertTrue($this->xpath('//div[contains(@class, :class)]', array(':class' => 'views-display-disabled')), 'Make sure the disabled display css class appears once the display is marked as such.');

    $this->assertNoFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-disable', '', 'Make sure the disable button is not visible.');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-enable', '', 'Make sure the enable button is visible.');
    $this->drupalPostForm(NULL, array(), 'Enable Page');
    $this->assertFalse($this->xpath('//div[contains(@class, :class)]', array(':class' => 'views-display-disabled')), 'Make sure the disabled display css class does not appears once the display is enabled again.');
  }

  /**
   * Tests views_ui_views_plugins_display_alter is altering plugin definitions.
   */
  public function testDisplayPluginsAlter() {
    $definitions = Views::pluginManager('display')->getDefinitions();

    $expected = array(
      'route_name' => 'views_ui.edit',
      'route_parameters_names' => array('view' => 'id'),
    );

    // Test the expected views_ui array exists on each definition.
    foreach ($definitions as $definition) {
      $this->assertIdentical($definition['contextual links']['views_ui_edit'], $expected, 'Expected views_ui array found in plugin definition.');
    }
  }

  /**
   * Tests display areas.
   */
  public function testDisplayAreas() {
    // Show the advanced column.
    \Drupal::config('views.settings')->set('ui.show.advanced_column', TRUE)->save();

    // Add a new data display to the view.
    $view = Views::getView('test_display');
    $view->storage->addDisplay('display_no_area_test');
    $view->save();

    $this->drupalGet('admin/structure/views/view/test_display/edit/display_no_area_test_1');

    // Create a mapping of area type => class.
    $areas = array(
      'header' => 'header',
      'footer' => 'footer',
      'empty' => 'no-results-behavior',
    );

    // Assert that the expected text is found in each area category.
    foreach ($areas as $type => $class) {
      $element = $this->xpath('//div[contains(@class, :class)]/div', array(':class' => $class));
      $this->assertEqual((string) $element[0], String::format('The selected display type does not utilize @type plugins', array('@type' => $type)));
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
    $result = $this->xpath("//a[contains(@href, :path)]", array(':path' => $link_display_path));
    $this->assertEqual($result[0], t('None'), 'Make sure that the link option summary shows "None" by default.');

    $this->drupalGet($link_display_path);
    $this->assertFieldChecked('edit-link-display-0');

    // Test the default radio option on the link display form.
    $this->drupalPostForm($link_display_path, array('link_display' => 'page_1'), t('Apply'));
    // The form redirects to the master display.
    $this->drupalGet($path);

    $result = $this->xpath("//a[contains(@href, :path)]", array(':path' => $link_display_path));
    $this->assertEqual($result[0], 'Page', 'Make sure that the link option summary shows the right linked display.');

    $this->drupalPostForm($link_display_path, array('link_display' => 'custom_url', 'link_url' => 'a-custom-url'), t('Apply'));
    // The form redirects to the master display.
    $this->drupalGet($path);

    $this->assertLink(t('Custom URL'), 0, 'The link option has custom url as summary.');

    // Test the default link_url value for new display
    $this->drupalPostForm(NULL, array(), t('Add Block'));
    $this->assertUrl('admin/structure/views/view/test_display/edit/block_2');
    $this->clickLink(t('Custom URL'));
    $this->assertFieldByName('link_url', 'a-custom-url');
  }

  /**
   * Tests contextual links on Views page displays.
   */
  public function testPageContextualLinks() {
    $this->drupalLogin($this->drupalCreateUser(array('administer views', 'access contextual links')));
    $view = entity_load('view', 'test_display');
    $view->enable()->save();

    $this->drupalGet('test-display');
    $id = 'views_ui_edit:view=test_display:location=page&name=test_display&display_id=page_1';
    // @see \Drupal\contextual\Tests\ContextualDynamicContextTest:assertContextualLinkPlaceHolder()
    $this->assertRaw('<div' . new Attribute(array('data-contextual-id' => $id)) . '></div>', format_string('Contextual link placeholder with id @id exists.', array('@id' => $id)));

    // Get server-rendered contextual links.
    // @see \Drupal\contextual\Tests\ContextualDynamicContextTest:renderContextualLinks()
    $post = array('ids[0]' => $id);
    $response = $this->drupalPost('contextual/render', 'application/json', $post, array('query' => array('destination' => 'test-display')));
    $this->assertResponse(200);
    $json = Json::decode($response);
    $this->assertIdentical($json[$id], '<ul class="contextual-links"><li class="views-uiedit"><a href="' . base_path() . 'admin/structure/views/view/test_display/edit/page_1">Edit view</a></li></ul>');
  }

  /**
   * Tests that the view status is correctly reflected on the edit form.
   */
  public function testViewStatus() {
    $view = $this->randomView();
    $id = $view['id'];

    // The view should initially have the enabled class on it's form wrapper.
    $this->drupalGet('admin/structure/views/view/' . $id);
    $elements = $this->xpath('//div[contains(@class, :edit) and contains(@class, :status)]', array(':edit' => 'views-edit-view', ':status' => 'enabled'));
    $this->assertTrue($elements, 'The enabled class was found on the form wrapper');

    $view = Views::getView($id);
    $view->storage->disable()->save();

    $this->drupalGet('admin/structure/views/view/' . $id);
    $elements = $this->xpath('//div[contains(@class, :edit) and contains(@class, :status)]', array(':edit' => 'views-edit-view', ':status' => 'disabled'));
    $this->assertTrue($elements, 'The disabled class was found on the form wrapper.');
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
    $this->drupalPostForm($display_title_path, array('display_title' => $display_title), t('Apply'));

    $placeholder = array('!display_title' => $display_title);
    // Ensure that the dropdown buttons are displayed correctly.
    $this->assertFieldByXpath('//input[@type="submit"]', t('Duplicate !display_title', $placeholder));
    $this->assertFieldByXpath('//input[@type="submit"]', t('Delete !display_title', $placeholder));
    $this->assertFieldByXpath('//input[@type="submit"]', t('Disable !display_title', $placeholder));
    $this->assertNoFieldByXpath('//input[@type="submit"]', t('Enable !display_title', $placeholder));

    // Disable the display so we can test the rendering of the "Enable" button.
    $this->drupalPostForm(NULL, NULL, t('Disable !display_title', $placeholder));
    $this->assertFieldByXpath('//input[@type="submit"]', t('Enable !display_title', $placeholder));
    $this->assertNoFieldByXpath('//input[@type="submit"]', t('Disable !display_title', $placeholder));
  }
}
