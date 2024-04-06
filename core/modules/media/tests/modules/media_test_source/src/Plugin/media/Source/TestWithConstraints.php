<?php

namespace Drupal\media_test_source\Plugin\media\Source;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\Attribute\MediaSource;
use Drupal\media\MediaSourceEntityConstraintsInterface;
use Drupal\media\MediaSourceFieldConstraintsInterface;

/**
 * Provides generic media type.
 */
#[MediaSource(
  id: "test_constraints",
  label: new TranslatableMarkup("Test source with constraints"),
  description: new TranslatableMarkup("Test media source that provides constraints."),
  allowed_field_types: ["string_long"],
)]
class TestWithConstraints extends Test implements MediaSourceEntityConstraintsInterface, MediaSourceFieldConstraintsInterface {

  /**
   * {@inheritdoc}
   */
  public function getEntityConstraints() {
    return \Drupal::state()->get('media_source_test_entity_constraints', []);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return \Drupal::state()->get('media_source_test_field_constraints', []);
  }

}
