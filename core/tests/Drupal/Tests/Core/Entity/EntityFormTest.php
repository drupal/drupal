<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityForm
 * @group Entity
 */
class EntityFormTest extends UnitTestCase {

  /**
   * The mocked entity form.
   *
   * @var \Drupal\Core\Entity\EntityFormInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityForm;

  /**
   * A fake entity type used in the test.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityForm = new EntityForm();
    $this->entityType = new EntityType(['id' => 'entity_test']);
  }

  /**
   * Tests the form ID generation.
   *
   * @covers ::getFormId
   *
   * @dataProvider providerTestFormIds
   */
  public function testFormId($expected, $definition) {
    $this->entityType->set('entity_keys', ['bundle' => $definition['bundle']]);

    $entity = $this->getMockForAbstractClass('Drupal\Core\Entity\EntityBase', [[], $definition['entity_type']], '', TRUE, TRUE, TRUE, ['getEntityType', 'bundle']);

    $entity->expects($this->any())
      ->method('getEntityType')
      ->will($this->returnValue($this->entityType));
    $entity->expects($this->any())
      ->method('bundle')
      ->will($this->returnValue($definition['bundle']));

    $this->entityForm->setEntity($entity);
    $this->entityForm->setOperation($definition['operation']);

    $this->assertSame($expected, $this->entityForm->getFormId());
  }

  /**
   * Provides test data for testFormId().
   */
  public function providerTestFormIds() {
    return [
      ['node_article_form', [
          'entity_type' => 'node',
          'bundle' => 'article',
          'operation' => 'default',
        ],
      ],
      ['node_article_delete_form', [
          'entity_type' => 'node',
          'bundle' => 'article',
          'operation' => 'delete',
        ],
      ],
      ['user_user_form', [
          'entity_type' => 'user',
          'bundle' => 'user',
          'operation' => 'default',
        ],
      ],
      ['user_form', [
          'entity_type' => 'user',
          'bundle' => '',
          'operation' => 'default',
        ],
      ],
      ['user_delete_form', [
          'entity_type' => 'user',
          'bundle' => '',
          'operation' => 'delete',
        ],
      ],
    ];
  }

  /**
   * @covers ::copyFormValuesToEntity
   */
  public function testCopyFormValuesToEntity() {
    $entity_id = 'test_config_entity_id';
    $values = ['id' => $entity_id];
    $entity = $this->getMockBuilder('\Drupal\Tests\Core\Config\Entity\Fixtures\ConfigEntityBaseWithPluginCollections')
      ->setConstructorArgs([$values, 'test_config_entity'])
      ->setMethods(['getPluginCollections'])
      ->getMock();
    $entity->expects($this->atLeastOnce())
      ->method('getPluginCollections')
      ->willReturn(['key_controlled_by_plugin_collection' => NULL]);
    $this->entityForm->setEntity($entity);

    $form_state = (new FormState())->setValues([
      'regular_key' => 'foo',
      'key_controlled_by_plugin_collection' => 'bar',
    ]);
    $result = $this->entityForm->buildEntity([], $form_state);

    $this->assertSame($entity_id, $result->id());
    // The regular key should have a value, but the one controlled by a plugin
    // collection should not have been set.
    $this->assertSame('foo', $result->get('regular_key'));
    $this->assertNull($result->get('key_controlled_by_plugin_collection'));
  }

  /**
   * Tests EntityForm::getEntityFromRouteMatch() for edit and delete forms.
   *
   * @covers ::getEntityFromRouteMatch
   */
  public function testGetEntityFromRouteMatchEditDelete() {
    $entity = $this->prophesize(EntityInterface::class)->reveal();
    $id = $this->entityType->id();
    $route_match = new RouteMatch(
      'test_route',
      new Route('/entity-test/manage/{' . $id . '}/edit'),
      [$id => $entity],
      [$id => 1]
    );
    $actual = $this->entityForm->getEntityFromRouteMatch($route_match, $id);
    $this->assertEquals($entity, $actual);
  }

