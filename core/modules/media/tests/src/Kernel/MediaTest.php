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
<<<<<<< HEAD
   * Tests various aspects of a media item.
=======
   * Tests various aspects of a Media entity.
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
   */
  public function testEntity() {
    $media = Media::create(['bundle' => $this->testMediaType->id()]);

    $this->assertSame($media, $media->setOwnerId($this->user->id()), 'setOwnerId() method returns its own entity.');
  }

  /**
<<<<<<< HEAD
   * Tests the Media "name" base field behavior.
   */
  public function testNameBaseField() {
=======
   * Ensure media name is configurable on manage display.
   */
  public function testNameIsConfigurable() {
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $field_definitions */
    $field_definitions = $this->container->get('entity_field.manager')
      ->getBaseFieldDefinitions('media');

<<<<<<< HEAD
    // Ensure media name is configurable on manage display.
    $this->assertTrue($field_definitions['name']->isDisplayConfigurable('view'));
    // Ensure it is not visible by default.
    $this->assertEquals($field_definitions['name']->getDisplayOptions('view'), ['region' => 'hidden']);
=======
    $this->assertTrue($field_definitions['name']->isDisplayConfigurable('view'));
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
  }

}
