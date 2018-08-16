<?php

namespace Drupal\Tests\media\Functional;

use Drupal\media\Entity\Media;
use Drupal\views\Views;

/**
 * Tests a media bulk form.
 *
 * @group media
 */
class MediaBulkFormTest extends MediaFunctionalTestBase {

  /**
   * Modules to be enabled.
   *
   * @var array
   */
  public static $modules = ['media_test_views'];

  /**
   * The test media type.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $testMediaType;

  /**
   * The test media items.
   *
   * @var \Drupal\media\MediaInterface[]
   */
  protected $mediaItems;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->testMediaType = $this->createMediaType('test');

    // Create some test media items.
    $this->mediaItems = [];
    for ($i = 1; $i <= 5; $i++) {
      $media = Media::create([
        'bundle' => $this->testMediaType->id(),
      ]);
      $media->save();
      $this->mediaItems[] = $media;
    }
  }

  /**
   * Tests the media bulk form.
   */
  public function testBulkForm() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Check that all created items are present in the test view.
    $view = Views::getView('test_media_bulk_form');
    $view->execute();
    $this->assertSame($view->total_rows, 5);

    // Check the operations are accessible to the logged in user.
    $this->drupalGet('test-media-bulk-form');
    // Current available actions: Delete, Save, Publish, Unpublish.
    $available_actions = [
      'media_delete_action',
      'media_publish_action',
      'media_save_action',
      'media_unpublish_action',
    ];
    foreach ($available_actions as $action_name) {
      $assert_session->optionExists('action', $action_name);
    }

    // Test unpublishing in bulk.
    $page->checkField('media_bulk_form[0]');
    $page->checkField('media_bulk_form[1]');
    $page->checkField('media_bulk_form[2]');
    $page->selectFieldOption('action', 'media_unpublish_action');
    $page->pressButton('Apply to selected items');
    $assert_session->pageTextContains('Unpublish media was applied to 3 items');
    $this->assertFalse($this->storage->loadUnchanged(1)->isPublished(), 'The unpublish action failed in some of the media items.');
    $this->assertFalse($this->storage->loadUnchanged(2)->isPublished(), 'The unpublish action failed in some of the media items.');
    $this->assertFalse($this->storage->loadUnchanged(3)->isPublished(), 'The unpublish action failed in some of the media items.');

    // Test publishing in bulk.
    $page->checkField('media_bulk_form[0]');
    $page->checkField('media_bulk_form[1]');
    $page->selectFieldOption('action', 'media_publish_action');
    $page->pressButton('Apply to selected items');
    $assert_session->pageTextContains('Publish media was applied to 2 items');
    $this->assertTrue($this->storage->loadUnchanged(1)->isPublished(), 'The publish action failed in some of the media items.');
    $this->assertTrue($this->storage->loadUnchanged(2)->isPublished(), 'The publish action failed in some of the media items.');

    // Test deletion in bulk.
    $page->checkField('media_bulk_form[0]');
    $page->checkField('media_bulk_form[1]');
    $page->selectFieldOption('action', 'media_delete_action');
    $page->pressButton('Apply to selected items');
    $assert_session->pageTextContains('Are you sure you want to delete these media items?');
    $page->pressButton('Delete');
    $assert_session->pageTextContains('Deleted 2 items.');
    $this->assertNull($this->storage->loadUnchanged(1), 'Could not delete some of the media items.');
    $this->assertNull($this->storage->loadUnchanged(2), 'Could not delete some of the media items.');
  }

}
