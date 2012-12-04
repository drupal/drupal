<?php

/**
 * @file
 * Definition of Drupal\views\Tests\UI\DisplayTest.
 */

namespace Drupal\views\Tests\UI;

/**
 * Tests the handling of displays in the UI, adding removing etc.
 */
class DisplayTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_display');

  public static function getInfo() {
    return array(
      'name' => 'Display tests',
      'description' => 'Tests the handling of displays in the UI, adding removing etc.',
      'group' => 'Views UI',
    );
  }

  /**
   * A helper method which creates a random view.
   */
  public function randomView(array $view = array()) {
    // Create a new view in the UI.
    $default = array();
    $default['human_name'] = $this->randomName(16);
    $default['name'] = strtolower($this->randomName(16));
    $default['description'] = $this->randomName(16);
    $default['page[create]'] = TRUE;
    $default['page[path]'] = $default['name'];

    $view += $default;

    $this->drupalPost('admin/structure/views/add', $view, t('Continue & edit'));

    return $default;
  }

  /**
   * Tests removing a display.
   */
  public function testRemoveDisplay() {
    $view = $this->randomView();
    $path_prefix = 'admin/structure/views/view/' . $view['name'] .'/edit';

    $this->drupalGet($path_prefix . '/default');
    $this->assertNoFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-delete', 'delete Page', 'Make sure there is no delete button on the default display.');

    $this->drupalGet($path_prefix . '/page_1');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-delete', 'delete Page', 'Make sure there is a delete button on the page display.');

    // Delete the page, so we can test the undo process.
    $this->drupalPost($path_prefix . '/page_1', array(), 'delete Page');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-undo-delete', 'undo delete of Page', 'Make sure there a undo button on the page display after deleting.');
    $this->assertTrue($this->xpath('//a[contains(@class, :class)]', array(':class' => 'views-display-deleted-link')), 'Make sure the display link is marked as to be deleted.');

    // Undo the deleting of the display.
    $this->drupalPost($path_prefix . '/page_1', array(), 'undo delete of Page');
    $this->assertNoFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-undo-delete', 'undo delete of Page', 'Make sure there is no undo button on the page display after reverting.');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-delete', 'delete Page', 'Make sure there is a delete button on the page display after the reverting.');

    // Now delete again and save the view.
    $this->drupalPost($path_prefix . '/page_1', array(), 'delete Page');
    $this->drupalPost(NULL, array(), t('Save'));

    $this->assertNoLinkByHref($path_prefix . '/page_1', 'Make sure there is no display tab for the deleted display.');
  }

  /**
   * Tests adding a display.
   */
  public function testAddDisplay() {
    // Show the master display.
    config('views.settings')->set('ui.show.master_display', TRUE)->save();

    $settings['page[create]'] = FALSE;
    $view = $this->randomView($settings);

    $path_prefix = 'admin/structure/views/view/' . $view['name'] .'/edit';
    $this->drupalGet($path_prefix);
    $this->drupalPost(NULL, array(), t('Save'));

    // Add a new display.
    $this->drupalPost(NULL, array(), 'Add Page');
    $this->assertLinkByHref($path_prefix . '/page_1', 0, 'Make sure after adding a display the new display appears in the UI');

    $this->assertNoLink('Master*', 0, 'Make sure the master display is not marked as changed.');
    $this->assertLink('Page*', 0, 'Make sure the added display is marked as changed.');
  }

  /**
   * Tests reordering of displays.
   */
  public function testReorderDisplay() {
    $view = array(
      'block[create]' => TRUE
    );
    $view = $this->randomView($view);
    $path_prefix = 'admin/structure/views/view/' . $view['name'] .'/edit';

    $edit = array();
    $this->drupalPost($path_prefix, $edit, t('Save'));
    $this->clickLink(t('reorder displays'));
    $this->assertTrue($this->xpath('//tr[@id="display-row-default"]'), 'Make sure the default display appears on the reorder listing');
    $this->assertTrue($this->xpath('//tr[@id="display-row-page_1"]'), 'Make sure the page display appears on the reorder listing');
    $this->assertTrue($this->xpath('//tr[@id="display-row-block_1"]'), 'Make sure the block display appears on the reorder listing');

    // Put the block display in front of the page display.
    $edit = array(
      'page_1[weight]' => 2,
      'block_1[weight]' => 1
    );
    $this->drupalPost(NULL, $edit, t('Apply'));
    $this->drupalPost(NULL, array(), t('Save'));

    $view = views_get_view($view['name']);
    $displays = $view->storage->get('display');
    $this->assertEqual($displays['default']['position'], 0, 'Make sure the master display comes first.');
    $this->assertEqual($displays['block_1']['position'], 1, 'Make sure the block display comes before the page display.');
    $this->assertEqual($displays['page_1']['position'], 2, 'Make sure the page display comes after the block display.');
  }

  /**
   * Tests that the correct display is loaded by default.
   */
  public function testDefaultDisplay() {
    $this->drupalGet('admin/structure/views/view/test_display');
    $elements = $this->xpath('//*[@id="views-page-1-display-title"]');
    $this->assertEqual(count($elements), 1, 'The page display is loaded as the default display.');
  }

  /**
   * Tests the cloning of a display.
   */
  public function testCloneDisplay() {
    $view = $this->randomView();
    $path_prefix = 'admin/structure/views/view/' . $view['name'] .'/edit';

    $this->drupalGet($path_prefix);
    $this->drupalPost(NULL, array(), 'clone Page');
    $this->assertLinkByHref($path_prefix . '/page_1', 0, 'Make sure after cloning the new display appears in the UI');
  }

  /**
   * Tests disabling of a display.
   */
  public function testDisableDisplay() {
    $view = $this->randomView();
    $path_prefix = 'admin/structure/views/view/' . $view['name'] .'/edit';

    $this->drupalGet($path_prefix);
    $this->assertFalse($this->xpath('//div[contains(@class, :class)]', array(':class' => 'views-display-disabled')), 'Make sure the disabled display css class does not appear after initial adding of a view.');

    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-disable', '', 'Make sure the disable button is visible.');
    $this->assertNoFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-enable', '', 'Make sure the enable button is not visible.');
    $this->drupalPost(NULL, array(), 'disable Page');
    $this->assertTrue($this->xpath('//div[contains(@class, :class)]', array(':class' => 'views-display-disabled')), 'Make sure the disabled display css class appears once the display is marked as such.');

    $this->assertNoFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-disable', '', 'Make sure the disable button is not visible.');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-enable', '', 'Make sure the enable button is visible.');
    $this->drupalPost(NULL, array(), 'enable Page');
    $this->assertFalse($this->xpath('//div[contains(@class, :class)]', array(':class' => 'views-display-disabled')), 'Make sure the disabled display css class does not appears once the display is enabled again.');
  }

  /**
   * Tests views_ui_views_plugins_display_alter is altering plugin definitions.
   */
  public function testDisplayPluginsAlter() {
    $definitions = drupal_container()->get('plugin.manager.views.display')->getDefinitions();

    $expected = array(
      'parent path' => 'admin/structure/views/view',
      'argument properties' => array('name'),
    );

    // Test the expected views_ui array exists on each definition.
    foreach ($definitions as $definition) {
      $this->assertIdentical($definition['contextual links']['views_ui'], $expected, 'Expected views_ui array found in plugin definition.');
    }
  }

  /**
   * Tests display areas.
   */
  public function testDisplayAreas() {
    // Show the advanced column.
    config('views.settings')->set('ui.show.advanced_column', TRUE)->save();

    // Add a new data display to the view.
    $view = views_get_view('test_display');
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
      $this->assertEqual((string) $element[0], "The selected display type does not utilize $type plugins");
    }
  }

}
