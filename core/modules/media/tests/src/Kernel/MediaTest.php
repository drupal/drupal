<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\media\Entity\Media;

/**
 * Tests Media.
 *
 * @group media
 */
class MediaTest extends MediaKernelTestBase {

  /**
   * Tests various aspects of a Media entity.
   */
  public function testEntity() {
    $media = Media::create(['bundle' => $this->testMediaType->id()]);

    $this->assertSame($media, $media->setOwnerId($this->user->id()), 'setOwnerId() method returns its own entity.');
  }

}
