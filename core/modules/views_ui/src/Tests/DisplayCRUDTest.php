<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\DisplayCRUDTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\views\Views;

/**
 * Tests creation, retrieval, updating, and deletion of displays in the Web UI.
 *
 * @group views_ui
 */
class DisplayCRUDTest extends UITestBase {

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
   * Tests adding a display.
   */
  public function testAddDisplay() {
    // Show the master display.
    $this->config('views.settings')->set('ui.show.master_display', TRUE)->save();

    $settings['page[create]'] = FALSE;
    $view = $this->randomView($settings);

    $path_prefix = 'admin/structure/views/view/' . $view['id'] .'/edit';
    $this->drupalGet($path_prefix);

    // Add a new display.
    $this->drupalPostForm(NULL, array(), 'Add Page');
    $this->assertLinkByHref($path_prefix . '/page_1', 0, 'Make sure after adding a display the new display appears in the UI');

    $this->assertNoLink('Master*', 0, 'Make sure the master display is not marked as changed.');
    $this->assertLink('Page*', 0, 'Make sure the added display is marked as changed.');

    $this->drupalPostForm("admin/structure/views/nojs/display/{$view['id']}/page_1/path", array('path' => 'test/path'), t('Apply'));
    $this->drupalPostForm(NULL, array(), t('Save'));
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
    $this->drupalPostForm($path_prefix . '/page_1', array(), 'Delete Page');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-undo-delete', 'Undo delete of Page', 'Make sure there a undo button on the page display after deleting.');
    $element = $this->xpath('//a[contains(@href, :href) and contains(@class, :class)]', array(':href' => $path_prefix . '/page_1', ':class' => 'views-display-deleted-link'));
    $this->assertTrue(!empty($element), 'Make sure the display link is marked as to be deleted.');

    $element = $this->xpath('//a[contains(@href, :href) and contains(@class, :class)]', array(':href' => $path_prefix . '/page_1', ':class' => 'views-display-deleted-link'));
    $this->assertTrue(!empty($element), 'Make sure the display link is marked as to be deleted.');

    // Undo the deleting of the display.
    $this->drupalPostForm($path_prefix . '/page_1', array(), 'Undo delete of Page');
    $this->assertNoFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-undo-delete', 'Undo delete of Page', 'Make sure there is no undo button on the page display after reverting.');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-delete', 'Delete Page', 'Make sure there is a delete button on the page display after the reverting.');

    // Now delete again and save the view.
    $this->drupalPostForm($path_prefix . '/page_1', array(), 'Delete Page');
    $this->drupalPostForm(NULL, array(), t('Save'));

    $this->assertNoLinkByHref($path_prefix . '/page_1', 'Make sure there is no display tab for the deleted display.');
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
   * Tests the duplicating of a display.
   */
  public function testDuplicateDisplay() {
    $view = $this->randomView();
    $path_prefix = 'admin/structure/views/view/' . $view['id'] .'/edit';
    $path = $view['page[path]'];

    $this->drupalGet($path_prefix);
    $this->drupalPostForm(NULL, array(), 'Duplicate Page');
    $this->assertLinkByHref($path_prefix . '/page_2', 0, 'Make sure after duplicating the new display appears in the UI');
    $this->assertUrl($path_prefix . '/page_2', array(), 'The user got redirected to the new display.');

    // Set the title and override the css classes.
    $random_title = $this->randomMachineName();
    $random_css = $this->randomMachineName();
    $this->drupalPostForm("admin/structure/views/nojs/display/{$view['id']}/page_2/title", array('title' => $random_title), t('Apply'));
    $this->drupalPostForm("admin/structure/views/nojs/display/{$view['id']}/page_2/css_class", array('override[dropdown]' => 'page_2', 'css_class' => $random_css), t('Apply'));

    // Duplicate as a different display type.
    $this->drupalPostForm(NULL, array(), 'Duplicate as Block');
    $this->assertLinkByHref($path_prefix . '/block_1', 0, 'Make sure after duplicating the new display appears in the UI');
    $this->assertUrl($path_prefix . '/block_1', array(), 'The user got redirected to the new display.');
    $this->assertText(t('Block settings'));
    $this->assertNoText(t('Page settings'));

    $this->drupalPostForm(NULL, array(), t('Save'));
    $view = Views::getView($view['id']);
    $view->initDisplay();

    $page_2 = $view->displayHandlers->get('page_2');
    $this->assertTrue($page_2, 'The new page display got saved.');
    $this->assertEqual($page_2->display['display_title'], 'Page');
    $this->assertEqual($page_2->display['display_options']['path'], $path);
    $block_1 = $view->displayHandlers->get('block_1');
    $this->assertTrue($block_1, 'The new block display got saved.');
    $this->assertEqual($block_1->display['display_plugin'], 'block');
    $this->assertEqual($block_1->display['display_title'], 'Block', 'The new display title got generated as expected.');
    $this->assertFalse(isset($block_1->display['display_options']['path']));
    $this->assertEqual($block_1->getOption('title'), $random_title, 'The overridden title option from the display got copied into the duplicate');
    $this->assertEqual($block_1->getOption('css_class'), $random_css, 'The overridden css_class option from the display got copied into the duplicate');
  }

}
