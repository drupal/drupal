<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityTypeManagerTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityHandlerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityTypeManager
 * @group Entity
 */
class EntityTypeManagerTest extends UnitTestCase {

  /**
   * The entity type manager under test.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $translationManager;

  /**
   * The plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $discovery;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $moduleHandler;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $cacheBackend;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->moduleHandler->getImplementations('entity_type_build')->willReturn([]);
    $this->moduleHandler->alter('entity_type', Argument::type('array'))->willReturn(NULL);

    $this->cacheBackend = $this->prophesize(CacheBackendInterface::class);
    $this->translationManager = $this->prophesize(TranslationInterface::class);

    $this->entityTypeManager = new TestEntityTypeManager(new \ArrayObject(), $this->moduleHandler->reveal(), $this->cacheBackend->reveal(), $this->translationManager->reveal(), $this->getClassResolverStub());
    $this->discovery = $this->prophesize(DiscoveryInterface::class);
    $this->entityTypeManager->setDiscovery($this->discovery->reveal());
  }

  /**
   * Sets up the entity type manager to be tested.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[]|\Prophecy\Prophecy\ProphecyInterface[] $definitions
   *   (optional) An array of entity type definitions.
   */
  protected function setUpEntityTypeDefinitions($definitions = []) {
    $class = $this->getMockClass(EntityInterface::class);
    foreach ($definitions as $key => $entity_type) {
      // \Drupal\Core\Entity\EntityTypeInterface::getLinkTemplates() is called
      // by \Drupal\Core\Entity\EntityManager::processDefinition() so it must
      // always be mocked.
      $entity_type->getLinkTemplates()->willReturn([]);

      // Give the entity type a legitimate class to return.
      $entity_type->getClass()->willReturn($class);

      $definitions[$key] = $entity_type->reveal();
    }

    $this->discovery->getDefinition(Argument::cetera())
      ->will(function ($args) use ($definitions) {
        $entity_type_id = $args[0];
        $exception_on_invalid = $args[1];
        if (isset($definitions[$entity_type_id])) {
          return $definitions[$entity_type_id];
        }
        elseif (!$exception_on_invalid) {
          return NULL;
        }
        else throw new PluginNotFoundException($entity_type_id);
      });
    $this->discovery->getDefinitions()->willReturn($definitions);

  }

  /**
   * Tests the hasHandler() method.
   *
   * @covers ::hasHandler
   *
   * @dataProvider providerTestHasHandler
   */
  public function testHasHandler($entity_type_id, $expected) {
    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->hasHandlerClass('storage')->willReturn(TRUE);

    $banana = $this->prophesize(EntityTypeInterface::class);
    $banana->hasHandlerClass('storage')->willReturn(FALSE);

    $this->setUpEntityTypeDefinitions([
      'apple' => $apple,
      'banana' => $banana,
    ]);

    $entity_type = $this->entityTypeManager->hasHandler($entity_type_id, 'storage');
    $this->assertSame($expected, $entity_type);
  }

  /**
   * Provides test data for testHasHandler().
   *
   * @return array
   *   Test data.
   */
  public function providerTestHasHandler() {
    return [
      ['apple', TRUE],
      ['banana', FALSE],
      ['pear', FALSE],
    ];
  }

  /**
   * Tests the getStorage() method.
   *
   * @covers ::getStorage
   */
  public function testGetStorage() {
    $class = $this->getTestHandlerClass();
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('storage')->willReturn($class);
    $this->setUpEntityTypeDefinitions(['test_entity_type' => $entity]);

    $this->assertInstanceOf($class, $this->entityTypeManager->getStorage('test_entity_type'));
  }

  /**
   * Tests the getListBuilder() method.
   *
   * @covers ::getListBuilder
   */
  public function testGetListBuilder() {
    $class = $this->getTestHandlerClass();
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('list_builder')->willReturn($class);
    $this->setUpEntityTypeDefinitions(['test_entity_type' => $entity]);

    $this->assertInstanceOf($class, $this->entityTypeManager->getListBuilder('test_entity_type'));
  }

  /**
   * Tests the getViewBuilder() method.
   *
   * @covers ::getViewBuilder
   */
  public function testGetViewBuilder() {
    $class = $this->getTestHandlerClass();
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('view_builder')->willReturn($class);
    $this->setUpEntityTypeDefinitions(['test_entity_type' => $entity]);

    $this->assertInstanceOf($class, $this->entityTypeManager->getViewBuilder('test_entity_type'));
  }

  /**
   * Tests the getAccessControlHandler() method.
   *
   * @covers ::getAccessControlHandler
   */
  public function testGetAccessControlHandler() {
    $class = $this->getTestHandlerClass();
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('access')->willReturn($class);
    $this->setUpEntityTypeDefinitions(['test_entity_type' => $entity]);

    $this->assertInstanceOf($class, $this->entityTypeManager->getAccessControlHandler('test_entity_type'));
  }

  /**
   * Tests the getFormObject() method.
   *
   * @covers ::getFormObject
   */
  public function testGetFormObject() {
    $entity_manager = $this->prophesize(EntityManagerInterface::class);
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('entity.manager')->willReturn($entity_manager->reveal());
    \Drupal::setContainer($container->reveal());

    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->getFormClass('default')->willReturn(TestEntityForm::class);

    $banana = $this->prophesize(EntityTypeInterface::class);
    $banana->getFormClass('default')->willReturn(TestEntityFormInjected::class);

    $this->setUpEntityTypeDefinitions([
      'apple' => $apple,
      'banana' => $banana,
    ]);

    $apple_form = $this->entityTypeManager->getFormObject('apple', 'default');
    $this->assertInstanceOf(TestEntityForm::class, $apple_form);
    $this->assertAttributeInstanceOf(ModuleHandlerInterface::class, 'moduleHandler', $apple_form);
    $this->assertAttributeInstanceOf(TranslationInterface::class, 'stringTranslation', $apple_form);

    $banana_form = $this->entityTypeManager->getFormObject('banana', 'default');
    $this->assertInstanceOf(TestEntityFormInjected::class, $banana_form);
    $this->assertAttributeEquals('yellow', 'color', $banana_form);

  }

