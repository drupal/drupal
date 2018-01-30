<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\layout_builder\Routing\SectionStorageDefaultsParamConverter;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\layout_builder\Routing\SectionStorageDefaultsParamConverter
 *
 * @group layout_builder
 */
class SectionStorageDefaultsParamConverterTest extends UnitTestCase {

  /**
   * The converter.
   *
   * @var \Drupal\layout_builder\Routing\SectionStorageDefaultsParamConverter
   */
  protected $converter;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityManager = $this->prophesize(EntityManagerInterface::class);
    $this->converter = new SectionStorageDefaultsParamConverter($this->entityManager->reveal());
  }

  /**
   * @covers ::convert
   * @covers ::getEntityTypeFromDefaults
   *
   * @dataProvider providerTestConvert
   */
  public function testConvert($success, $expected_entity_id, $value, array $defaults) {
    if ($expected_entity_id) {
      $entity_storage = $this->prophesize(EntityStorageInterface::class);
      $entity_storage->load($expected_entity_id)->willReturn('the_return_value');

      $this->entityManager->getDefinition('entity_view_display')->willReturn(new EntityType(['id' => 'entity_view_display']));
      $this->entityManager->getStorage('entity_view_display')->willReturn($entity_storage->reveal());
    }
    else {
      $this->entityManager->getDefinition('entity_view_display')->shouldNotBeCalled();
      $this->entityManager->getStorage('entity_view_display')->shouldNotBeCalled();
    }

    $result = $this->converter->convert($value, [], 'the_parameter_name', $defaults);
    if ($success) {
      $this->assertEquals('the_return_value', $result);
    }
    else {
      $this->assertNull($result);
    }
  }

  /**
   * Provides data for ::testConvert().
   */
  public function providerTestConvert() {
    $data = [];
    $data['with value'] = [
      TRUE,
      'some_value',
      'some_value',
      [],
    ];
    $data['empty value, without bundle'] = [
      TRUE,
      'my_entity_type.bundle_name.default',
      '',
      [
        'entity_type_id' => 'my_entity_type',
        'view_mode_name' => 'default',
        'bundle_key' => 'my_bundle',
        'my_bundle' => 'bundle_name',
      ],
    ];
    $data['empty value, with bundle'] = [
      TRUE,
      'my_entity_type.bundle_name.default',
      '',
      [
        'entity_type_id' => 'my_entity_type',
        'view_mode_name' => 'default',
        'bundle' => 'bundle_name',
      ],
    ];
    $data['without value, empty defaults'] = [
      FALSE,
      NULL,
      '',
      [],
    ];
    return $data;
  }

  /**
   * @covers ::convert
   */
  public function testConvertCreate() {
    $expected = 'the_return_value';
    $value = 'foo.bar.baz';
    $expected_create_values = [
      'targetEntityType' => 'foo',
      'bundle' => 'bar',
      'mode' => 'baz',
      'status' => TRUE,
    ];
    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $entity_storage->load($value)->willReturn(NULL);
    $entity_storage->create($expected_create_values)->willReturn($expected);

    $this->entityManager->getDefinition('entity_view_display')->willReturn(new EntityType(['id' => 'entity_view_display']));
    $this->entityManager->getStorage('entity_view_display')->willReturn($entity_storage->reveal());

    $result = $this->converter->convert($value, [], 'the_parameter_name', []);
    $this->assertSame($expected, $result);
  }

}
