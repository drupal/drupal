<?php

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\serialization\Normalizer\ConfigEntityNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\serialization\Normalizer\ConfigEntityNormalizer
 * @group serialization
 */
class ConfigEntityNormalizerTest extends UnitTestCase {

  /**
   * Tests the normalize() method.
   *
   * @covers ::normalize
   */
  public function testNormalize() {
    $test_export_properties = [
      'test' => 'test',
      '_core' => [
        'default_config_hash' => $this->randomMachineName(),
        $this->randomMachineName() => 'some random key',
      ],
    ];

    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_repository = $this->createMock(EntityTypeRepositoryInterface::class);
    $normalizer = new ConfigEntityNormalizer(
      $entity_type_manager,
      $entity_type_repository,
      $entity_field_manager
    );

    $config_entity = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityInterface');
    $config_entity->expects($this->once())
      ->method('toArray')
      ->will($this->returnValue($test_export_properties));

    $this->assertSame(['test' => 'test'], $normalizer->normalize($config_entity));
  }

  /**
   * @covers ::denormalize
   */
  public function testDenormalize() {
    $test_value = $this->randomMachineName();
    $data = [
      'test' => $test_value,
      '_core' => [
        'default_config_hash' => $this->randomMachineName(),
        $this->randomMachineName() => 'some random key',
      ],
    ];

    $expected_storage_data = [
      'test' => $test_value,
    ];

    // Mock of the entity storage, to test our expectation that the '_core' key
    // never makes it to that point, thanks to the denormalizer omitting it.
    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $entity_storage->create($expected_storage_data)
      ->shouldBeCalled()
      ->will(function ($args) {
        $entity = new \stdClass();
        $entity->received_data = $args[0];
        return $entity;
      });

    // Stubs for the denormalizer going from entity type manager to entity
    // storage.
    $entity_type_id = $this->randomMachineName();
    $entity_type_class = $this->randomMachineName();
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getDefinition($entity_type_id, FALSE)
      ->willReturn($this->prophesize(ConfigEntityTypeInterface::class)->reveal());
    $entity_type_manager->getStorage($entity_type_id)
      ->willReturn($entity_storage->reveal());
    $entity_type_repository = $this->prophesize(EntityTypeRepositoryInterface::class);
    $entity_type_repository->getEntityTypeFromClass($entity_type_class)
      ->willReturn($entity_type_id);
    $entity_field_manager = $this->prophesize(EntityFieldManagerInterface::class);
    $normalizer = new ConfigEntityNormalizer($entity_type_manager->reveal(), $entity_type_repository->reveal(), $entity_field_manager->reveal());

    // Verify the denormalizer still works correctly: the mock above creates an
    // artificial entity object containing exactly the data it received. It also
    // should still set _restSubmittedFields correctly.
    $expected_denormalization = (object) [
      '_restSubmittedFields' => [
        'test',
      ],
      'received_data' => [
        'test' => $test_value,
      ],
    ];
    $this->assertEquals($expected_denormalization, $normalizer->denormalize($data, $entity_type_class, 'json'));
  }

}
