<?php

namespace Drupal\Tests\image\Kernel;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests the image field rendering using entity fields of the image field type.
 *
 * @group image
 */
class ImageFormatterTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'image'];

  /**
   * @var string
   */
  protected $entityType;

  /**
   * @var string
   */
  protected $bundle;

  /**
   * @var string
   */
  protected $fieldName;

  /**
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['field']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    $this->entityType = 'entity_test';
    $this->bundle = $this->entityType;
    $this->fieldName = Unicode::strtolower($this->randomMachineName());

    FieldStorageConfig::create([
      'entity_type' => $this->entityType,
      'field_name' => $this->fieldName,
      'type' => 'image',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => $this->entityType,
      'field_name' => $this->fieldName,
      'bundle' => $this->bundle,
      'settings' => [
        'file_extensions' => 'jpg',
      ],
    ])->save();

    $this->display = entity_get_display($this->entityType, $this->bundle, 'default')
      ->setComponent($this->fieldName, [
        'type' => 'image',
        'label' => 'hidden',
      ]);
    $this->display->save();
  }

  /**
   * Tests the cache tags from image formatters.
   */
  public function testImageFormatterCacheTags() {
    // Create a test entity with the image field set.
    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
    ]);
    $entity->{$this->fieldName}->generateSampleItems(2);
    $entity->save();

    // Generate the render array to verify if the cache tags are as expected.
    $build = $this->display->build($entity);

    $this->assertEquals($entity->{$this->fieldName}[0]->entity->getCacheTags(), $build[$this->fieldName][0]['#cache']['tags'], 'First image cache tags is as expected');
    $this->assertEquals($entity->{$this->fieldName}[1]->entity->getCacheTags(), $build[$this->fieldName][1]['#cache']['tags'], 'Second image cache tags is as expected');
  }

}
