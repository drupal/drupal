<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\media\Entity\Media;

/**
<<<<<<< HEAD
 * Tests link relationships for media items.
=======
 * Tests link relationships for Media entity.
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
 *
 * @group media
 */
class MediaLinkRelationsTest extends MediaKernelTestBase {

  /**
   * Tests that all link relationships for Media exist.
   */
  public function testExistLinkRelationships() {
    /** @var \Drupal\Core\Http\LinkRelationTypeManager $link_relation_type_manager */
    $link_relation_type_manager = $this->container->get('plugin.manager.link_relation_type');
    $media = Media::create(['bundle' => $this->testMediaType->id()]);
    $media->save();
    foreach ($media->uriRelationships() as $relation_name) {
<<<<<<< HEAD
      $this->assertTrue($link_relation_type_manager->hasDefinition($relation_name), "Link relationship '{$relation_name}' for a media item");
=======
      $this->assertTrue($link_relation_type_manager->hasDefinition($relation_name), "Link relationship '{$relation_name}' for Media entity");
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
    }
  }

}
