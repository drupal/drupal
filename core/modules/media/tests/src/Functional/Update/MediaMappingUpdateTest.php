<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\media\Entity\MediaType;
use Drupal\media\Plugin\media\Source\File;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests update functions for the Media module.
 *
 * @group media
 */
class MediaMappingUpdateTest extends UpdatePathTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      $this->getDrupalRoot() . '/core/modules/system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/media.php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager */
    $update_manager = $this->container->get('entity.definition_update_manager');

    $entity_type = $entity_type_manager->getDefinition('media_type');
    $update_manager->installEntityType($entity_type);
    $entity_type = $entity_type_manager->getDefinition('media');
    $update_manager->installEntityType($entity_type);
  }

  /**
   * Tests updating Media types using source field in meta mappings.
   *
   * @see media_post_update_remove_mappings_targeting_source_field()
   */
  public function testMediaMappingUpdate(): void {
    $media_type = $this->createMediaType('image', ['id' => 'invalid_mapping']);

    $source_field_name = $media_type->getSource()
      ->getSourceFieldDefinition($media_type)
      ->getName();
    $field_map = $media_type->getFieldMap();
    $field_map[File::METADATA_ATTRIBUTE_MIME] = $source_field_name;
    $this->config($media_type->getConfigDependencyName())
      ->set('field_map', $field_map)
      ->save();

    $this->runUpdates();

    $this->container->get('entity_type.manager')
      ->getStorage('media_type')
      ->resetCache(['invalid_mapping']);
    $field_map = MediaType::load('invalid_mapping')?->getFieldMap();
    $this->assertIsArray($field_map);
    $this->assertNotContains($source_field_name, $field_map);
  }

}
