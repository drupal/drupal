<?php

namespace Drupal\media_test_source\Plugin\media\Source;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\media\MediaTypeInterface;

/**
 * Provides test media source.
 *
 * @MediaSource(
 *   id = "test_different_displays",
 *   label = @Translation("Test source with different displays"),
 *   description = @Translation("Test source with different displays."),
 *   allowed_field_types = {"entity_reference"},
 * )
 */
class TestDifferentDisplays extends Test {

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display) {
    $display->setComponent($this->getSourceFieldDefinition($type)->getName(), [
      'type' => 'entity_reference_entity_id',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareFormDisplay(MediaTypeInterface $type, EntityFormDisplayInterface $display) {
    $display->setComponent($this->getSourceFieldDefinition($type)->getName(), [
      'type' => 'entity_reference_autocomplete_tags',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceFieldName() {
    return 'field_media_different_display';
  }

}
