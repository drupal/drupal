<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\layout_builder\Routing\SectionStorageOverridesParamConverter;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\layout_builder\Routing\SectionStorageOverridesParamConverter
 *
 * @group layout_builder
 */
class SectionStorageOverridesParamConverterTest extends UnitTestCase {

  /**
   * The converter.
   *
   * @var \Drupal\layout_builder\Routing\SectionStorageOverridesParamConverter
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
    $this->converter = new SectionStorageOverridesParamConverter($this->entityManager->reveal());
  }

  /**
   * @covers ::convert
   * @covers ::getEntityTypeFromDefaults
   * @covers ::getEntityIdFromDefaults
   *
   * @dataProvider providerTestConvert
   */
  public function testConvert($success, $expected_entity_type_id, $value, array $defaults) {
    $defaults['the_parameter_name'] = $value;

    if ($expected_entity_type_id) {
      $entity_storage = $this->prophesize(EntityStorageInterface::class);

      $entity_without_layout = $this->prophesize(FieldableEntityInterface::class);
      $entity_without_layout->hasField('layout_builder__layout')->willReturn(FALSE);
      $entity_without_layout->get('layout_builder__layout')->shouldNotBeCalled();
      $entity_storage->load('entity_without_layout')->willReturn($entity_without_layout->reveal());

      $entity_with_layout = $this->prophesize(FieldableEntityInterface::class);
      $entity_with_layout->hasField('layout_builder__layout')->willReturn(TRUE);
      $entity_with_layout->get('layout_builder__layout')->willReturn('the_return_value');
      $entity_storage->load('entity_with_layout')->willReturn($entity_with_layout->reveal());

      $this->entityManager->getDefinition($expected_entity_type_id)->willReturn(new EntityType(['id' => 'entity_view_display']));
      $this->entityManager->getStorage($expected_entity_type_id)->willReturn($entity_storage->reveal());
    }
    else {
      $this->entityManager->getDefinition(Argument::any())->shouldNotBeCalled();
      $this->entityManager->getStorage(Argument::any())->shouldNotBeCalled();
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
    $data['with value, with layout'] = [
      TRUE,
      'my_entity_type',
      'my_entity_type:entity_with_layout',
      [],
    ];
    $data['with value, without layout'] = [
      FALSE,
      'my_entity_type',
      'my_entity_type:entity_without_layout',
      [],
    ];
    $data['empty value, populated defaults'] = [
      TRUE,
      'my_entity_type',
      '',
      [
        'entity_type_id' => 'my_entity_type',
        'my_entity_type' => 'entity_with_layout',
      ],
    ];
    $data['empty value, empty defaults'] = [
      FALSE,
      NULL,
      '',
      [],
    ];
    return $data;
  }

}
