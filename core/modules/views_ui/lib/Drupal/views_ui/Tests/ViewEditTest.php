<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\ViewEditTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\views\Plugin\Core\Entity\View;

/**
 * Tests some general functionality of editing views, like deleting a view.
 */
class ViewEditTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  public static function getInfo() {
    return array(
      'name' => 'General views edit test',
      'description' => 'Tests some general functionality of editing views, like deleting a view.',
      'group' => 'Views UI'
    );
  }

  /**
   * Tests the delete link on a views UI.
   */
  public function testDeleteLink() {
    $this->drupalGet('admin/structure/views/view/test_view');
    $this->assertLink(t('Delete view'), 0, 'Ensure that the view delete link appears');

    $this->clickLink(t('Delete view'));
    $this->assertUrl('admin/structure/views/view/test_view/delete');
    $this->drupalPostForm(NULL, array(), t('Delete'));

    $this->assertUrl('admin/structure/views');
    $view = $this->container->get('plugin.manager.entity')->getStorageController('view')->load('test_view');
    $this->assertFalse($view instanceof View);
  }

}
