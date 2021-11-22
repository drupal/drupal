<?php

namespace Drupal\Tests\media\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\media\MediaInterface;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the revisionability of media entities.
 *
 * @group media
 */
class MediaRevisionTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Checks media revision operations.
   */
  public function testRevisions() {
    $assert = $this->assertSession();

    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $media_storage */
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');

    // Create a media type and media item.
    $media_type = $this->createMediaType('test');
    $media = $media_storage->create([
      'bundle' => $media_type->id(),
      'name' => 'Unnamed',
    ]);
    $media->save();

    // You can access the revision page when there is only 1 revision.
    $this->drupalGet('media/' . $media->id() . '/revisions/' . $media->getRevisionId() . '/view');
    $assert->statusCodeEquals(200);

    // Create some revisions.
    $media_revisions = [];
    $media_revisions[] = clone $media;
    $revision_count = 3;
    for ($i = 0; $i < $revision_count; $i++) {
      $media->revision_log = $this->randomMachineName(32);
      $media = $this->createMediaRevision($media);
      $media_revisions[] = clone $media;
    }

    // Get the last revision for simple checks.
    /** @var \Drupal\media\MediaInterface $media */
    $media = end($media_revisions);

    // Test permissions.
    $this->drupalLogin($this->nonAdminUser);
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);

    // Test 'view all media revisions' permission ('view media' permission is
    // needed as well).
    user_role_revoke_permissions($role->id(), ['view media', 'view all media revisions']);
    $this->drupalGet('media/' . $media->id() . '/revisions/' . $media->getRevisionId() . '/view');
    $assert->statusCodeEquals(403);
    $this->grantPermissions($role, ['view media', 'view all media revisions']);
    $this->drupalGet('media/' . $media->id() . '/revisions/' . $media->getRevisionId() . '/view');
    $assert->statusCodeEquals(200);

    // Confirm the revision page shows the correct title.
    $assert->pageTextContains($media->getName());

    // Confirm that the last revision is the default revision.
    $this->assertTrue($media->isDefaultRevision(), 'Last revision is the default.');
  }

  /**
   * Tests creating revisions of a File media item.
   */
  public function testFileMediaRevision() {
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
  public function testImageMediaRevision() {
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

}
