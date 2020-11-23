<?php

namespace Drupal\Tests\views_ui\Functional;

/**
 * Tests the shared tempstore cache in the UI.
 *
 * @group views_ui
 */
class CachedDataUITest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the shared tempstore views data in the UI.
   */
  public function testCacheData() {
    $views_admin_user_uid = $this->fullAdminUser->id();

    $temp_store = $this->container->get('tempstore.shared')->get('views');
    // The view should not be locked.
    $this->assertEqual($temp_store->getMetadata('test_view'), NULL, 'The view is not locked.');

    $this->drupalGet('admin/structure/views/view/test_view/edit');
    // Make sure we have 'changes' to the view.
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/default/title', [], 'Apply');
    $this->assertText('You have unsaved changes.');
    $this->assertEqual($temp_store->getMetadata('test_view')->getOwnerId(), $views_admin_user_uid, 'View cache has been saved.');

    $view_cache = $temp_store->get('test_view');
    // The view should be enabled.
    $this->assertTrue($view_cache->status(), 'The view is enabled.');
    // The view should now be locked.
    $this->assertEqual($temp_store->getMetadata('test_view')->getOwnerId(), $views_admin_user_uid, 'The view is locked.');

    // Cancel the view edit and make sure the cache is deleted.
    $this->submitForm([], 'Cancel');
    $this->assertEqual($temp_store->getMetadata('test_view'), NULL, 'Shared tempstore data has been removed.');
    // Test we are redirected to the view listing page.
    $this->assertSession()->addressEquals('admin/structure/views');

    // Log in with another user and make sure the view is locked and break.
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/default/title', [], 'Apply');
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/structure/views/view/test_view/edit');
    // Test that save and cancel buttons are not shown.
    $this->assertSession()->buttonNotExists('Save');
    $this->assertSession()->buttonNotExists('Cancel');
    // Test we have the break lock link.
    $this->assertSession()->linkByHrefExists('admin/structure/views/view/test_view/break-lock');
    // Break the lock.
    $this->clickLink(t('break this lock'));
    $this->submitForm([], 'Break lock');
    // Test that save and cancel buttons are shown.
    $this->assertSession()->buttonExists('Save');
    $this->assertSession()->buttonExists('Cancel');
    // Test we can save the view.
    $this->drupalPostForm('admin/structure/views/view/test_view/edit', [], 'Save');
    $this->assertRaw(t('The view %view has been saved.', ['%view' => 'Test view']));

    // Test that a deleted view has no tempstore data.
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/default/title', [], 'Apply');
    $this->drupalPostForm('admin/structure/views/view/test_view/delete', [], 'Delete');
    // No view tempstore data should be returned for this view after deletion.
    $this->assertEqual($temp_store->getMetadata('test_view'), NULL, 'View tempstore data has been removed after deletion.');
  }

}
