<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityTypeBundleInfo
 * @group Entity
 */
class EntityTypeBundleInfoTest extends UnitTestCase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $moduleHandler;

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $cacheBackend;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $typedDataManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type bundle info under test.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->moduleHandler->getImplementations('entity_type_build')->willReturn([]);
    $this->moduleHandler->alter('entity_type', Argument::type('array'))->willReturn(NULL);

    $this->cacheBackend = $this->prophesize(CacheBackendInterface::class);

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);

    $this->cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $language = new Language(['id' => 'en']);
    $this->languageManager = $this->prophesize(LanguageManagerInterface::class);
    $this->languageManager->getCurrentLanguage()->willReturn($language);
    $this->languageManager->getLanguages()->willReturn(['en' => (object) ['id' => 'en']]);

    $this->typedDataManager = $this->prophesize(TypedDataManagerInterface::class);

    $this->cacheBackend = $this->prophesize(CacheBackendInterface::class);

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('cache_tags.invalidator')->willReturn($this->cacheTagsInvalidator->reveal());
    //$container->get('typed_data_manager')->willReturn($this->typedDataManager->reveal());
    \Drupal::setContainer($container->reveal());

    $this->entityTypeBundleInfo = new EntityTypeBundleInfo($this->entityTypeManager->reveal(), $this->languageManager->reveal(), $this->moduleHandler->reveal(), $this->typedDataManager->reveal(), $this->cacheBackend->reveal());
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

    $this->entityTypeManager->getDefinition(Argument::cetera())
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
    $this->entityTypeManager->getDefinitions()->willReturn($definitions);

  }

  /**
   * Tests the clearCachedBundles() method.
   *
   * @covers ::clearCachedBundles
   */
  public function testClearCachedBundles() {
    $this->setUpEntityTypeDefinitions();

    $this->typedDataManager->clearCachedDefinitions()->shouldBeCalled();

    $this->cacheTagsInvalidator->invalidateTags(['entity_bundles'])->shouldBeCalled();

    $this->entityTypeBundleInfo->clearCachedBundles();
  }

  /**
   * Tests the getBundleInfo() method.
   *
   * @covers ::getBundleInfo
   *
   * @dataProvider providerTestGetBundleInfo
   */
  public function testGetBundleInfo($entity_type_id, $expected) {
    $this->moduleHandler->invokeAll('entity_bundle_info')->willReturn([]);
    $this->moduleHandler->alter('entity_bundle_info', Argument::type('array'))->willReturn(NULL);

    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->getLabel()->willReturn('Apple');
    $apple->getBundleEntityType()->willReturn(NULL);

    $banana = $this->prophesize(EntityTypeInterface::class);
    $banana->getLabel()->willReturn('Banana');
    $banana->getBundleEntityType()->willReturn(NULL);

    $this->setUpEntityTypeDefinitions([
      'apple' => $apple,
      'banana' => $banana,
    ]);

    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    $this->assertSame($expected, $bundle_info);
  }

  /**
   * Provides test data for testGetBundleInfo().
   *
   * @return array
   *   Test data.
   */
  public function providerTestGetBundleInfo() {
    return [
      ['apple', [
        'apple' => [
          'label' => 'Apple',
        ],
      ]],
      ['banana', [
        'banana' => [
          'label' => 'Banana',
        ],
      ]],
      ['pear', []],
    ];
  }

  /**
   * Tests the getAllBundleInfo() method.
   *
   * @covers ::getAllBundleInfo
   */
  public function testGetAllBundleInfo() {
    $this->moduleHandler->invokeAll('entity_bundle_info')->willReturn([]);
    $this->moduleHandler->alter('entity_bundle_info', Argument::type('array'))->willReturn(NULL);

    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->getLabel()->willReturn('Apple');
    $apple->getBundleEntityType()->willReturn(NULL);

    $banana = $this->prophesize(EntityTypeInterface::class);
    $banana->getLabel()->willReturn('Banana');
    $banana->getBundleEntityType()->willReturn(NULL);

    $this->setUpEntityTypeDefinitions([
      'apple' => $apple,
      'banana' => $banana,
    ]);

    $this->cacheBackend->get('entity_bundle_info:en')->willReturn(FALSE);
    $this->cacheBackend->set('entity_bundle_info:en', Argument::any(), Cache::PERMANENT, ['entity_types', 'entity_bundles'])
      ->will(function () {
        $this->get('entity_bundle_info:en')
          ->willReturn((object) ['data' => 'cached data'])
          ->shouldBeCalled();
      })
      ->shouldBeCalled();

    $this->cacheTagsInvalidator->invalidateTags(['entity_bundles'])->shouldBeCalled();

    $this->typedDataManager->clearCachedDefinitions()->shouldBeCalled();

    $expected = [
      'apple' => [
        'apple' => [
          'label' => 'Apple',
        ],
      ],
      'banana' => [
        'banana' => [
          'label' => 'Banana',
        ],
      ],
    ];
    $bundle_info = $this->entityTypeBundleInfo->getAllBundleInfo();
    $this->assertSame($expected, $bundle_info);

    $bundle_info = $this->entityTypeBundleInfo->getAllBundleInfo();
    $this->assertSame($expected, $bundle_info);

    $this->entityTypeBundleInfo->clearCachedBundles();

    $bundle_info = $this->entityTypeBundleInfo->getAllBundleInfo();
    $this->assertSame('cached data', $bundle_info);
  }

  /**
   * @covers ::getAllBundleInfo
   */
  public function testGetAllBundleInfoWithEntityBundleInfo() {
    // Ensure that EntityTypeBundleInfo::getAllBundleInfo() does not add
    // additional bundles if hook_entity_bundle_info() defines some and the
    // entity_type does not define a bundle entity type.
    $this->moduleHandler->invokeAll('entity_bundle_info')->willReturn([
      'banana' => [
        'fig' => [
          'label' => 'Fig banana',
        ],
      ],
    ]);
    $this->moduleHandler->alter('entity_bundle_info', Argument::type('array'))->willReturn(NULL);

    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->getLabel()->willReturn('Apple');
    $apple->getBundleEntityType()->willReturn(NULL);

    $banana = $this->prophesize(EntityTypeInterface::class);
    $banana->getLabel()->willReturn('Banana');
    $banana->getBundleEntityType()->willReturn(NULL);

    $this->setUpEntityTypeDefinitions([
      'apple' => $apple,
      'banana' => $banana,
    ]);

    $expected = [
      'banana' => [
        'fig' => [
          'label' => 'Fig banana',
        ],
      ],
      'apple' => [
        'apple' => [
          'label' => 'Apple',
        ],
      ],
    ];
    $bundle_info = $this->entityTypeBundleInfo->getAllBundleInfo();
    $this->assertSame($expected, $bundle_info);
  }

}
