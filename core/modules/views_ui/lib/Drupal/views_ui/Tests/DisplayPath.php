<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\DisplayPath
 */

namespace Drupal\views_ui\Tests;

/**
 * Tests the UI of generic display path plugin.
 *
 * @see \Drupal\views\Plugin\views\display\PathPluginBase
 */
class DisplayPath extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  public static function getInfo() {
    return array(
      'name' => 'Display Path: UI',
      'description' => 'Tests the UI of generic display path plugin.',
      'group' => 'Views UI',
    );
  }

  public function testPathUI() {
    $this->drupalGet('admin/structure/views/view/test_view');

    // Add a new page display and check the appearing text.
    $this->drupalPost(NULL, array(), 'Add Page');
    $this->assertText(t('No path is set'), 'The right text appears if no path was set.');
    $this->assertNoLink(t('View @display', array('@display' => 'page')), 'No view page link found on the page.');

    // Save a path and make sure the summary appears as expected.
    $random_path = $this->randomName();
    $this->drupalPost("admin/structure/views/nojs/display/test_view/page_1/path", array('path' => $random_path), t('Apply'));
    $this->assertText('/' . $random_path, 'The custom path appears in the summary.');
    $this->assertLink(t('View @display', array('@display' => 'Page')), 0, 'view page link found on the page.');
  }

  /**
   * Tests deleting a page display that has no path.
   */
  public function testDeleteWithNoPath() {
    $this->drupalGet('admin/structure/views/view/test_view');
    $this->drupalPost(NULL, array(), t('Add Page'));
    $this->drupalPost(NULL, array(), t('Delete Page'));
    $this->drupalPost(NULL, array(), t('Save'));
    $this->assertRaw(t('The view %view has been saved.', array('%view' => 'Test view')));
  }

}
