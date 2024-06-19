<?php

declare(strict_types=1);

namespace Drupal\Tests\serialization\Kernel;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group TypedData
 */
class MapDataNormalizerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'serialization'];

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->serializer = \Drupal::service('serializer');
    $this->typedDataManager = \Drupal::typedDataManager();
  }

  /**
   * Tests whether map data can be normalized.
   */
  public function testMapNormalize(): void {
    $typed_data = $this->buildExampleTypedData();
    $data = $this->serializer->normalize($typed_data, 'json');
    $expect_value = [
      'key1' => 'value1',
      'key2' => 'value2',
      'key3' => 3,
      'key4' => [
        0 => TRUE,
        1 => 'value6',
        'key7' => 'value7',
      ],
    ];
    $this->assertSame($expect_value, $data);
  }

  /**
   * Tests whether map data with properties can be normalized.
   */
  public function testMapWithPropertiesNormalize(): void {
    $typed_data = $this->buildExampleTypedDataWithProperties();
    $data = $this->serializer->normalize($typed_data, 'json');
    $expect_value = [
      'key1' => 'value1',
      'key2' => 'value2',
      'key3' => 3,
      'key4' => [
        0 => TRUE,
        1 => 'value6',
        'key7' => 'value7',
      ],
    ];
    $this->assertSame($expect_value, $data);
  }

  /**
   * Builds some example typed data object with no properties.
   */
  protected function buildExampleTypedData() {
    $tree = [
      'key1' => 'value1',
      'key2' => 'value2',
      'key3' => 3,
      'key4' => [
        0 => TRUE,
        1 => 'value6',
        'key7' => 'value7',
      ],
    ];
    $map_data_definition = MapDataDefinition::create();
    $typed_data = $this->typedDataManager->create(
      $map_data_definition,
      $tree,
      'test name'
    );
    return $typed_data;
  }

  /**
   * Builds some example typed data object with properties.
   */
  protected function buildExampleTypedDataWithProperties() {
    $tree = [
      'key1' => 'value1',
      'key2' => 'value2',
      'key3' => 3,
      'key4' => [
        0 => TRUE,
        1 => 'value6',
        'key7' => 'value7',
      ],
    ];
    $map_data_definition = MapDataDefinition::create()
      ->setPropertyDefinition('key1', DataDefinition::create('string'))
      ->setPropertyDefinition('key2', DataDefinition::create('string'))
      ->setPropertyDefinition('key3', DataDefinition::create('integer'))
      ->setPropertyDefinition('key4', MapDataDefinition::create()
        ->setPropertyDefinition(0, DataDefinition::create('boolean'))
        ->setPropertyDefinition(1, DataDefinition::create('string'))
        ->setPropertyDefinition('key7', DataDefinition::create('string'))
    );

    $typed_data = $this->typedDataManager->create(
      $map_data_definition,
      $tree,
      'test name'
    );

    return $typed_data;
  }

}
