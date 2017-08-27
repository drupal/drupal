<?php

namespace Drupal\Tests\media\Functional;

use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;

/**
 * Ensures that media UI works correctly.
 *
 * @group media
 */
class MediaUiFunctionalTest extends MediaFunctionalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'media_test_source',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');

    // We need to test without any default configuration in place.
    // @TODO: Remove this when https://www.drupal.org/node/2883813 lands.
    MediaType::load('file')->delete();
    MediaType::load('image')->delete();
  }

  /**
   * Tests the media actions (add/edit/delete).
   */
  public function testMediaWithOnlyOneMediaType() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $media_type = $this->createMediaType([
      'new_revision' => FALSE,
      'queue_thumbnail_downloads' => FALSE,
    ]);

    $this->drupalGet('media/add');
    $assert_session->statusCodeEquals(200);
    $assert_session->addressEquals('media/add/' . $media_type->id());
    $assert_session->elementNotExists('css', '#edit-revision');

    // Tests media add form.
    $media_name = $this->randomMachineName();
    $page->fillField('name[0][value]', $media_name);
    $revision_log_message = $this->randomString();
    $page->fillField('revision_log_message[0][value]', $revision_log_message);
    $page->pressButton('Save');
    $media_id = $this->container->get('entity.query')->get('media')->execute();
    $media_id = reset($media_id);
    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->loadUnchanged($media_id);
    $this->assertEquals($media->getRevisionLogMessage(), $revision_log_message);
    $this->assertEquals($media->getName(), $media_name);
    $assert_session->titleEquals($media_name . ' | Drupal');

    // Tests media edit form.
    $media_type->setNewRevision(FALSE);
    $media_type->save();
    $media_name2 = $this->randomMachineName();
    $this->drupalGet('media/' . $media_id . '/edit');
    $assert_session->checkboxNotChecked('edit-revision');
    $media_name = $this->randomMachineName();
    $page->fillField('name[0][value]', $media_name2);
    $page->pressButton('Save');
    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->loadUnchanged($media_id);
    $this->assertEquals($media->getName(), $media_name2);
    $assert_session->titleEquals($media_name2 . ' | Drupal');

    // Test that there is no empty vertical tabs element, if the container is
    // empty (see #2750697).
    // Make the "Publisher ID" and "Created" fields hidden.
    $this->drupalGet('/admin/structure/media/manage/' . $media_type->id() . '/form-display');
    $page->selectFieldOption('fields[created][parent]', 'hidden');
    $page->selectFieldOption('fields[uid][parent]', 'hidden');
    $page->pressButton('Save');
    // Assure we are testing with a user without permission to manage revisions.
    $this->drupalLogin($this->nonAdminUser);
    // Check the container is not present.
    $this->drupalGet('media/' . $media_id . '/edit');
    $assert_session->elementNotExists('css', 'input.vertical-tabs__active-tab');
    // Continue testing as admin.
    $this->drupalLogin($this->adminUser);

    // Enable revisions by default.
    $previous_revision_id = $media->getRevisionId();
    $media_type->setNewRevision(TRUE);
    $media_type->save();
    $this->drupalGet('media/' . $media_id . '/edit');
    $assert_session->checkboxChecked('edit-revision');
    $page->fillField('name[0][value]', $media_name);
    $page->fillField('revision_log_message[0][value]', $revision_log_message);
    $page->pressButton('Save');
    $assert_session->titleEquals($media_name . ' | Drupal');
    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->loadUnchanged($media_id);
    $this->assertEquals($media->getRevisionLogMessage(), $revision_log_message);
    $this->assertNotEquals($previous_revision_id, $media->getRevisionId());

    // Test the status checkbox.
    $this->drupalGet('media/' . $media_id . '/edit');
    $page->uncheckField('status[value]');
    $page->pressButton('Save');
    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->loadUnchanged($media_id);
    $this->assertFalse($media->isPublished());

    // Tests media delete form.
    $this->drupalGet('media/' . $media_id . '/edit');
    $page->clickLink('Delete');
    $assert_session->pageTextContains('This action cannot be undone');
    $page->pressButton('Delete');
    $media_id = \Drupal::entityQuery('media')->execute();
    $this->assertFalse($media_id);
  }

  /**
   * Tests the "media/add" and "media/mid" pages.
   *
   * Tests if the "media/add" page gives you a selecting option if there are
   * multiple media types available.
   */
  public function testMediaWithMultipleMediaTypes() {
    $assert_session = $this->assertSession();

    // Tests and creates the first media type.
    $first_media_type = $this->createMediaType(['description' => $this->randomMachineName(32)]);

    // Test and create a second media type.
    $second_media_type = $this->createMediaType(['description' => $this->randomMachineName(32)]);

    // Test if media/add displays two media type options.
    $this->drupalGet('media/add');

    // Checks for the first media type.
    $assert_session->pageTextContains($first_media_type->label());
    $assert_session->pageTextContains($first_media_type->getDescription());
    // Checks for the second media type.
    $assert_session->pageTextContains($second_media_type->label());
    $assert_session->pageTextContains($second_media_type->getDescription());

    // Continue testing media type filter.
    $first_media_item = Media::create(['bundle' => $first_media_type->id()]);
    $first_media_item->save();
    $second_media_item = Media::create(['bundle' => $second_media_type->id()]);
    $second_media_item->save();

    // Go to first media item.
    $this->drupalGet('media/' . $first_media_item->id());
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains($first_media_item->getName());

    // Go to second media item.
    $this->drupalGet('media/' . $second_media_item->id());
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains($second_media_item->getName());
  }

}
