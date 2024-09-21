<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Functional;

use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Ensures that media UI works correctly.
 *
 * @group media
 */
class MediaUiFunctionalTest extends MediaFunctionalTestBase {

  use FieldUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'media_test_source',
    'media',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the media actions (add/edit/delete).
   */
  public function testMediaWithOnlyOneMediaType(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $media_type = $this->createMediaType('test', [
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
    $source_field = $this->randomString();
    $page->fillField('field_media_test[0][value]', $source_field);
    $page->pressButton('Save');
    $media_id = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->getQuery()
      ->accessCheck(FALSE)
      ->execute();
    $media_id = reset($media_id);
    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->loadUnchanged($media_id);
    $this->assertSame($media->getRevisionLogMessage(), $revision_log_message);
    $this->assertSame($media->getName(), $media_name);

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
    $this->assertSame($media->getName(), $media_name2);

    // Change the authored by field to an empty string, which should assign
    // authorship to the anonymous user (uid 0).
    $this->drupalGet('media/' . $media_id . '/edit');
    $edit['uid[0][target_id]'] = '';
    $this->submitForm($edit, 'Save');
    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->loadUnchanged($media_id);
    $uid = $media->getOwnerId();
    // Most SQL database drivers stringify fetches but entities are not
    // necessarily stored in a SQL database. At the same time, NULL/FALSE/""
    // won't do.
    $this->assertTrue($uid === 0 || $uid === '0', 'Media authored by anonymous user.');

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
    $this->drupalGet('media/' . $media_id);
    $assert_session->statusCodeEquals(404);
    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->loadUnchanged($media_id);
    $this->assertSame($media->getRevisionLogMessage(), $revision_log_message);
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
    $media_id = \Drupal::entityQuery('media')->accessCheck(FALSE)->execute();
    $this->assertEmpty($media_id);
  }

  /**
   * Tests the "media/add" page.
   *
   * Tests if the "media/add" page gives you a selecting option if there are
   * multiple media types available.
   */
  public function testMediaWithMultipleMediaTypes(): void {
    $assert_session = $this->assertSession();

    // Tests and creates the first media type.
    $first_media_type = $this->createMediaType('test', ['description' => $this->randomMachineName()]);

    // Test and create a second media type.
    $second_media_type = $this->createMediaType('test', ['description' => $this->randomMachineName()]);

    // Test if media/add displays two media type options.
    $this->drupalGet('media/add');

    // Checks for the first media type.
    $assert_session->pageTextContains($first_media_type->label());
    $assert_session->pageTextContains($first_media_type->getDescription());
    // Checks for the second media type.
    $assert_session->pageTextContains($second_media_type->label());
    $assert_session->pageTextContains($second_media_type->getDescription());
  }

  /**
   * Tests that media in ER fields use the Rendered Entity formatter by default.
   */
  public function testRenderedEntityReferencedMedia(): void {
    $assert_session = $this->assertSession();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $this->createMediaType('image', ['id' => 'image', 'new_revision' => TRUE]);
    $this->fieldUIAddNewField('/admin/structure/types/manage/page', 'foo_field', 'Foo field', 'field_ui:entity_reference:media', [], ['settings[handler_settings][target_bundles][image]' => TRUE]);
    $this->drupalGet('/admin/structure/types/manage/page/display');
    $assert_session->fieldValueEquals('fields[field_foo_field][type]', 'entity_reference_entity_view');
  }

  /**
   * Tests the redirect URL after creating a media item.
   */
  public function testMediaCreateRedirect(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->createMediaType('test', [
      'queue_thumbnail_downloads' => FALSE,
    ]);

    // Test a redirect to the media canonical URL for a user without the 'access
    // media overview' permission.
    $this->drupalLogin($this->drupalCreateUser([
      'view media',
      'create media',
    ]));
    $this->drupalGet('media/add');
    $page->fillField('name[0][value]', $this->randomMachineName());
    $page->fillField('field_media_test[0][value]', $this->randomString());
    $page->pressButton('Save');
    $media_id = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->getQuery()
      ->accessCheck(FALSE)
      ->execute();
    $media_id = reset($media_id);
    $assert_session->addressEquals("media/$media_id/edit");

    // Test a redirect to the media overview for a user with the 'access media
    // overview' permission.
    $this->drupalLogin($this->drupalCreateUser([
      'view media',
      'create media',
      'access media overview',
    ]));
    $this->drupalGet('media/add');
    $page->fillField('name[0][value]', $this->randomMachineName());
    $page->fillField('field_media_test[0][value]', $this->randomString());
    $page->pressButton('Save');
    $assert_session->addressEquals('admin/content/media');
  }

  /**
   * Tests the media collection route.
   */
  public function testMediaCollectionRoute(): void {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $media_storage */
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');

    $this->container->get('module_installer')->uninstall(['views']);

    // Create a media type and media item.
    $media_type = $this->createMediaType('test');
    $media = $media_storage->create([
      'bundle' => $media_type->id(),
      'name' => 'Unnamed',
    ]);
    $media->save();

    $this->drupalGet($media->toUrl('collection'));

    $assert_session = $this->assertSession();

    // Media list table exists.
    $assert_session->elementExists('css', 'th:contains("Media Name")');
    $assert_session->elementExists('css', 'th:contains("Type")');
    $assert_session->elementExists('css', 'th:contains("Operations")');
    // Media item is present.
    $assert_session->elementExists('css', 'td:contains("Unnamed")');
  }

}
