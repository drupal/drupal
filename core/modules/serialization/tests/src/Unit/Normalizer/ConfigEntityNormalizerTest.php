<?php

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
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

    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $normalizer = new ConfigEntityNormalizer($entity_manager);

    $config_entity = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityInterface');
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

    // Stubs for the denormalizer going from entity manager to entity storage.
    $entity_type_id = $this->randomMachineName();
    $entity_type_class = $this->randomMachineName();
    $entity_manager = $this->prophesize(EntityManagerInterface::class);
    $entity_manager->getEntityTypeFromClass($entity_type_class)
      ->willReturn($entity_type_id);
    $entity_manager->getDefinition($entity_type_id, FALSE)
      ->willReturn($this->prophesize(ConfigEntityTypeInterface::class)->reveal());
    $entity_manager->getStorage($entity_type_id)
      ->willReturn($entity_storage->reveal());
    $normalizer = new ConfigEntityNormalizer($entity_manager->reveal());

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
