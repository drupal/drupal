<?php

declare(strict_types=1);

namespace Drupal\media_test_source\Plugin\media\Source;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\Attribute\MediaSource;
use Drupal\media\MediaInterface;

/**
 * Provides test media source.
 */
#[MediaSource(
  id: "test_translation",
  label: new TranslatableMarkup("Test source with translations"),
  description: new TranslatableMarkup("Test media source with translations."),
  allowed_field_types: ["string"],
  thumbnail_alt_metadata_attribute: "test_thumbnail_alt"
)]
class TestTranslation extends Test {

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    if ($attribute_name == 'thumbnail_uri') {
      return 'public://' . $media->language()->getId() . '.png';
    }

    if ($attribute_name == 'test_thumbnail_alt') {
      $langcode = $media->language()->getId();
      return $this->t('Test Thumbnail @language', ['@language' => $langcode], ['langcode' => $langcode]);
    }

    return parent::getMetadata($media, $attribute_name);
  }

}
