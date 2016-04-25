<?php

namespace Drupal\views_ui\Tests;

/**
 * Tests the user tempstore cache in the UI.
 *
 * @group views_ui
 */
class CachedDataUITest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * Tests the user tempstore views data in the UI.
   */
  public function testCacheData() {
    $views_admin_user_uid = $this->fullAdminUser->id();

    $temp_store = $this->container->get('user.shared_tempstore')->get('views');
    // The view should not be locked.
    $this->assertEqual($temp_store->getMetadata('test_view'), NULL, 'The view is not locked.');

    $this->drupalGet('admin/structure/views/view/test_view/edit');
    // Make sure we have 'changes' to the view.
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/default/title', array(), t('Apply'));
    $this->assertText('You have unsaved changes.');
    $this->assertEqual($temp_store->getMetadata('test_view')->owner, $views_admin_user_uid, 'View cache has been saved.');

    $view_cache = $temp_store->get('test_view');
    // The view should be enabled.
    $this->assertTrue($view_cache->status(), 'The view is enabled.');
    // The view should now be locked.
    $this->assertEqual($temp_store->getMetadata('test_view')->owner, $views_admin_user_uid, 'The view is locked.');

    // Cancel the view edit and make sure the cache is deleted.
    $this->drupalPostForm(NULL, array(), t('Cancel'));
    $this->assertEqual($temp_store->getMetadata('test_view'), NULL, 'User tempstore data has been removed.');
    // Test we are redirected to the view listing page.
    $this->assertUrl('admin/structure/views', array(), 'Redirected back to the view listing page.');

    // Login with another user and make sure the view is locked and break.
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/default/title', array(), t('Apply'));
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/structure/views/view/test_view/edit');
    // Test that save and cancel buttons are not shown.
    $this->assertNoFieldById('edit-actions-submit', t('Save'));
    $this->assertNoFieldById('edit-actions-cancel', t('Cancel'));
    // Test we have the break lock link.
    $this->assertLinkByHref('admin/structure/views/view/test_view/break-lock');
    // Break the lock.
    $this->clickLink(t('break this lock'));
    $this->drupalPostForm(NULL, array(), t('Break lock'));
    // Test that save and cancel buttons are shown.
    $this->assertFieldById('edit-actions-submit', t('Save'));
    $this->assertFieldById('edit-actions-cancel', t('Cancel'));
    // Test we can save the view.
    $this->drupalPostForm('admin/structure/views/view/test_view/edit', array(), t('Save'));
    $this->assertRaw(t('The view %view has been saved.', array('%view' => 'Test view')));

    // Test that a deleted view has no tempstore data.
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/default/title', array(), t('Apply'));
    $this->drupalPostForm('admin/structure/views/view/test_view/delete', array(), t('Delete'));
    // No view tempstore data should be returned for this view after deletion.
    $this->assertEqual($temp_store->getMetadata('test_view'), NULL, 'View tempstore data has been removed after deletion.');
  }

}
