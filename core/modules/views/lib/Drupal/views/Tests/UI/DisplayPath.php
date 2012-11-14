<?php

/**
 * @file
 * Contains \Drupal\views\Tests\UI\DisplayPath
 */

namespace Drupal\views\Tests\UI;

/**
 * Tests the UI of generic display path plugin.
 *
 * @see \Drupal\views\Plugin\views\display\PathPluginBase
 */
class DisplayPath extends UITestBase {

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

    // Save a path and make sure the summary appears as expected.
    $random_path = $this->randomName();
    $this->drupalPost("admin/structure/views/nojs/display/test_view/page_1/path", array('path' => $random_path), t('Apply'));
    $this->assertText('/' . $random_path, 'The custom path appears in the summary.');
  }

}

