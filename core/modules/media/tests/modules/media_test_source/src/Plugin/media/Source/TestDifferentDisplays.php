<?php

namespace Drupal\media_test_source\Plugin\media\Source;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\Attribute\MediaSource;
use Drupal\media\MediaTypeInterface;

/**
 * Provides test media source.
 */
#[MediaSource(
  id: "test_different_displays",
  label: new TranslatableMarkup("Test source with different displays"),
  description: new TranslatableMarkup("Test source with different displays."),
  allowed_field_types: ["entity_reference"]
)]
class TestDifferentDisplays extends Test {

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display) {
    parent::prepareViewDisplay($type, $display);
    $source_name = $this->getSourceFieldDefinition($type)->getName();
    $source_component = $display->getComponent($source_name) ?: [];
    $source_component['type'] = 'entity_reference_entity_id';
    $display->setComponent($source_name, $source_component);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareFormDisplay(MediaTypeInterface $type, EntityFormDisplayInterface $display) {
    parent::prepareFormDisplay($type, $display);
    $source_name = $this->getSourceFieldDefinition($type)->getName();
    $source_component = $display->getComponent($source_name) ?: [];
    $source_component['type'] = 'entity_reference_autocomplete_tags';
    $display->setComponent($source_name, $source_component);
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceFieldName() {
    return 'field_media_different_display';
  }

}
