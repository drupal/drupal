<?php

namespace Drupal\Tests\media\Functional;

use Drupal\media\Entity\MediaType;

/**
 * Trait with helpers for Media functional tests.
 */
trait MediaFunctionalTestCreateMediaTypeTrait {

  /**
   * Creates a media type.
   *
   * @param array $values
   *   The media type values.
   * @param string $source
   *   (optional) The media source plugin that is responsible for additional
   *   logic related to this media type. Defaults to 'test'.
   *
   * @return \Drupal\media\MediaTypeInterface
   *   A newly created media type.
   */
  protected function createMediaType(array $values = [], $source = 'test') {
    if (empty($values['bundle'])) {
      $id = strtolower($this->randomMachineName());
    }
    else {
      $id = $values['bundle'];
    }
    $values += [
      'id' => $id,
      'label' => $id,
      'source' => $source,
      'source_configuration' => [],
      'field_map' => [],
      'new_revision' => FALSE,
    ];

    $media_type = MediaType::create($values);
    $status = $media_type->save();

    // @todo Rename to assertSame() when #1945040 is done.
    // @see https://www.drupal.org/node/1945040
    $this->assertIdentical(SAVED_NEW, $status, 'Media type was created successfully.');

    // Ensure that the source field exists.
    $source = $media_type->getSource();
    $source_field = $source->getSourceFieldDefinition($media_type);
    if (!$source_field) {
      $source_field = $source->createSourceField($media_type);
      /** @var \Drupal\field\FieldStorageConfigInterface $storage */
      $storage = $source_field->getFieldStorageDefinition();
      $storage->save();
      $source_field->save();

      $media_type
        ->set('source_configuration', [
          'source_field' => $source_field->getName(),
        ])
        ->save();
    }

    return $media_type;
  }

}
