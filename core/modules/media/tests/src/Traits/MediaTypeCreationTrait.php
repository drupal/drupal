<?php

namespace Drupal\Tests\media\Traits;

use Drupal\media\Entity\MediaType;

/**
 * Provides methods to create a media type from given values.
 *
 * This trait is meant to be used only by test classes.
 */
trait MediaTypeCreationTrait {

  /**
   * Create a media type for a source plugin.
   *
   * @param string $source_plugin_id
   *   The media source plugin ID.
   * @param mixed[] $values
   *   (optional) Additional values for the media type entity:
   *   - id: The ID of the media type. If none is provided, a random value will
   *   be used.
   *   - label: The human-readable label of the media type. If none is provided,
   *   a random value will be used.
   *   - new_revision: Whether media items of this type should create new
   *   revisions on save by default. Defaults to FALSE.
   *   - field_map: Array containing the field map configuration. The keys are
   *   the names of metadata attributes provided by the source plugin, and the
   *   values are the names of entity fields to which those attributes should be
   *   copied. Empty by default.
   *   - source_configuration: Additional configuration options for the source
   *   plugin. Empty by default.
   *
   * @return \Drupal\media\MediaTypeInterface
   *   A media type.
   */
  protected function createMediaType($source_plugin_id, array $values = []) {
    if (isset($values['bundle'])) {
      $values['id'] = $values['bundle'];
      unset($values['bundle']);
    }

    $values += [
      'id' => $this->randomMachineName(),
      'label' => $this->randomMachineName(),
      'source' => $source_plugin_id,
      'source_configuration' => [],
      'field_map' => [],
      'new_revision' => FALSE,
    ];

    /** @var \Drupal\media\MediaTypeInterface $media_type */
    $media_type = MediaType::create($values);
    $this->assertSame(SAVED_NEW, $media_type->save());

    $source = $media_type->getSource();
    $source_field = $source->createSourceField($media_type);
    // The media type form creates a source field if it does not exist yet. The
    // same must be done in a kernel test, since it does not use that form.
    // @see \Drupal\media\MediaTypeForm::save()
    $source_field->getFieldStorageDefinition()->save();
    // The source field storage has been created, now the field can be saved.
    $source_field->save();

    $source_configuration = $source->getConfiguration();
    $source_configuration['source_field'] = $source_field->getName();
    $source->setConfiguration($source_configuration);

    $this->assertSame(SAVED_UPDATED, $media_type->save());

    // Add the source field to the form display for the media type.
    $form_display = entity_get_form_display('media', $media_type->id(), 'default');
    $source->prepareFormDisplay($media_type, $form_display);
    $form_display->save();

    return $media_type;
  }

}
