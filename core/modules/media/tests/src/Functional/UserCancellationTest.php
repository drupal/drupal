<?php

namespace Drupal\Tests\media\Functional;

use Drupal\media\Entity\Media;
use Drupal\Tests\user\Traits\UserCancellationTrait;
use Drupal\user\CancellationHandlerInterface;
use Drupal\user\UserInterface;

/**
 * Tests how media items react to user cancellation.
 *
 * @group media
 */
class UserCancellationTest extends MediaFunctionalTestBase {

  use UserCancellationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests how media items react to user cancellation.
   */
  public function testUserCancellation(): void {
    $this->createMediaType('test', [
      'id' => 'test',
    ]);
    $alice = $this->drupalCreateUser();
    $bob = $this->drupalCreateUser();
    $charlie = $this->drupalCreateUser();

    $alice_media = $this->createMediaForUser($alice);
    $bob_media = $this->createMediaForUser($bob);
    $charlie_media = $this->createMediaForUser($charlie);

    $original_charlie_media_vid = $charlie_media->getRevisionId();
    $charlie_media->setNewRevision();
    $charlie_media->save();

    $this->drupalLogin($this->rootUser);
    $this->cancelUser($alice, CancellationHandlerInterface::METHOD_BLOCK_UNPUBLISH);
    $alice_media = Media::load($alice_media->id());
    $this->assertFalse($alice_media->isPublished());
    $this->assertSame($alice->id(), $alice_media->getOwnerId());

    $this->cancelUser($bob, CancellationHandlerInterface::METHOD_REASSIGN);
    $bob_media = Media::load($bob_media->id());
    $this->assertTrue($bob_media->isPublished());
    $this->assertTrue($bob_media->getOwner()->isAnonymous());

    $this->cancelUser($charlie, CancellationHandlerInterface::METHOD_DELETE);
    /** @var \Drupal\media\MediaStorage $media_storage */
    $media_storage = $this->container->get('entity_type.manager')
      ->getStorage('media');
    $this->assertNull($media_storage->load($charlie_media->id()));
    $this->assertNull($media_storage->loadRevision($original_charlie_media_vid));
  }

  protected function createMediaForUser(UserInterface $user): Media {
    $media = Media::create([
      'bundle' => 'test',
      'uid' => $user->id(),
    ]);
    $media->save();
    $this->assertSame($user->id(), $media->getOwnerId());
    $this->assertTrue($media->isPublished());
    return $media;
  }

}
