<?php

namespace Drupal\Tests\media\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Tests the revisionability of media entities.
 *
 * @group media
 */
class MediaRevisionTest extends MediaFunctionalTestBase {

  /**
   * Tests creating revisions of a File media item.
   */
  public function testFileMediaRevision() {
    $assert = $this->assertSession();

    $uri = 'temporary://foo.txt';
    file_put_contents($uri, $this->randomString(128));

    // Create a media item.
    $this->drupalGet('/media/add/file');
    $page = $this->getSession()->getPage();
    $page->fillField('Name', 'Foobar');
    $page->attachFileToField('File', $this->container->get('file_system')->realpath($uri));
    $page->pressButton('Save and publish');
    $assert->addressMatches('/^\/media\/[0-9]+$/');

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
    $page->fillField('Name', 'Foobaz');
    $page->pressButton('Save and keep published');
    $this->assertRevisionCount($media, 2);
  }

  /**
   * Tests creating revisions of a Image media item.
   */
  public function testImageMediaRevision() {
    $assert = $this->assertSession();

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
    $page->attachFileToField('Image', \Drupal::root() . '/core/modules/media/tests/fixtures/example_1.jpeg');
    $page->pressButton('Save and publish');
    $assert->addressMatches('/^\/media\/[0-9]+$/');

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
    $page->fillField('Name', 'Foobaz');
    $page->pressButton('Save and keep published');
    $this->assertRevisionCount($media, 2);
  }

  /**
   * Asserts that an entity has a certain number of revisions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity in question.
   * @param int $expected_revisions
   *   The expected number of revisions.
   */
  protected function assertRevisionCount(EntityInterface $entity, $expected_revisions) {
    $entity_type = $entity->getEntityType();

    $count = $this->container
      ->get('entity.query')
      ->get($entity_type->id())
      ->count()
      ->allRevisions()
      ->condition($entity_type->getKey('id'), $entity->id())
      ->execute();

    $this->assertSame($expected_revisions, (int) $count);
  }

}
