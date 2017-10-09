<?php

namespace Drupal\Tests\media\Kernel;

/**
 * Tests the file media source.
 *
 * @group media
 */
class MediaSourceFileTest extends MediaKernelTestBase {

  /**
   * Tests the file extension constraint.
   */
  public function testFileExtensionConstraint() {
    $mediaType = $this->createMediaType('file');
    // Create a random file that should fail.
    $media = $this->generateMedia('test.patch', $mediaType);
    $result = $media->validate();
    $this->assertCount(1, $result);
    $this->assertEquals('field_media_file.0', $result->get(0)->getPropertyPath());
    $this->assertContains('Only files with the following extensions are allowed:', (string) $result->get(0)->getMessage());

    // Create a random file that should pass.
    $media = $this->generateMedia('test.txt', $mediaType);
    $result = $media->validate();
    $this->assertCount(0, $result);
  }

}
