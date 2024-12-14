<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Plugin\media\Source\File;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @coversDefaultClass \Drupal\media\Plugin\Validation\Constraint\MediaMappingsConstraintValidator
 *
 * @group media
 */
class MediaMappingsConstraintValidatorTest extends KernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'file', 'image', 'media', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('user');
  }

  /**
   * @covers ::validate
   */
  public function testMediaMappingSource(): void {
    $media_type = $this->createMediaType('image', [
      'id' => 'test',
    ]);

    $source_field_name = $media_type->getSource()
      ->getSourceFieldDefinition($media_type)
      ->getName();
    $field_map = $media_type->getFieldMap();
    $field_map[File::METADATA_ATTRIBUTE_MIME] = $source_field_name;
    $media_type->setFieldMap($field_map);
    $media_type->save();
    $typed_data = $this->container->get('typed_data_manager');
    $definition = $typed_data->createDataDefinition('entity:media_type');
    $violations = $typed_data->create($definition, $media_type)->validate();
    assert($violations instanceof ConstraintViolationListInterface);
    $this->assertCount(1, $violations);
    $this->assertEquals('It is not possible to map the source field ' . $source_field_name . ' of a media type.', $violations[0]->getMessage());
  }

}
