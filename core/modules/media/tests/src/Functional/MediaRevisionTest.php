<?php

namespace Drupal\Tests\media\Functional;

use Drupal\Core\Entity\EntityInterface;

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
