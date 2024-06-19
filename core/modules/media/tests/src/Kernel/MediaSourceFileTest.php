<?php

declare(strict_types=1);

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
  public function testFileExtensionConstraint(): void {
    $mediaType = $this->createMediaType('file');
    // Create a random file that should fail.
    $media = $this->generateMedia('test.patch', $mediaType);
    $result = $media->validate();
    $this->assertCount(1, $result);
    $this->assertSame('field_media_file.0', $result->get(0)->getPropertyPath());
    $this->assertStringContainsString('Only files with the following extensions are allowed:', (string) $result->get(0)->getMessage());

    // Create a random file that should pass.
    $media = $this->generateMedia('test.txt', $mediaType);
    $result = $media->validate();
    $this->assertCount(0, $result);
  }

  /**
   * Tests a media file can be deleted.
   */
  public function testFileDeletion(): void {
    $mediaType = $this->createMediaType('file');
    $media = $this->generateMedia('test.txt', $mediaType);
    $media->save();

    $source_field_name = $mediaType->getSource()
      ->getSourceFieldDefinition($mediaType)
      ->getName();
    /** @var \Drupal\file\FileInterface $file */
    $file = $media->get($source_field_name)->entity;
    $file->delete();
    $this->assertEmpty($this->container->get('entity_type.manager')->getStorage('file')->loadByProperties(['filename' => 'test.txt']));
  }

}
