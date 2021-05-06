<?php

namespace Drupal\Tests\media\Functional;

use Drupal\media\Entity\Media;
use Drupal\Tests\user\Traits\UserCancellationTrait;
use Drupal\user\CancellationHandlerInterface;

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
    $media_type = $this->createMediaType('test');
    $alice = $this->drupalCreateUser();
    $bob = $this->drupalCreateUser();

    $aliceMedia = Media::create([
      'bundle' => $media_type->id(),
      'uid' => $alice->id(),
    ]);
    $aliceMedia->save();
    $this->assertSame($alice->id(), $aliceMedia->getOwnerId());
    $this->assertTrue($aliceMedia->isPublished());

    $bobMedia = Media::create([
      'bundle' => $media_type->id(),
      'uid' => $bob->id(),
    ]);
    $bobMedia->save();
    $this->assertSame($bob->id(), $bobMedia->getOwnerId());
    $this->assertTrue($bobMedia->isPublished());

    $this->drupalLogin($this->rootUser);
    $this->cancelUser($alice, CancellationHandlerInterface::METHOD_BLOCK_UNPUBLISH);
    $aliceMedia = Media::load($aliceMedia->id());
    $this->assertFalse($aliceMedia->isPublished());
    $this->assertSame($alice->id(), $aliceMedia->getOwnerId());

    $this->cancelUser($bob, CancellationHandlerInterface::METHOD_REASSIGN);
    $bobMedia = Media::load($bobMedia->id());
    $this->assertTrue($bobMedia->isPublished());
    $this->assertTrue($bobMedia->getOwner()->isAnonymous());
  }

}