  /**
   * Tests EntityForm::getEntityFromRouteMatch() for add forms without a bundle.
   *
   * @covers ::getEntityFromRouteMatch
   */
  public function testGetEntityFromRouteMatchAdd() {
    $entity = $this->prophesize(EntityInterface::class)->reveal();
    $this->setUpStorage()->create([])->willReturn($entity);
    $route_match = new RouteMatch('test_route', new Route('/entity-test/add'));
    $actual = $this->entityForm->getEntityFromRouteMatch($route_match, $this->entityType->id());
    $this->assertEquals($entity, $actual);
  }

  /**
   * Tests EntityForm::getEntityFromRouteMatch() with a static bundle.
   *
   * @covers ::getEntityFromRouteMatch
   */
  public function testGetEntityFromRouteMatchAddStatic() {
    $entity = $this->prophesize(EntityInterface::class)->reveal();
    $bundle_key = 'bundle';
    $bundle = 'test_bundle';
    $this->entityType->set('entity_keys', ['bundle' => $bundle_key]);
    $storage = $this->setUpStorage();

    // Test without a bundle parameter in the route.
    $storage->create([])->willReturn($entity);
    $route_match = new RouteMatch('test_route', new Route('/entity-test/add'));
    $actual = $this->entityForm->getEntityFromRouteMatch($route_match, $this->entityType->id());
    $this->assertEquals($entity, $actual);

    // Test with a static bundle parameter.
    $storage->create([$bundle_key => 'test_bundle'])->willReturn($entity);
    $route_match = new RouteMatch(
      'test_route',
      new Route('/entity-test/add/{' . $bundle_key . '}'),
      [$bundle_key => $bundle],
      [$bundle_key => $bundle]
    );
    $actual = $this->entityForm->getEntityFromRouteMatch($route_match, $this->entityType->id());
    $this->assertEquals($entity, $actual);
  }

  /**
   * Tests EntityForm::getEntityFromRouteMatch() with a config entity bundle.
   *
   * @covers ::getEntityFromRouteMatch
   */
  public function testGetEntityFromRouteMatchAddEntity() {
    $entity = $this->prophesize(EntityInterface::class)->reveal();
    $bundle_entity_type_id = 'entity_test_bundle';
    $bundle = 'test_entity_bundle';
    $this->entityType->set('bundle_entity_type', $bundle_entity_type_id);
    $storage = $this->setUpStorage();

    // Test without a bundle parameter in the route.
    $storage->create([])->willReturn($entity);
    $route_match = new RouteMatch('test_route', new Route('/entity-test/add'));
    $actual = $this->entityForm->getEntityFromRouteMatch($route_match, $this->entityType->id());
    $this->assertEquals($entity, $actual);

    // Test with an entity bundle parameter.
    $storage->create(['bundle' => $bundle])->willReturn($entity);
    $bundle_entity = $this->prophesize(EntityInterface::class);
    $bundle_entity->id()->willReturn('test_entity_bundle');
    $route_match = new RouteMatch(
      'test_route',
      new Route('/entity-test/add/{entity_test_bundle}'),
      [$bundle_entity_type_id => $bundle_entity->reveal()],
      [$bundle_entity_type_id => $bundle]
    );
    $actual = $this->entityForm->getEntityFromRouteMatch($route_match, $this->entityType->id());
    $this->assertEquals($entity, $actual);
  }

  /**
   * Sets up the storage accessed via the entity type manager in the form.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy
   *   The storage prophecy.
   */
  protected function setUpStorage() {
    $storage = $this->prophesize(EntityStorageInterface::class);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getDefinition($this->entityType->id())->willReturn($this->entityType);
    $entity_type_manager->getStorage($this->entityType->id())->willReturn($storage->reveal());

    $this->entityForm->setEntityTypeManager($entity_type_manager->reveal());

    return $storage;
  }

}
