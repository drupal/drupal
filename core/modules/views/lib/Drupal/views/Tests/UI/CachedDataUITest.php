<?php

/**
 * @file
 * Contains \Drupal\views\Tests\UI\CachedDataUITest.
 */

namespace Drupal\views\Tests\UI;

/**
 * Tests the user tempstore cache in the UI.
 */
class CachedDataUITest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  public static function getInfo() {
    return array(
      'name' => 'Cached data',
      'description' => 'Tests the user tempstore object caching in the UI.',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests the user tempstore views data in the UI.
   */
  public function testCacheData() {
    $controller = $this->container->get('views_ui.controller');
    $view = entity_load('view', 'test_view');

    $view_cache = $controller->getViewUI($view);
    // The view should not be locked.
    $this->assertFalse($view_cache->locked, 'The view is not locked.');

    $this->drupalGet('admin/structure/views/view/test_view/edit');
    // Make sure we have 'changes' to the view.
    $this->drupalPost('admin/structure/views/nojs/display/test_view/default/title', array(), t('Apply'));
    $this->assertText('* All changes are stored temporarily. Click Save to make your changes permanent. Click Cancel to discard your changes.', 'The view has been changed.');

    $view_cache = $controller->getViewUI($view);
    // The view should be enabled.
    $this->assertTrue($view_cache->status(), ' The view is enabled.');
    // The view should now be locked.
    $this->assertTrue($view_cache->locked, 'The view is locked.');

    // Change the status of the view.
    $view->disable()->save();

    // Load the tempstore data again and check test the status.
    $view_cache = $controller->getViewUI($view);
    // The view should be disabled.
    $this->assertFalse($view_cache->status(), 'The cached view is disabled.');

    // Login with another user and make sure the view is locked and break.
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/structure/views/view/test_view/edit');
    // Test we have the break lock link.
    $this->assertLinkByHref('admin/structure/views/view/test_view/break-lock');
    // Break the lock.
    $this->clickLink(t('break this lock'));
    // Test we can save the view.
    $this->drupalPost('admin/structure/views/view/test_view/edit', array(), t('Save'));
    $this->assertText(t('The view test_view has been saved.'));
  }

}