  /**
   * Tests the getFormObject() method with an invalid operation.
   *
   * @covers ::getFormObject
   *
   * @expectedException \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function testGetFormObjectInvalidOperation() {
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getFormClass('edit')->willReturn('');
    $this->setUpEntityTypeDefinitions(['test_entity_type' => $entity]);

    $this->entityTypeManager->getFormObject('test_entity_type', 'edit');
  }

  /**
   * Tests the getHandler() method.
   *
   * @covers ::getHandler
   */
  public function testGetHandler() {
    $class = $this->getTestHandlerClass();
    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->getHandlerClass('storage')->willReturn($class);

    $this->setUpEntityTypeDefinitions([
      'apple' => $apple,
    ]);

    $apple_controller = $this->entityTypeManager->getHandler('apple', 'storage');
    $this->assertInstanceOf($class, $apple_controller);
    $this->assertAttributeInstanceOf(ModuleHandlerInterface::class, 'moduleHandler', $apple_controller);
    $this->assertAttributeInstanceOf(TranslationInterface::class, 'stringTranslation', $apple_controller);
  }

  /**
   * Tests the getHandler() method when no controller is defined.
   *
   * @covers ::getHandler
   *
   * @expectedException \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function testGetHandlerMissingHandler() {
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('storage')->willReturn('');
    $this->setUpEntityTypeDefinitions(['test_entity_type' => $entity]);
    $this->entityTypeManager->getHandler('test_entity_type', 'storage');
  }

  /**
   * @covers ::getRouteProviders
   */
  public function testGetRouteProviders() {
    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->getRouteProviderClasses()->willReturn(['default' => TestRouteProvider::class]);

    $this->setUpEntityTypeDefinitions([
      'apple' => $apple,
    ]);

    $apple_route_provider = $this->entityTypeManager->getRouteProviders('apple');
    $this->assertInstanceOf(TestRouteProvider::class, $apple_route_provider['default']);
    $this->assertAttributeInstanceOf(ModuleHandlerInterface::class, 'moduleHandler', $apple_route_provider['default']);
    $this->assertAttributeInstanceOf(TranslationInterface::class, 'stringTranslation', $apple_route_provider['default']);
  }

  /**
   * Tests the processDefinition() method.
   *
   * @covers ::processDefinition
   *
   * @expectedException \Drupal\Core\Entity\Exception\InvalidLinkTemplateException
   * @expectedExceptionMessage Link template 'canonical' for entity type 'apple' must start with a leading slash, the current link template is 'path/to/apple'
   */
  public function testProcessDefinition() {
    $apple = $this->prophesize(EntityTypeInterface::class);
    $this->setUpEntityTypeDefinitions(['apple' => $apple]);

    $apple->getLinkTemplates()->willReturn(['canonical' => 'path/to/apple']);

    $definition = $apple->reveal();
    $this->entityTypeManager->processDefinition($definition, 'apple');
  }

  /**
   * Tests the getDefinition() method.
   *
   * @covers ::getDefinition
   *
   * @dataProvider providerTestGetDefinition
   */
  public function testGetDefinition($entity_type_id, $expected) {
    $entity = $this->prophesize(EntityTypeInterface::class);

    $this->setUpEntityTypeDefinitions([
      'apple' => $entity,
      'banana' => $entity,
    ]);

    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id, FALSE);
    if ($expected) {
      $this->assertInstanceOf(EntityTypeInterface::class, $entity_type);
    }
    else {
      $this->assertNull($entity_type);
    }
  }

  /**
   * Provides test data for testGetDefinition().
   *
   * @return array
   *   Test data.
   */
  public function providerTestGetDefinition() {
    return [
      ['apple', TRUE],
      ['banana', TRUE],
      ['pear', FALSE],
    ];
  }

  /**
   * Tests the getDefinition() method with an invalid definition.
   *
   * @covers ::getDefinition
   *
   * @expectedException \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @expectedExceptionMessage The "pear" entity type does not exist.
   */
  public function testGetDefinitionInvalidException() {
    $this->setUpEntityTypeDefinitions();

    $this->entityTypeManager->getDefinition('pear', TRUE);
  }

  /**
   * Gets a mock controller class name.
   *
   * @return string
   *   A mock controller class name.
   */
  protected function getTestHandlerClass() {
    return get_class($this->getMockForAbstractClass(EntityHandlerBase::class));
  }

}

class TestEntityTypeManager extends EntityTypeManager {

  /**
   * Sets the discovery for the manager.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $discovery
   *   The discovery object.
   */
  public function setDiscovery(DiscoveryInterface $discovery) {
    $this->discovery = $discovery;
  }

}

/**
 * Provides a test entity form.
 */
class TestEntityForm extends EntityHandlerBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'the_base_form_id';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'the_form_id';
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOperation($operation) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityManager(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

}

/**
 * Provides a test entity form that uses injection.
 */
class TestEntityFormInjected extends TestEntityForm implements ContainerInjectionInterface {

  /**
   * The color of the entity type.
   *
   * @var string
   */
  protected $color;

  /**
   * Constructs a new TestEntityFormInjected.
   *
   * @param string $color
   *   The color of the entity type.
   */
  public function __construct($color) {
    $this->color = $color;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static('yellow');
  }

}

/**
 * Provides a test entity route provider.
 */
class TestRouteProvider extends EntityHandlerBase {

}
