<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\Routing\DefaultHtmlRouteProviderTest.
 */

namespace Drupal\Tests\Core\Entity\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 * @group Entity
 */
class DefaultHtmlRouteProviderTest extends UnitTestCase {

  /**
   * @covers ::getEntityTypeIdKeyType
   */
  public function testGetEntityTypeIdKeyType() {
    $entity_manager = $this->prophesize(EntityManagerInterface::class);
    $route_provider = new TestDefaultHtmlRouteProvider($entity_manager->reveal());

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->isSubclassOf(FieldableEntityInterface::class)->willReturn(TRUE);
    $entity_type_id = 'the_entity_type_id';
    $entity_type->id()->willReturn($entity_type_id);
    $entity_type->getKey('id')->willReturn('id');

    $field_storage_definition = $this->prophesize(FieldStorageDefinitionInterface::class);
    $field_storage_definition->getType()->willReturn('integer');
    $entity_manager->getFieldStorageDefinitions($entity_type_id)->willReturn(['id' => $field_storage_definition]);

    $type = $route_provider->getEntityTypeIdKeyType($entity_type->reveal());
    $this->assertSame('integer', $type);
  }

  /**
   * @covers ::getEntityTypeIdKeyType
   */
  public function testGetEntityTypeIdKeyTypeNotFieldable() {
    $entity_manager = $this->prophesize(EntityManagerInterface::class);
    $route_provider = new TestDefaultHtmlRouteProvider($entity_manager->reveal());

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->isSubclassOf(FieldableEntityInterface::class)->willReturn(FALSE);
    $entity_manager->getFieldStorageDefinitions(Argument::any())->shouldNotBeCalled();

    $type = $route_provider->getEntityTypeIdKeyType($entity_type->reveal());
    $this->assertNull($type);
  }

  /**
   * @covers ::getCanonicalRoute
   * @dataProvider providerTestGetCanonicalRoute
   */
  public function testGetCanonicalRoute($entity_type_prophecy, $expected, $field_storage_definition = NULL) {
    $entity_manager = $this->prophesize(EntityManagerInterface::class);
    $route_provider = new TestDefaultHtmlRouteProvider($entity_manager->reveal());
    $entity_type = $entity_type_prophecy->reveal();

    if ($field_storage_definition) {
      $entity_manager->getFieldStorageDefinitions($entity_type->id())
        ->willReturn([$entity_type->getKey('id') => $field_storage_definition]);
    }

    $route = $route_provider->getCanonicalRoute($entity_type);
    $this->assertEquals($expected, $route);
  }

  public function providerTestGetCanonicalRoute() {
    $data = [];

    $entity_type1 = $this->prophesize(EntityTypeInterface::class);
    $entity_type1->hasLinkTemplate('canonical')->willReturn(FALSE);
    $data['no_canonical_link_template'] = [$entity_type1, NULL];

    $entity_type2 = $this->prophesize(EntityTypeInterface::class);
    $entity_type2->hasLinkTemplate('canonical')->willReturn(TRUE);
    $entity_type2->hasViewBuilderClass()->willReturn(FALSE);
    $data['no_view_builder'] = [$entity_type2, NULL];

    $entity_type3 = $this->prophesize(EntityTypeInterface::class);
    $entity_type3->hasLinkTemplate('canonical')->willReturn(TRUE);
    $entity_type3->hasViewBuilderClass()->willReturn(TRUE);
    $entity_type3->id()->willReturn('the_entity_type_id');
    $entity_type3->getLinkTemplate('canonical')->willReturn('/the/canonical/link/template');
    $entity_type3->isSubclassOf(FieldableEntityInterface::class)->willReturn(FALSE);
    $route3 = (new Route('/the/canonical/link/template'))
      ->setDefaults([
        '_entity_view' => 'the_entity_type_id.full',
        '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
      ])
      ->setRequirements([
        '_entity_access' => 'the_entity_type_id.view',
      ])
      ->setOptions([
        'parameters' => [
          'the_entity_type_id' => [
            'type' => 'entity:the_entity_type_id',
          ],
        ],
      ]);
    $data['id_key_type_null'] = [$entity_type3, $route3];

    $entity_type4 = $this->prophesize(EntityTypeInterface::class);
    $entity_type4->hasLinkTemplate('canonical')->willReturn(TRUE);
    $entity_type4->hasViewBuilderClass()->willReturn(TRUE);
    $entity_type4->id()->willReturn('the_entity_type_id');
    $entity_type4->getLinkTemplate('canonical')->willReturn('/the/canonical/link/template');
    $entity_type4->isSubclassOf(FieldableEntityInterface::class)->willReturn(TRUE);
    $entity_type4->getKey('id')->willReturn('id');
    $route4 = (new Route('/the/canonical/link/template'))
      ->setDefaults([
        '_entity_view' => 'the_entity_type_id.full',
        '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
      ])
      ->setRequirements([
        '_entity_access' => 'the_entity_type_id.view',
        'the_entity_type_id' => '\d+',
      ])
      ->setOptions([
        'parameters' => [
          'the_entity_type_id' => [
            'type' => 'entity:the_entity_type_id',
          ],
        ],
      ]);
    $field_storage_definition = $this->prophesize(FieldStorageDefinitionInterface::class);
    $field_storage_definition->getType()->willReturn('integer');
    $data['id_key_type_integer'] = [$entity_type4, $route4, $field_storage_definition];

    return $data;
  }

}

class TestDefaultHtmlRouteProvider extends DefaultHtmlRouteProvider {

  public function getEntityTypeIdKeyType(EntityTypeInterface $entity_type) {
    return parent::getEntityTypeIdKeyType($entity_type);
  }
  public function getCanonicalRoute(EntityTypeInterface $entity_type) {
    return parent::getCanonicalRoute($entity_type);
  }

}
