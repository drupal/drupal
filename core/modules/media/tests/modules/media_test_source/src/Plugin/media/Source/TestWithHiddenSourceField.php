<?php

namespace Drupal\media_test_source\Plugin\media\Source;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\media\MediaTypeInterface;

/**
 * Provides test media source.
 *
 * @MediaSource(
 *   id = "test_hidden_source_field",
 *   label = @Translation("Test source with hidden source field"),
 *   description = @Translation("Test media source with hidden source field."),
 *   allowed_field_types = {"string"},
 * )
 */
class TestWithHiddenSourceField extends Test {

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display) {
    $display->removeComponent($this->getSourceFieldDefinition($type)->getName());
  }

  /**
   * {@inheritdoc}
   */
  public function prepareFormDisplay(MediaTypeInterface $type, EntityFormDisplayInterface $display) {
    $display->removeComponent($this->getSourceFieldDefinition($type)->getName());
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceFieldName() {
    return 'field_media_hidden';
  }

}
