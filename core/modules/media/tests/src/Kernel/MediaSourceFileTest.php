<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\media\Entity\MediaType;

/**
 * Tests the file media source.
 *
 * @group media
 */
class MediaSourceFileTest extends MediaKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // We need to test without any default configuration in place.
    // @TODO: Remove this as part of https://www.drupal.org/node/2883813.
    MediaType::load('file')->delete();
    MediaType::load('image')->delete();
  }

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
