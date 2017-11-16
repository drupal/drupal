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

  /**
   * Ensure media name is configurable on manage display.
   */
  public function testNameIsConfigurable() {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $field_definitions */
    $field_definitions = $this->container->get('entity_field.manager')
      ->getBaseFieldDefinitions('media');

    $this->assertTrue($field_definitions['name']->isDisplayConfigurable('view'));
  }

}
