<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the revisions of media entities.
 *
 * @group media
 * @group #slow
 */
class MediaRevisionTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createMediaType('test', ['id' => 'test', 'label' => 'test']);
  }

  /**
   * Creates a media item.
   *
   * @param string $title
   *   Title of media item.
   *
   * @return \Drupal\media\Entity\Media
   *   A media item.
   */
  protected function createMedia(string $title): Media {
    $media = Media::create([
      'bundle' => 'test',
      'name' => $title,
    ]);
    $media->save();

    return $media;
  }

  /**
   * Checks media revision operations.
   */
  public function testRevisions(): void {
    $assert = $this->assertSession();

    $media = $this->createMedia('Sample media');
    $originalRevisionId = $media->getRevisionId();

    // You can access the revision page when there is only 1 revision.
    $this->drupalGet($media->toUrl('revision'));
    $assert->statusCodeEquals(200);

    // Create some revisions.
    $revision_count = 3;
    for ($i = 0; $i < $revision_count; $i++) {
      $media->revision_log = $this->randomMachineName(32);
      $media = $this->createMediaRevision($media);
    }

    // Confirm that the last revision is the default revision.
    $this->assertTrue($media->isDefaultRevision(), 'Last revision is the default.');

    // Get the original revision for simple checks.
    $media = \Drupal::entityTypeManager()->getStorage('media')
      ->loadRevision($originalRevisionId);

    // Test permissions.
    $this->drupalLogin($this->nonAdminUser);
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);

    // Test 'view all media revisions' permission ('view media' permission is
    // needed as well).
    user_role_revoke_permissions($role->id(), [
      'view all media revisions',
    ]);
    $this->drupalGet($media->toUrl('revision'));
    $assert->statusCodeEquals(403);
    $this->grantPermissions($role, ['view any test media revisions']);
    $this->drupalGet($media->toUrl('revision'));
    $assert->statusCodeEquals(200);
    user_role_revoke_permissions($role->id(), ['view any test media revisions']);
    $this->grantPermissions($role, ['view all media revisions']);
    $this->drupalGet($media->toUrl('revision'));
    $assert->statusCodeEquals(200);

    // Confirm the revision page shows the correct title.
    $assert->pageTextContains($media->getName());
  }

  /**
   * Tests creating revisions of a File media item.
   */
  public function testFileMediaRevision(): void {
    $assert = $this->assertSession();

    $uri = 'temporary://foo.txt';
    file_put_contents($uri, $this->randomString(128));

    $this->createMediaType('file', ['id' => 'document', 'new_revision' => TRUE]);

    // Create a media item.
    $this->drupalGet('/media/add/document');
    $page = $this->getSession()->getPage();
    $page->fillField('Name', 'Foobar');
    $page->attachFileToField('File', $this->container->get('file_system')->realpath($uri));
    $page->pressButton('Save');
    $assert->addressEquals('admin/content/media');

    // The media item was just created, so it should only have one revision.
    $media = $this->container
      ->get('entity_type.manager')
      ->getStorage('media')
      ->load(1);
    $this->assertRevisionCount($media, 1);

    // If we edit the item, we should get a new revision.
    $this->drupalGet('/media/1/edit');
    $assert->checkboxChecked('Create new revision');
    $page = $this->getSession()->getPage();
    $page->fillField('Name', 'Foo');
    $page->pressButton('Save');
    $this->assertRevisionCount($media, 2);

    // Confirm the correct revision title appears on "view revisions" page.
    $media = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->loadUnchanged(1);
    $this->drupalGet("media/" . $media->id() . "/revisions/" . $media->getRevisionId() . "/view");
    $assert->pageTextContains('Foo');
  }

  /**
   * Tests creating revisions of an Image media item.
   */
  public function testImageMediaRevision(): void {
    $assert = $this->assertSession();

    $this->createMediaType('image', ['id' => 'image', 'new_revision' => TRUE]);

    /** @var \Drupal\field\FieldConfigInterface $field */
    // Disable the alt text field, because this is not a JavaScript test and
    // the alt text field will therefore not appear without a full page refresh.
    $field = FieldConfig::load('media.image.field_media_image');
    $settings = $field->getSettings();
    $settings['alt_field'] = FALSE;
    $settings['alt_field_required'] = FALSE;
    $field->set('settings', $settings);
    $field->save();

    // Create a media item.
    $this->drupalGet('/media/add/image');
    $page = $this->getSession()->getPage();
    $page->fillField('Name', 'Foobar');
    $page->attachFileToField('Image', $this->root . '/core/modules/media/tests/fixtures/example_1.jpeg');
    $page->pressButton('Save');
    $assert->addressEquals('admin/content/media');

    // The media item was just created, so it should only have one revision.
    $media = $this->container
      ->get('entity_type.manager')
      ->getStorage('media')
      ->load(1);
    $this->assertRevisionCount($media, 1);

    // If we edit the item, we should get a new revision.
    $this->drupalGet('/media/1/edit');
    $assert->checkboxChecked('Create new revision');
    $page = $this->getSession()->getPage();
    $page->fillField('Name', 'Foo');
    $page->pressButton('Save');
    $this->assertRevisionCount($media, 2);

    // Confirm the correct revision title appears on "view revisions" page.
    $media = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->loadUnchanged(1);
    $this->drupalGet("media/" . $media->id() . "/revisions/" . $media->getRevisionId() . "/view");
    $assert->pageTextContains('Foo');
  }

  /**
   * Creates a new revision for a given media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media object.
   *
   * @return \Drupal\media\MediaInterface
   *   A media object with up to date revision information.
   */
  protected function createMediaRevision(MediaInterface $media) {
    $media->setName($this->randomMachineName());
    $media->setNewRevision();
    $media->save();
    return $media;
  }

  /**
   * Asserts that an entity has a certain number of revisions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity in question.
   * @param int $expected_revisions
   *   The expected number of revisions.
   *
   * @internal
   */
  protected function assertRevisionCount(EntityInterface $entity, int $expected_revisions): void {
    $entity_type = $entity->getEntityType();

    $count = $this->container
      ->get('entity_type.manager')
      ->getStorage($entity_type->id())
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->allRevisions()
      ->condition($entity_type->getKey('id'), $entity->id())
      ->execute();

    $this->assertSame($expected_revisions, (int) $count);
  }

  /**
   * Creates a media with a revision.
   *
   * @param \Drupal\media\Entity\Media $media
   *   The media object.
   */
  private function createMediaWithRevision(Media $media): void {
    $media->setNewRevision();
    $media->setName('1st changed title');
    $media->setRevisionLogMessage('first revision');
    // Set revision creation time to check the confirmation message while
    // deleting or reverting a revision.
    $media->setRevisionCreationTime((new \DateTimeImmutable('11 January 2009 4pm'))->getTimestamp());
    $media->save();
  }

  /**
   * Tests deleting a revision.
   */
  public function testRevisionDelete(): void {
    $user = $this->drupalCreateUser([
      'edit any test media',
      'view any test media revisions',
      'delete any test media revisions',
    ]);
    $this->drupalLogin($user);

    $media = $this->createMedia('Sample media');
    $this->createMediaWithRevision($media);
    $originalRevisionId = $media->getRevisionId();

    // Cannot delete latest revision.
    $this->drupalGet($media->toUrl('revision-delete-form'));
    $this->assertSession()->statusCodeEquals(403);

    // Create a new revision.
    $media->setNewRevision();
    $media->setRevisionLogMessage('second revision')
      ->setRevisionCreationTime((new \DateTimeImmutable('12 March 2012 5pm'))->getTimestamp())
      ->setName('Sample media updated')
      ->save();

    $this->drupalGet($media->toUrl('version-history'));
    $this->assertSession()->pageTextContains("First revision");
    $this->assertSession()->pageTextContains("Second revision");
    $this->assertSession()->elementsCount('css', 'table tbody tr', 3);

    // Reload the previous revision, and ensure we can delete it in the UI.
    $revision = \Drupal::entityTypeManager()->getStorage('media')
      ->loadRevision($originalRevisionId);
    $this->drupalGet($revision->toUrl('revision-delete-form'));
    $this->assertSession()->pageTextContains('Are you sure you want to delete the revision from Sun, 01/11/2009 - 16:00?');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextNotContains("First revision");
    $this->assertSession()->pageTextContains("Second revision");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals(sprintf('media/%s/revisions', $media->id()));
    $this->assertSession()->pageTextContains('Revision from Sun, 01/11/2009 - 16:00 of test 1st changed title has been deleted.');
    // Check that only two revisions exists, i.e. the original and the latest
    // revision.
    $this->assertSession()->elementsCount('css', 'table tbody tr', 2);
  }

  /**
   * Tests reverting a revision.
   */
  public function testRevisionRevert(): void {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->drupalCreateUser([
      'edit any test media',
      'view any test media revisions',
      'revert any test media revisions',
    ]);
    $this->drupalLogin($user);

    $media = $this->createMedia('Initial title');
    $this->createMediaWithRevision($media);
    $originalRevisionId = $media->getRevisionId();
    $originalRevisionLabel = $media->getName();

    // Cannot revert latest revision.
    $this->drupalGet($media->toUrl('revision-revert-form'));
    $this->assertSession()->statusCodeEquals(403);

    // Create a new revision.
    $media->setNewRevision();
    $media->setRevisionLogMessage('Second revision')
      ->setRevisionCreationTime((new \DateTimeImmutable('12 March 2012 5pm'))->getTimestamp())
      ->setName('Sample media updated')
      ->save();

    $this->drupalGet($media->toUrl('version-history'));
    $this->assertSession()->pageTextContains("First revision");
    $this->assertSession()->pageTextContains("Second revision");
    $this->assertSession()->elementsCount('css', 'table tbody tr', 3);

    // Reload the previous revision, and ensure we can revert to it in the UI.
    $revision = \Drupal::entityTypeManager()->getStorage('media')
      ->loadRevision($originalRevisionId);
    $this->drupalGet($revision->toUrl('revision-revert-form'));
    $this->assertSession()->pageTextContains('Are you sure you want to revert to the revision from Sun, 01/11/2009 - 16:00?');

    $this->submitForm([], 'Revert');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Copy of the revision from Sun, 01/11/2009 - 16:00');
    $this->assertSession()->addressEquals(sprintf('media/%s/revisions', $media->id()));
    $this->assertSession()->pageTextContains(sprintf('test %s has been reverted to the revision from Sun, 01/11/2009 - 16:00.', $originalRevisionLabel));
    $this->assertSession()->elementsCount('css', 'table tbody tr', 4);
    $this->drupalGet($media->toUrl('edit-form'));
    // Check if the title is changed to the reverted revision.
    $this->assertSession()->pageTextContains('1st changed title');
  }

}
