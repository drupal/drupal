<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\DisplayTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\Component\Utility\String;

/**
 * Tests the handling of displays in the UI, adding removing etc.
 */
use Drupal\views\Views;

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
    $default['label'] = $this->randomName(16);
    $default['id'] = strtolower($this->randomName(16));
    $default['description'] = $this->randomName(16);
    $default['page[create]'] = TRUE;
    $default['page[path]'] = $default['id'];

    $view += $default;

    $this->drupalPost('admin/structure/views/add', $view, t('Save and edit'));

    return $default;
  }

  /**
   * Tests removing a display.
   */
  public function testRemoveDisplay() {
    $view = $this->randomView();
    $path_prefix = 'admin/structure/views/view/' . $view['id'] .'/edit';

    $this->drupalGet($path_prefix . '/default');
    $this->assertNoFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-delete', 'Delete Page', 'Make sure there is no delete button on the default display.');

    $this->drupalGet($path_prefix . '/page_1');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-delete', 'Delete Page', 'Make sure there is a delete button on the page display.');

    // Delete the page, so we can test the undo process.
    $this->drupalPost($path_prefix . '/page_1', array(), 'Delete Page');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-undo-delete', 'Undo delete of Page', 'Make sure there a undo button on the page display after deleting.');
    $element = $this->xpath('//a[contains(@href, :href) and contains(@class, :class)]', array(':href' => $path_prefix . '/page_1', ':class' => 'views-display-deleted-link'));
    $this->assertTrue(!empty($element), 'Make sure the display link is marked as to be deleted.');

    $element = $this->xpath('//a[contains(@href, :href) and contains(@class, :class)]', array(':href' => $path_prefix . '/page_1', ':class' => 'views-display-deleted-link'));
    $this->assertTrue(!empty($element), 'Make sure the display link is marked as to be deleted.');

    // Undo the deleting of the display.
    $this->drupalPost($path_prefix . '/page_1', array(), 'Undo delete of Page');
    $this->assertNoFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-undo-delete', 'Undo delete of Page', 'Make sure there is no undo button on the page display after reverting.');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-delete', 'Delete Page', 'Make sure there is a delete button on the page display after the reverting.');

    // Now delete again and save the view.
    $this->drupalPost($path_prefix . '/page_1', array(), 'Delete Page');
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

    $path_prefix = 'admin/structure/views/view/' . $view['id'] .'/edit';
    $this->drupalGet($path_prefix);

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
    $path_prefix = 'admin/structure/views/view/' . $view['id'] .'/edit';

    $this->clickLink(t('Reorder displays'));
    $this->assertTrue($this->xpath('//tr[@id="display-row-default"]'), 'Make sure the default display appears on the reorder listing');
    $this->assertTrue($this->xpath('//tr[@id="display-row-page_1"]'), 'Make sure the page display appears on the reorder listing');
    $this->assertTrue($this->xpath('//tr[@id="display-row-block_1"]'), 'Make sure the block display appears on the reorder listing');

    // Put the block display in front of the page display.
    $edit = array(
      'displays[page_1][weight]' => 2,
      'displays[block_1][weight]' => 1
    );
    $this->drupalPost(NULL, $edit, t('Apply'));
    $this->drupalPost(NULL, array(), t('Save'));

    $view = views_get_view($view['id']);
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
    $path_prefix = 'admin/structure/views/view/' . $view['id'] .'/edit';

    $this->drupalGet($path_prefix);
    $this->drupalPost(NULL, array(), 'Clone Page');
    $this->assertLinkByHref($path_prefix . '/page_2', 0, 'Make sure after cloning the new display appears in the UI');
    $this->assertUrl($path_prefix . '/page_2', array(), 'The user got redirected to the new display.');

    // Set the title and override the css classes.
    $random_title = $this->randomName();
    $random_css = $this->randomName();
    $this->drupalPost("admin/structure/views/nojs/display/{$view['id']}/page_2/title", array('title' => $random_title), t('Apply'));
    $this->drupalPost("admin/structure/views/nojs/display/{$view['id']}/page_2/css_class", array('override[dropdown]' => 'page_2', 'css_class' => $random_css), t('Apply'));

    // Clone as a different display type.
    $this->drupalPost(NULL, array(), 'Clone as Block');
    $this->assertLinkByHref($path_prefix . '/block_1', 0, 'Make sure after cloning the new display appears in the UI');
    $this->assertUrl($path_prefix . '/block_1', array(), 'The user got redirected to the new display.');
    $this->assertText(t('Block settings'));
    $this->assertNoText(t('Page settings'));

    $this->drupalPost(NULL, array(), t('Save'));
    $view = views_get_view($view['id']);
    $view->initDisplay();

    $page_2 = $view->displayHandlers->get('page_2');
    $this->assertTrue($page_2, 'The new page display got saved.');
    $this->assertEqual($page_2->display['display_title'], 'Page');
    $block_1 = $view->displayHandlers->get('block_1');
    $this->assertTrue($block_1, 'The new block display got saved.');
    $this->assertEqual($block_1->display['display_plugin'], 'block');
    $this->assertEqual($block_1->display['display_title'], 'Block', 'The new display title got generated as expected.');
    $this->assertEqual($block_1->getOption('title'), $random_title, 'The overridden title option from the display got copied into the clone');
    $this->assertEqual($block_1->getOption('css_class'), $random_css, 'The overridden css_class option from the display got copied into the clone');
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
    $this->drupalPost(NULL, array(), 'Disable Page');
    $this->assertTrue($this->xpath('//div[contains(@class, :class)]', array(':class' => 'views-display-disabled')), 'Make sure the disabled display css class appears once the display is marked as such.');

    $this->assertNoFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-disable', '', 'Make sure the disable button is not visible.');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-enable', '', 'Make sure the enable button is visible.');
    $this->drupalPost(NULL, array(), 'Enable Page');
    $this->assertFalse($this->xpath('//div[contains(@class, :class)]', array(':class' => 'views-display-disabled')), 'Make sure the disabled display css class does not appears once the display is enabled again.');
  }

  /**
   * Tests views_ui_views_plugins_display_alter is altering plugin definitions.
   */
  public function testDisplayPluginsAlter() {
    $definitions = Views::pluginManager('display')->getDefinitions();

    $expected = array(
      'parent path' => 'admin/structure/views/view',
      'argument properties' => array('id'),
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
    $this->drupalPost($link_display_path, array('link_display' => 'page_1'), t('Apply'));
    // The form redirects to the master display.
    $this->drupalGet($path);

    $result = $this->xpath("//a[contains(@href, :path)]", array(':path' => $link_display_path));
    $this->assertEqual($result[0], 'Page', 'Make sure that the link option summary shows the right linked display.');

    $link_display_path = 'admin/structure/views/nojs/display/test_display/block_1/link_display';
    $this->drupalPost($link_display_path, array('link_display' => 'custom_url'), t('Apply'));
    // The form redirects to the master display.
    $this->drupalGet($path);

    $this->assertLink(t('Custom URL'), 0, 'The link option has custom url as summary.');
  }

  /**
   * Tests contextual links on Views page displays.
   */
  public function testPageContextualLinks() {
    $this->drupalLogin($this->drupalCreateUser(array('administer views', 'access contextual links')));
    $view = entity_load('view', 'test_display');
    $view->enable()->save();

    $this->drupalGet('test-display');
    $id = 'views_ui:admin/structure/views/view:test_display:location=page&name=test_display&display_id=page_1';
    // @see \Drupal\contextual\Tests\ContextualDynamicContextTest:assertContextualLinkPlaceHolder()
    $this->assertRaw('<div data-contextual-id="'. $id . '"></div>', format_string('Contextual link placeholder with id @id exists.', array('@id' => $id)));

    // Get server-rendered contextual links.
    // @see \Drupal\contextual\Tests\ContextualDynamicContextTest:renderContextualLinks()
    $post = urlencode('ids[0]') . '=' . urlencode($id);
    $response = $this->curlExec(array(
      CURLOPT_URL => url('contextual/render', array('absolute' => TRUE, 'query' => array('destination' => 'test-display'))),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $post,
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
      ),
    ));
    $this->assertResponse(200);
    $json = drupal_json_decode($response);
    $this->assertIdentical($json[$id], '<ul class="contextual-links"><li class="views-ui-edit odd first last"><a href="' . base_path() . 'admin/structure/views/view/test_display/edit/page_1?destination=test-display">Edit view</a></li></ul>');
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

    $view = views_get_view($id);
    $view->storage->disable()->save();

    $this->drupalGet('admin/structure/views/view/' . $id);
    $elements = $this->xpath('//div[contains(@class, :edit) and contains(@class, :status)]', array(':edit' => 'views-edit-view', ':status' => 'disabled'));
    $this->assertTrue($elements, 'The disabled class was found on the form wrapper.');
  }

}
