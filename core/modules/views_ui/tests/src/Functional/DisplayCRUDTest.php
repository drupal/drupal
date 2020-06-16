<?php

namespace Drupal\Tests\views_ui\Functional;

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
  public static $testViews = ['test_display'];

  /**
   * Modules to enable
   *
   * @var array
   */
  public static $modules = ['contextual'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests adding a display.
   */
  public function testAddDisplay() {
    // Show the master display.
    $this->config('views.settings')->set('ui.show.master_display', TRUE)->save();

    $settings['page[create]'] = FALSE;
    $view = $this->randomView($settings);

    $path_prefix = 'admin/structure/views/view/' . $view['id'] . '/edit';
    $this->drupalGet($path_prefix);

    // Add a new display.
    $this->drupalPostForm(NULL, [], 'Add Page');
    $this->assertLinkByHref($path_prefix . '/page_1', 0, 'Make sure after adding a display the new display appears in the UI');

    $this->assertSession()->linkNotExists('Master*', 'Make sure the master display is not marked as changed.');
    $this->assertSession()->linkExists('Page*', 0, 'Make sure the added display is marked as changed.');

    $this->drupalPostForm("admin/structure/views/nojs/display/{$view['id']}/page_1/path", ['path' => 'test/path'], t('Apply'));
    $this->drupalPostForm(NULL, [], t('Save'));
  }

  /**
   * Tests removing a display.
   */
  public function testRemoveDisplay() {
    $view = $this->randomView();
    $path_prefix = 'admin/structure/views/view/' . $view['id'] . '/edit';

    $this->drupalGet($path_prefix . '/default');
    $this->assertNoFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-delete', 'Delete Page', 'Make sure there is no delete button on the default display.');

    $this->drupalGet($path_prefix . '/page_1');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-delete', 'Delete Page', 'Make sure there is a delete button on the page display.');

    // Delete the page, so we can test the undo process.
    $this->drupalPostForm($path_prefix . '/page_1', [], 'Delete Page');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-undo-delete', 'Undo delete of Page', 'Make sure there a undo button on the page display after deleting.');
    $element = $this->xpath('//a[contains(@href, :href) and contains(@class, :class)]', [':href' => $path_prefix . '/page_1', ':class' => 'views-display-deleted-link']);
    $this->assertTrue(!empty($element), 'Make sure the display link is marked as to be deleted.');

    $element = $this->xpath('//a[contains(@href, :href) and contains(@class, :class)]', [':href' => $path_prefix . '/page_1', ':class' => 'views-display-deleted-link']);
    $this->assertTrue(!empty($element), 'Make sure the display link is marked as to be deleted.');

    // Undo the deleting of the display.
    $this->drupalPostForm($path_prefix . '/page_1', [], 'Undo delete of Page');
    $this->assertNoFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-undo-delete', 'Undo delete of Page', 'Make sure there is no undo button on the page display after reverting.');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-delete', 'Delete Page', 'Make sure there is a delete button on the page display after the reverting.');

    // Now delete again and save the view.
    $this->drupalPostForm($path_prefix . '/page_1', [], 'Delete Page');
    $this->drupalPostForm(NULL, [], t('Save'));

    $this->assertNoLinkByHref($path_prefix . '/page_1', 'Make sure there is no display tab for the deleted display.');

    // Test deleting a display that has a modified machine name.
    $view = $this->randomView();
    $machine_name = 'new_machine_name';
    $path_prefix = 'admin/structure/views/view/' . $view['id'] . '/edit';
    $this->drupalPostForm("admin/structure/views/nojs/display/{$view['id']}/page_1/display_id", ['display_id' => $machine_name], 'Apply');
    $this->drupalPostForm(NULL, [], 'Delete Page');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoLinkByHref($path_prefix . '/new_machine_name', 'Make sure there is no display tab for the deleted display.');
  }

  /**
   * Tests that the correct display is loaded by default.
   */
  public function testDefaultDisplay() {
    $this->drupalGet('admin/structure/views/view/test_display');
    $elements = $this->xpath('//*[@id="views-page-1-display-title"]');
    $this->assertCount(1, $elements, 'The page display is loaded as the default display.');
  }

  /**
   * Tests the duplicating of a display.
   */
  public function testDuplicateDisplay() {
    $view = $this->randomView();
    $path_prefix = 'admin/structure/views/view/' . $view['id'] . '/edit';
    $path = $view['page[path]'];

    $this->drupalGet($path_prefix);
    $this->drupalPostForm(NULL, [], 'Duplicate Page');
    $this->assertLinkByHref($path_prefix . '/page_2', 0, 'Make sure after duplicating the new display appears in the UI');
    $this->assertUrl($path_prefix . '/page_2', [], 'The user got redirected to the new display.');

    // Set the title and override the css classes.
    $random_title = $this->randomMachineName();
    $random_css = $this->randomMachineName();
    $this->drupalPostForm("admin/structure/views/nojs/display/{$view['id']}/page_2/title", ['title' => $random_title], t('Apply'));
    $this->drupalPostForm("admin/structure/views/nojs/display/{$view['id']}/page_2/css_class", ['override[dropdown]' => 'page_2', 'css_class' => $random_css], t('Apply'));

    // Duplicate as a different display type.
    $this->drupalPostForm(NULL, [], 'Duplicate as Block');
    $this->assertLinkByHref($path_prefix . '/block_1', 0, 'Make sure after duplicating the new display appears in the UI');
    $this->assertUrl($path_prefix . '/block_1', [], 'The user got redirected to the new display.');
    $this->assertText(t('Block settings'));
    $this->assertNoText(t('Page settings'));

    $this->drupalPostForm(NULL, [], t('Save'));
    $view = Views::getView($view['id']);
    $view->initDisplay();

    $page_2 = $view->displayHandlers->get('page_2');
    $this->assertNotEmpty($page_2, 'The new page display got saved.');
    $this->assertEqual($page_2->display['display_title'], 'Page');
    $this->assertEqual($page_2->display['display_options']['path'], $path);
    $block_1 = $view->displayHandlers->get('block_1');
    $this->assertNotEmpty($block_1, 'The new block display got saved.');
    $this->assertEqual($block_1->display['display_plugin'], 'block');
    $this->assertEqual($block_1->display['display_title'], 'Block', 'The new display title got generated as expected.');
    $this->assertFalse(isset($block_1->display['display_options']['path']));
    $this->assertEqual($block_1->getOption('title'), $random_title, 'The overridden title option from the display got copied into the duplicate');
    $this->assertEqual($block_1->getOption('css_class'), $random_css, 'The overridden css_class option from the display got copied into the duplicate');

    // Test duplicating a display after changing the machine name.
    $view_id = $view->id();
    $this->drupalPostForm("admin/structure/views/nojs/display/$view_id/page_2/display_id", ['display_id' => 'page_new'], 'Apply');
    $this->drupalPostForm(NULL, [], 'Duplicate as Block');
    $this->drupalPostForm(NULL, [], t('Save'));
    $view = Views::getView($view_id);
    $view->initDisplay();
    $this->assertNotNull($view->displayHandlers->get('page_new'), 'The original display is saved with a changed id');
    $this->assertNotNull($view->displayHandlers->get('block_2'), 'The duplicate display is saved with new id');
  }

}
