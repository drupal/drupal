<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityHandlerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Exception\InvalidLinkTemplateException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Container;
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
   * The entity last installed schema repository.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityLastInstalledSchemaRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);

    $this->cacheBackend = $this->prophesize(CacheBackendInterface::class);
    $this->translationManager = $this->prophesize(TranslationInterface::class);
    $this->entityLastInstalledSchemaRepository = $this->prophesize(EntityLastInstalledSchemaRepositoryInterface::class);
    $container = $this->prophesize(Container::class);

    $this->entityTypeManager = new TestEntityTypeManager(new \ArrayObject(), $this->moduleHandler->reveal(), $this->cacheBackend->reveal(), $this->translationManager->reveal(), $this->getClassResolverStub(), $this->entityLastInstalledSchemaRepository->reveal(), $container->reveal());
    $this->discovery = $this->prophesize(DiscoveryInterface::class);
    $this->entityTypeManager->setDiscovery($this->discovery->reveal());
  }

  /**
   * Sets up the entity type manager to be tested.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[]|\Prophecy\Prophecy\ProphecyInterface[] $definitions
   *   (optional) An array of entity type definitions.
   */
  protected function setUpEntityTypeDefinitions($definitions = []): void {
    $class = get_class($this->createMock(EntityInterface::class));
    foreach ($definitions as $key => $entity_type) {
      // \Drupal\Core\Entity\EntityTypeInterface::getLinkTemplates() is called
      // by \Drupal\Core\Entity\EntityTypeManager::processDefinition() so it
      // must always be mocked.
      $entity_type->getLinkTemplates()->willReturn([]);

      // Give the entity type a legitimate class to return.
      $entity_type->getClass()->willReturn($class);
      $entity_type->setClass($class)->willReturn($entity_type->reveal());

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
        else {
          throw new PluginNotFoundException($entity_type_id);
        }
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
  public function testHasHandler($entity_type_id, $expected): void {
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
  public static function providerTestHasHandler() {
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
  public function testGetStorage(): void {
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('storage')->willReturn(StubEntityHandlerBase::class);
    $this->setUpEntityTypeDefinitions(['test_entity_type' => $entity]);

    $this->assertInstanceOf(StubEntityHandlerBase::class, $this->entityTypeManager->getStorage('test_entity_type'));
  }

  /**
   * Tests the getListBuilder() method.
   *
   * @covers ::getListBuilder
   */
  public function testGetListBuilder(): void {
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('list_builder')->willReturn(StubEntityHandlerBase::class);
    $this->setUpEntityTypeDefinitions(['test_entity_type' => $entity]);

    $this->assertInstanceOf(StubEntityHandlerBase::class, $this->entityTypeManager->getListBuilder('test_entity_type'));
  }

  /**
   * Tests the getViewBuilder() method.
   *
   * @covers ::getViewBuilder
   */
  public function testGetViewBuilder(): void {
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('view_builder')->willReturn(StubEntityHandlerBase::class);
    $this->setUpEntityTypeDefinitions(['test_entity_type' => $entity]);

    $this->assertInstanceOf(StubEntityHandlerBase::class, $this->entityTypeManager->getViewBuilder('test_entity_type'));
  }

  /**
   * Tests the getAccessControlHandler() method.
   *
   * @covers ::getAccessControlHandler
   */
  public function testGetAccessControlHandler(): void {
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('access')->willReturn(StubEntityHandlerBase::class);
    $this->setUpEntityTypeDefinitions(['test_entity_type' => $entity]);

    $this->assertInstanceOf(StubEntityHandlerBase::class, $this->entityTypeManager->getAccessControlHandler('test_entity_type'));
  }

  /**
   * Tests the getFormObject() method.
   *
   * @covers ::getFormObject
   */
  public function testGetFormObject(): void {
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
    $this->assertInstanceOf(ModuleHandlerInterface::class, $apple_form->moduleHandler);
    $this->assertInstanceOf(TranslationInterface::class, $apple_form->stringTranslation);

    $banana_form = $this->entityTypeManager->getFormObject('banana', 'default');
    $this->assertInstanceOf(TestEntityFormInjected::class, $banana_form);
    $this->assertEquals('yellow', $banana_form->color);
  }

  /**
   * Provides test data for testGetFormObjectInvalidOperation().
   *
   * @return array
   *   Test data.
   */
  public static function provideFormObjectInvalidOperationData(): array {
    return [
      'missing_form_handler' => [
        'test_entity_type',
        'edit',
        '',
        'The "test_entity_type" entity type did not specify a "edit" form class.',
      ],
      'missing_form_handler_class' => [
        'test_entity_type',
        'edit',
        'Drupal\test_entity_type\Form\NonExistingClass',
        'The "edit" form handler of the "test_entity_type" entity type specifies a non-existent class "Drupal\test_entity_type\Form\NonExistingClass".',
      ],
    ];
  }

  /**
   * Tests the getFormObject() method with an invalid operation.
   *
   * @covers ::getFormObject
   *
   * @dataProvider provideFormObjectInvalidOperationData
   */
  public function testGetFormObjectInvalidOperation(string $entity_type_id, string $operation, string $form_class, string $exception_message): void {
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getFormClass($operation)->willReturn(NULL);
    if (!$form_class) {
      $entity->getHandlerClasses()->willReturn([]);
    }
    else {
      $entity->getHandlerClasses()->willReturn([
        'form' => [$operation => $form_class],
      ]);
    }
    $this->setUpEntityTypeDefinitions([$entity_type_id => $entity]);

    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage($exception_message);
    $this->entityTypeManager->getFormObject($entity_type_id, $operation);
  }

  /**
   * Tests the getHandler() method.
   *
   * @covers ::getHandler
   */
  public function testGetHandler(): void {
    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->getHandlerClass('storage')->willReturn(StubEntityHandlerBase::class);

    $this->setUpEntityTypeDefinitions([
      'apple' => $apple,
    ]);

    $apple_controller = $this->entityTypeManager->getHandler('apple', 'storage');
    $this->assertInstanceOf(StubEntityHandlerBase::class, $apple_controller);
    $this->assertInstanceOf(ModuleHandlerInterface::class, $apple_controller->moduleHandler);
    $this->assertInstanceOf(TranslationInterface::class, $apple_controller->stringTranslation);
  }

  /**
   * Provides test data for testGetHandlerMissingHandler().
   *
   * @return array
   *   Test data.
   */
  public static function provideMissingHandlerData() : array {
    return [
      'missing_handler' => [
        'test_entity_type',
        'storage',
        '',
        'The "test_entity_type" entity type did not specify a storage handler.',
      ],
      'missing_handler_class' => [
        'test_entity_type',
        'storage',
        'Non_Existing_Class',
        'The storage handler of the "test_entity_type" entity type specifies a non-existent class "Non_Existing_Class".',
      ],
    ];
  }

  /**
   * Tests the getHandler() method when no controller is defined.
   *
   * @covers ::getHandler
   *
   * @dataProvider provideMissingHandlerData
   */
  public function testGetHandlerMissingHandler(string $entity_type, string $handler_name, string $handler_class, $exception_message) : void {
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass($handler_name)->willReturn(NULL);
    if (!$handler_class) {
      $entity->getHandlerClasses()->willReturn([]);
    }
    else {
      $entity->getHandlerClasses()->willReturn([$handler_name => $handler_class]);
    }
    $this->setUpEntityTypeDefinitions([$entity_type => $entity]);
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage($exception_message);
    $this->entityTypeManager->getHandler($entity_type, $handler_name);
  }

  /**
   * @covers ::getRouteProviders
   */
  public function testGetRouteProviders(): void {
    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->getRouteProviderClasses()->willReturn(['default' => TestRouteProvider::class]);

    $this->setUpEntityTypeDefinitions([
      'apple' => $apple,
    ]);

    $apple_route_provider = $this->entityTypeManager->getRouteProviders('apple');
    $this->assertInstanceOf(TestRouteProvider::class, $apple_route_provider['default']);
    $this->assertInstanceOf(ModuleHandlerInterface::class, $apple_route_provider['default']->moduleHandler);
    $this->assertInstanceOf(TranslationInterface::class, $apple_route_provider['default']->stringTranslation);
  }

  /**
   * Tests the processDefinition() method.
   *
   * @covers ::processDefinition
   */
  public function testProcessDefinition(): void {
    $apple = $this->prophesize(EntityTypeInterface::class);
    $this->setUpEntityTypeDefinitions(['apple' => $apple]);

    $apple->getLinkTemplates()->willReturn(['canonical' => 'path/to/apple']);

    $definition = $apple->reveal();
    $this->expectException(InvalidLinkTemplateException::class);
    $this->expectExceptionMessage("Link template 'canonical' for entity type 'apple' must start with a leading slash, the current link template is 'path/to/apple'");
    $this->entityTypeManager->processDefinition($definition, 'apple');
  }

  /**
   * Tests the getDefinition() method.
   *
   * @covers ::getDefinition
   *
   * @dataProvider providerTestGetDefinition
   */
  public function testGetDefinition($entity_type_id, $expected): void {
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
  public static function providerTestGetDefinition() {
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
   */
  public function testGetDefinitionInvalidException(): void {
    $this->setUpEntityTypeDefinitions();

    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage('The "pear" entity type does not exist.');
    $this->entityTypeManager->getDefinition('pear', TRUE);
  }

}

/**
 * Provides a test entity type manager.
 */
class TestEntityTypeManager extends EntityTypeManager {

  /**
   * Sets the discovery for the manager.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $discovery
   *   The discovery object.
   */
  public function setDiscovery(DiscoveryInterface $discovery): void {
    $this->discovery = $discovery;
  }

}

/**
 * Provides a test entity form.
 */
class TestEntityForm extends EntityHandlerBase {

  /**
   * {@inheritdoc}
   */
  public $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public $stringTranslation;

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
  public $color;

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

  /**
   * {@inheritdoc}
   */
  public $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public $stringTranslation;

}
