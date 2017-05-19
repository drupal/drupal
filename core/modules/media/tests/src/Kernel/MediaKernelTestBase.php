<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\MediaType;

/**
 * Base class for Media kernel tests.
 */
abstract class MediaKernelTestBase extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'media',
    'media_test_source',
    'image',
    'user',
    'field',
    'system',
    'file',
  ];

  /**
   * The test media type.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $testMediaType;

  /**
   * The test media type with constraints.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $testConstraintsMediaType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', 'file_usage');
    $this->installEntitySchema('media');
    $this->installConfig(['field', 'system', 'image', 'file', 'media']);

    // Create a test media type.
    $this->testMediaType = $this->createMediaType('test');
    // Create a test media type with constraints.
    $this->testConstraintsMediaType = $this->createMediaType('test_constraints');
  }

  /**
   * Create a media type for a source plugin.
   *
   * @param string $media_source_name
   *   The name of the media source.
   *
   * @return \Drupal\media\MediaTypeInterface
   *   A media type.
   */
  protected function createMediaType($media_source_name) {
    $id = strtolower($this->randomMachineName());
    $media_type = MediaType::create([
      'id' => $id,
      'label' => $id,
      'source' => $media_source_name,
      'new_revision' => FALSE,
    ]);
    $media_type->save();
    $source_field = $media_type->getSource()->createSourceField($media_type);
    // The media type form creates a source field if it does not exist yet. The
    // same must be done in a kernel test, since it does not use that form.
    // @see \Drupal\media\MediaTypeForm::save()
    $source_field->getFieldStorageDefinition()->save();
    // The source field storage has been created, now the field can be saved.
    $source_field->save();
    $media_type->set('source_configuration', [
      'source_field' => $source_field->getName(),
    ])->save();
    return $media_type;
  }

}
