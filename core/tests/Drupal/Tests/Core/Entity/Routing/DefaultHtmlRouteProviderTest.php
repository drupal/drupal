<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\Routing\DefaultHtmlRouteProviderTest.
 */

namespace Drupal\Tests\Core\Entity\Routing;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 * @group Entity
 */
class DefaultHtmlRouteProviderTest extends UnitTestCase {

  /**
   * The entity type manager prophecy used in the test.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager prophecy used in the test.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The HTML route provider used in the test.
   *
   * @var \Drupal\Tests\Core\Entity\Routing\TestDefaultHtmlRouteProvider
   */
  protected $routeProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);

    $this->routeProvider = new TestDefaultHtmlRouteProvider($this->entityTypeManager->reveal(), $this->entityFieldManager->reveal());
  }

  /**
   * @covers ::getAddPageRoute
   * @dataProvider providerTestGetAddPageRoute
   */
  public function testGetAddPageRoute(Route $expected = NULL, EntityTypeInterface $entity_type) {
    $route = $this->routeProvider->getAddPageRoute($entity_type);
    $this->assertEquals($expected, $route);
  }

  public function providerTestGetAddPageRoute() {
    $data = [];

    $entity_type1 = $this->getEntityType();
    $entity_type1->hasLinkTemplate('add-page')->willReturn(FALSE);
    $data['no_add_page_link_template'] = [NULL, $entity_type1->reveal()];

    $entity_type2 = $this->getEntityType();
    $entity_type2->hasLinkTemplate('add-page')->willReturn(TRUE);
    $entity_type2->getKey('bundle')->willReturn(NULL);
    $data['no_bundle'] = [NULL, $entity_type2->reveal()];

    $entity_type3 = $this->getEntityType();
    $entity_type3->hasLinkTemplate('add-page')->willReturn(TRUE);
    $entity_type3->getLinkTemplate('add-page')->willReturn('/the/add/page/link/template');
    $entity_type3->id()->willReturn('the_entity_type_id');
    $entity_type3->getKey('bundle')->willReturn('type');
    $route = new Route('/the/add/page/link/template');
    $route->setDefaults([
      '_controller' => 'Drupal\Core\Entity\Controller\EntityController::addPage',
      '_title_callback' => 'Drupal\Core\Entity\Controller\EntityController::addTitle',
      'entity_type_id' => 'the_entity_type_id',
    ]);
    $route->setRequirement('_entity_create_any_access', 'the_entity_type_id');
    $data['add_page'] = [clone $route, $entity_type3->reveal()];

    return $data;
  }

  /**
   * @covers ::getAddFormRoute
   * @dataProvider providerTestGetAddFormRoute
   */
  public function testGetAddFormRoute(Route $expected = NULL, EntityTypeInterface $entity_type, EntityTypeInterface $bundle_entity_type = NULL, FieldStorageDefinitionInterface $field_storage_definition = NULL) {
    if ($bundle_entity_type) {
      $this->entityTypeManager->getDefinition('the_bundle_entity_type_id')->willReturn($bundle_entity_type);

      if ($field_storage_definition) {
        $this->entityFieldManager->getFieldStorageDefinitions('the_bundle_entity_type_id')
          ->willReturn(['id' => $field_storage_definition]);
      }
    }

    $route = $this->routeProvider->getAddFormRoute($entity_type);
    $this->assertEquals($expected, $route);
  }

  public function providerTestGetAddFormRoute() {
    $data = [];

    $entity_type1 = $this->getEntityType();
    $entity_type1->hasLinkTemplate('add-form')->willReturn(FALSE);

    $data['no_add_form_link_template'] = [NULL, $entity_type1->reveal()];

    $entity_type2 = $this->getEntityType();
    $entity_type2->getBundleEntityType()->willReturn(NULL);
    $entity_type2->hasLinkTemplate('add-form')->willReturn(TRUE);
    $entity_type2->id()->willReturn('the_entity_type_id');
    $entity_type2->getLinkTemplate('add-form')->willReturn('/the/add/form/link/template');
    $entity_type2->getFormClass('add')->willReturn(NULL);
    $entity_type2->getKey('bundle')->willReturn(NULL);
    $route = (new Route('/the/add/form/link/template'))
      ->setDefaults([
        '_entity_form' => 'the_entity_type_id.default',
        'entity_type_id' => 'the_entity_type_id',
        '_title_callback' => 'Drupal\Core\Entity\Controller\EntityController::addTitle',
      ])
      ->setRequirement('_entity_create_access', 'the_entity_type_id');
    $data['no_add_form_no_bundle'] = [clone $route, $entity_type2->reveal()];

    $entity_type3 = $this->getEntityType($entity_type2);
    $entity_type3->getFormClass('add')->willReturn('Drupal\Core\Entity\EntityForm');
    $route->setDefault('_entity_form', 'the_entity_type_id.add');
    $data['add_form_no_bundle'] = [clone $route, $entity_type3->reveal()];

    $entity_type4 = $this->getEntityType($entity_type3);
    $entity_type4->getKey('bundle')->willReturn('the_bundle_key');
    $entity_type4->getBundleEntityType()->willReturn(NULL);
    $entity_type4->getLinkTemplate('add-form')->willReturn('/the/add/form/link/template/{the_bundle_key}');
    $route->setPath('/the/add/form/link/template/{the_bundle_key}');
    $route
      ->setDefault('_title_callback', 'Drupal\Core\Entity\Controller\EntityController::addBundleTitle')
      ->setDefault('bundle_parameter', 'the_bundle_key')
      ->setRequirement('_entity_create_access', 'the_entity_type_id:{the_bundle_key}');
    $data['add_form_bundle_static'] = [clone $route, $entity_type4->reveal()];

    $entity_type5 = $this->getEntityType($entity_type4);
    $entity_type5->getBundleEntityType()->willReturn('the_bundle_entity_type_id');
    $entity_type5->getLinkTemplate('add-form')->willReturn('/the/add/form/link/template/{the_bundle_entity_type_id}');
    $bundle_entity_type = $this->getEntityType();
    $bundle_entity_type->entityClassImplements(FieldableEntityInterface::class)->willReturn(FALSE);
    $route->setPath('/the/add/form/link/template/{the_bundle_entity_type_id}');
    $route
      ->setDefault('bundle_parameter', 'the_bundle_entity_type_id')
      ->setRequirement('_entity_create_access', 'the_entity_type_id:{the_bundle_entity_type_id}')
      ->setOption('parameters', [
        'the_bundle_entity_type_id' => [
          'type' => 'entity:the_bundle_entity_type_id',
        ],
      ]);
    $data['add_form_bundle_entity_id_key_type_null'] = [clone $route, $entity_type5->reveal(), $bundle_entity_type->reveal()];

    $entity_type6 = $this->getEntityType($entity_type5);
    $bundle_entity_type = $this->getEntityType();
    $bundle_entity_type->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);
    $field_storage_definition = $this->prophesize(FieldStorageDefinitionInterface::class);
    $field_storage_definition->getType()->willReturn('integer');
    $route->setRequirement('the_entity_type_id', '\d+');
    $data['add_form_bundle_entity_id_key_type_integer'] = [clone $route, $entity_type6->reveal(), $bundle_entity_type->reveal(), $field_storage_definition->reveal()];

    $entity_type7 = $this->getEntityType($entity_type6);
    $bundle_entity_type = $this->prophesize(ConfigEntityTypeInterface::class);
    $bundle_entity_type->entityClassImplements(FieldableEntityInterface::class)->willReturn(FALSE);
    $field_storage_definition = $this->prophesize(FieldStorageDefinitionInterface::class);
    $route
      // Unset the 'the_entity_type_id' requirement.
      ->setRequirements(['_entity_create_access' => $route->getRequirement('_entity_create_access')])
      ->setOption('parameters', [
        'the_bundle_entity_type_id' => [
          'type' => 'entity:the_bundle_entity_type_id',
          'with_config_overrides' => TRUE,
        ],
      ]);
    $data['add_form_bundle_entity_id_key_type_integer'] = [clone $route, $entity_type7->reveal(), $bundle_entity_type->reveal(), $field_storage_definition->reveal()];

    return $data;
  }

  /**
   * @covers ::getCanonicalRoute
   * @dataProvider providerTestGetCanonicalRoute
   */
  public function testGetCanonicalRoute(Route $expected = NULL, EntityTypeInterface $entity_type, FieldStorageDefinitionInterface $field_storage_definition = NULL) {
    if ($field_storage_definition) {
      $this->entityFieldManager->getFieldStorageDefinitions($entity_type->id())
        ->willReturn([$entity_type->getKey('id') => $field_storage_definition]);
    }

    $route = $this->routeProvider->getCanonicalRoute($entity_type);
    $this->assertEquals($expected, $route);
  }

  public function providerTestGetCanonicalRoute() {
    $data = [];

    $entity_type1 = $this->getEntityType();
    $entity_type1->hasLinkTemplate('canonical')->willReturn(FALSE);
    $data['no_canonical_link_template'] = [NULL, $entity_type1->reveal()];

    $entity_type2 = $this->getEntityType();;
    $entity_type2->hasLinkTemplate('canonical')->willReturn(TRUE);
    $entity_type2->hasViewBuilderClass()->willReturn(FALSE);
    $data['no_view_builder'] = [NULL, $entity_type2->reveal()];

    $entity_type3 = $this->getEntityType($entity_type2);
    $entity_type3->hasViewBuilderClass()->willReturn(TRUE);
    $entity_type3->id()->willReturn('the_entity_type_id');
    $entity_type3->getLinkTemplate('canonical')->willReturn('/the/canonical/link/template');
    $entity_type3->entityClassImplements(FieldableEntityInterface::class)->willReturn(FALSE);
    $route = (new Route('/the/canonical/link/template'))
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
    $data['id_key_type_null'] = [clone $route, $entity_type3->reveal()];

    $entity_type4 = $this->getEntityType($entity_type3);
    $entity_type4->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);
    $entity_type4->getKey('id')->willReturn('id');
    $route->setRequirement('the_entity_type_id', '\d+');
    $field_storage_definition = $this->prophesize(FieldStorageDefinitionInterface::class);
    $field_storage_definition->getType()->willReturn('integer');
    $data['id_key_type_integer'] = [clone $route, $entity_type4->reveal(), $field_storage_definition->reveal()];

    return $data;
  }

  /**
   * @covers ::getCollectionRoute
   * @dataProvider providerTestGetCollectionRoute
   */
  public function testGetCollectionRoute(Route $expected = NULL, EntityTypeInterface $entity_type) {
    $route = $this->routeProvider->getCollectionRoute($entity_type);
    $this->assertEquals($expected, $route);
  }

  public function providerTestGetCollectionRoute() {
    $data = [];

    $entity_type1 = $this->getEntityType();
    $entity_type1->hasLinkTemplate('collection')->willReturn(FALSE);
    $data['no_collection_link_template'] = [NULL, $entity_type1->reveal()];

    $entity_type2 = $this->getEntityType();
    $entity_type2->hasLinkTemplate('collection')->willReturn(TRUE);
    $entity_type2->hasListBuilderClass()->willReturn(FALSE);
    $data['no_list_builder'] = [NULL, $entity_type2->reveal()];

    $entity_type3 = $this->getEntityType($entity_type2);
    $entity_type3->hasListBuilderClass()->willReturn(TRUE);
    $entity_type3->getAdminPermission()->willReturn(FALSE);
    $data['no_admin_permission'] = [NULL, $entity_type3->reveal()];

    $entity_type4 = $this->getEntityType($entity_type3);
    $entity_type4->getAdminPermission()->willReturn('administer the entity type');
    $entity_type4->id()->willReturn('the_entity_type_id');
    $entity_type4->getLabel()->willReturn('The entity type');
    $entity_type4->getCollectionLabel()->willReturn(new TranslatableMarkup('Test entities'));
    $entity_type4->getLinkTemplate('collection')->willReturn('/the/collection/link/template');
    $entity_type4->entityClassImplements(FieldableEntityInterface::class)->willReturn(FALSE);
    $route = (new Route('/the/collection/link/template'))
      ->setDefaults([
        '_entity_list' => 'the_entity_type_id',
        '_title' => 'Test entities',
        '_title_arguments' => [],
        '_title_context' => '',
      ])
      ->setRequirements([
        '_permission' => 'administer the entity type',
      ]);
    $data['collection_route'] = [clone $route, $entity_type4->reveal()];

    return $data;
  }

  /**
   * @covers ::getEntityTypeIdKeyType
   */
  public function testGetEntityTypeIdKeyType() {
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);
    $entity_type->id()->willReturn('the_entity_type_id');
    $entity_type->getKey('id')->willReturn('id');

    $field_storage_definition = $this->prophesize(FieldStorageDefinitionInterface::class);
    $field_storage_definition->getType()->willReturn('integer');
    $this->entityFieldManager->getFieldStorageDefinitions('the_entity_type_id')->willReturn(['id' => $field_storage_definition]);

    $type = $this->routeProvider->getEntityTypeIdKeyType($entity_type->reveal());
    $this->assertSame('integer', $type);
  }

  /**
   * @covers ::getEntityTypeIdKeyType
   */
  public function testGetEntityTypeIdKeyTypeNotFieldable() {
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->entityClassImplements(FieldableEntityInterface::class)->willReturn(FALSE);
    $this->entityFieldManager->getFieldStorageDefinitions(Argument::any())->shouldNotBeCalled();

    $type = $this->routeProvider->getEntityTypeIdKeyType($entity_type->reveal());
    $this->assertNull($type);
  }

  /**
   * @param \Prophecy\Prophecy\ObjectProphecy $base_entity_type
   * @return \Prophecy\Prophecy\ObjectProphecy
   */
  protected function getEntityType(ObjectProphecy $base_entity_type = NULL) {
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    if ($base_entity_type) {
      foreach ($base_entity_type->getMethodProphecies() as $method => $prophecies) {
        foreach ($prophecies as $prophecy) {
          $entity_type->addMethodProphecy(clone $prophecy);
        }
      }
    }
    return $entity_type;
  }

}

class TestDefaultHtmlRouteProvider extends DefaultHtmlRouteProvider {

  public function getEntityTypeIdKeyType(EntityTypeInterface $entity_type) {
    return parent::getEntityTypeIdKeyType($entity_type);
  }
  public function getAddPageRoute(EntityTypeInterface $entity_type) {
    return parent::getAddPageRoute($entity_type);
  }
  public function getAddFormRoute(EntityTypeInterface $entity_type) {
    return parent::getAddFormRoute($entity_type);
  }
  public function getCanonicalRoute(EntityTypeInterface $entity_type) {
    return parent::getCanonicalRoute($entity_type);
  }
  public function getCollectionRoute(EntityTypeInterface $entity_type) {
    return parent::getCollectionRoute($entity_type);
  }

}
