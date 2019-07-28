<?php

namespace Drupal\Tests\Core\Plugin\Context;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Core\Validation\ConstraintManager;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\Context\EntityContextDefinition
 * @group Plugin
 */
class EntityContextDefinitionIsSatisfiedTest extends UnitTestCase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $namespaces = new \ArrayObject([
      'Drupal\\Core\\TypedData' => $this->root . '/core/lib/Drupal/Core/TypedData',
      'Drupal\\Core\\Validation' => $this->root . '/core/lib/Drupal/Core/Validation',
      'Drupal\\Core\\Entity' => $this->root . '/core/lib/Drupal/Core/Entity',
    ]);
    $cache_backend = new NullBackend('cache');
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);

    $class_resolver = $this->prophesize(ClassResolverInterface::class);
    $class_resolver->getInstanceFromDefinition(Argument::type('string'))->will(function ($arguments) {
      $class_name = $arguments[0];
      return new $class_name();
    });

    $type_data_manager = new TypedDataManager($namespaces, $cache_backend, $module_handler->reveal(), $class_resolver->reveal());
    $type_data_manager->setValidationConstraintManager(new ConstraintManager($namespaces, $cache_backend, $module_handler->reveal()));

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityManager = $this->prophesize(EntityManagerInterface::class);

    $this->entityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);

    $string_translation = new TranslationManager(new LanguageDefault([]));

    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $type_data_manager);
    $container->set('entity_type.manager', $this->entityTypeManager->reveal());
    $container->set('entity.manager', $this->entityManager->reveal());
    $container->set('entity_type.bundle.info', $this->entityTypeBundleInfo->reveal());
    $container->set('string_translation', $string_translation);
    \Drupal::setContainer($container);
  }

  /**
   * Asserts that the requirement is satisfied as expected.
   *
   * @param bool $expected
   *   The expected outcome.
   * @param \Drupal\Core\Plugin\Context\EntityContextDefinition $requirement
   *   The requirement to check against.
   * @param \Drupal\Core\Plugin\Context\EntityContextDefinition $definition
   *   The context definition to check.
   * @param mixed $value
   *   (optional) The value to set on the context, defaults to NULL.
   */
  protected function assertRequirementIsSatisfied($expected, ContextDefinition $requirement, ContextDefinition $definition, $value = NULL) {
    $context = new EntityContext($definition, $value);
    $this->assertSame($expected, $requirement->isSatisfiedBy($context));
  }

  /**
   * @covers ::isSatisfiedBy
   * @covers ::dataTypeMatches
   * @covers ::getSampleValues
   * @covers ::getConstraintObjects
   *
   * @dataProvider providerTestIsSatisfiedBy
   */
  public function testIsSatisfiedBy($expected, ContextDefinition $requirement, ContextDefinition $definition, $value = NULL) {
    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $content_entity_storage = $this->prophesize(ContentEntityStorageInterface::class);
    $this->entityTypeManager->getStorage('test_config')->willReturn($entity_storage->reveal());
    $this->entityTypeManager->getStorage('test_content')->willReturn($content_entity_storage->reveal());

    $config_entity_type = new EntityType(['id' => 'test_config']);
    $content_entity_type = new EntityType(['id' => 'test_content']);
    $this->entityTypeManager->getDefinition('test_config')->willReturn($config_entity_type);
    $this->entityTypeManager->getDefinition('test_content')->willReturn($content_entity_type);
    $this->entityManager->getDefinitions()->willReturn([
      'test_config' => $config_entity_type,
      'test_content' => $content_entity_type,
    ]);

    $this->entityTypeBundleInfo->getBundleInfo('test_config')->willReturn([
      'test_config' => ['label' => 'test_config'],
    ]);
    $this->entityTypeBundleInfo->getBundleInfo('test_content')->willReturn([
      'test_content' => ['label' => 'test_content'],
    ]);

    $this->assertRequirementIsSatisfied($expected, $requirement, $definition, $value);
  }

  /**
   * Provides test data for ::testIsSatisfiedBy().
   */
  public function providerTestIsSatisfiedBy() {
    $data = [];

    $content = new EntityType(['id' => 'test_content']);
    $config = new EntityType(['id' => 'test_config']);

    // Entities without bundles.
    $data['content entity, matching type, no value'] = [
      TRUE,
      EntityContextDefinition::fromEntityType($content),
      EntityContextDefinition::fromEntityType($content),
    ];
    $entity = $this->prophesize(ContentEntityInterface::class)->willImplement(\IteratorAggregate::class);
    $entity->getIterator()->willReturn(new \ArrayIterator([]));
    $entity->getCacheContexts()->willReturn([]);
    $entity->getCacheTags()->willReturn([]);
    $entity->getCacheMaxAge()->willReturn(0);
    $entity->getEntityTypeId()->willReturn('test_content');
    $data['content entity, matching type, correct value'] = [
      TRUE,
      EntityContextDefinition::fromEntityType($content),
      EntityContextDefinition::fromEntityType($content),
      $entity->reveal(),
    ];
    $data['content entity, incorrect manual constraint'] = [
      TRUE,
      EntityContextDefinition::fromEntityType($content),
      EntityContextDefinition::fromEntityType($content)->addConstraint('EntityType', 'test_config'),
    ];
    $data['config entity, matching type, no value'] = [
      TRUE,
      EntityContextDefinition::fromEntityType($config),
      EntityContextDefinition::fromEntityType($config),
    ];
    $data['generic entity requirement, specific context'] = [
      TRUE,
      new ContextDefinition('entity'),
      EntityContextDefinition::fromEntityType($config),
    ];
    $data['specific requirement, generic entity context'] = [
      FALSE,
      EntityContextDefinition::fromEntityType($content),
      new ContextDefinition('entity'),
    ];

    return $data;
  }

  /**
   * @covers ::isSatisfiedBy
   * @covers ::dataTypeMatches
   * @covers ::getSampleValues
   * @covers ::getConstraintObjects
   *
   * @dataProvider providerTestIsSatisfiedByGenerateBundledEntity
   */
  public function testIsSatisfiedByGenerateBundledEntity($expected, array $requirement_bundles, array $candidate_bundles, array $bundles_to_instantiate = NULL) {
    // If no bundles are explicitly specified, instantiate all bundles.
    if (!$bundles_to_instantiate) {
      $bundles_to_instantiate = $candidate_bundles;
    }

    $content_entity_storage = $this->prophesize(ContentEntityStorageInterface::class);
    foreach ($bundles_to_instantiate as $bundle) {
      $entity = $this->prophesize(ContentEntityInterface::class)->willImplement(\IteratorAggregate::class);
      $entity->getEntityTypeId()->willReturn('test_content');
      $entity->getIterator()->willReturn(new \ArrayIterator([]));
      $entity->bundle()->willReturn($bundle);
      $content_entity_storage->create(['the_bundle_key' => $bundle])
        ->willReturn($entity->reveal())
        ->shouldBeCalled();
    }

    // Creating entities with sample values can lead to performance issues when
    // called many times. Ensure that createWithSampleValues() is not called.
    $content_entity_storage->createWithSampleValues(Argument::any())->shouldNotBeCalled();

    $entity_type = new EntityType([
      'id' => 'test_content',
      'entity_keys' => [
        'bundle' => 'the_bundle_key',
      ],
    ]);

    $this->entityTypeManager->getStorage('test_content')->willReturn($content_entity_storage->reveal());

    $this->entityTypeManager->getDefinition('test_content')->willReturn($entity_type);
    $this->entityManager->getDefinitions()->willReturn([
      'test_content' => $entity_type,
    ]);

    $this->entityTypeBundleInfo->getBundleInfo('test_content')->willReturn([
      'first_bundle' => ['label' => 'First bundle'],
      'second_bundle' => ['label' => 'Second bundle'],
      'third_bundle' => ['label' => 'Third bundle'],
    ]);

    $requirement = EntityContextDefinition::fromEntityType($entity_type);
    if ($requirement_bundles) {
      $requirement->addConstraint('Bundle', $requirement_bundles);
    }
    $definition = EntityContextDefinition::fromEntityType($entity_type)->addConstraint('Bundle', $candidate_bundles);
    $this->assertRequirementIsSatisfied($expected, $requirement, $definition);
  }

  /**
   * Provides test data for ::testIsSatisfiedByGenerateBundledEntity().
   */
  public function providerTestIsSatisfiedByGenerateBundledEntity() {
    $data = [];
    $data['no requirement'] = [
      TRUE,
      [],
      ['first_bundle'],
    ];
    $data['single requirement'] = [
      TRUE,
      ['first_bundle'],
      ['first_bundle'],
    ];
    $data['single requirement, multiple candidates, satisfies last candidate'] = [
      TRUE,
      ['third_bundle'],
      ['first_bundle', 'second_bundle', 'third_bundle'],
    ];
    $data['single requirement, multiple candidates, satisfies first candidate'] = [
      TRUE,
      ['first_bundle'],
      ['first_bundle', 'second_bundle', 'third_bundle'],
      // Once the first match is found, subsequent candidates are not checked.
      ['first_bundle'],
    ];
    $data['unsatisfied requirement'] = [
      FALSE,
      ['second_bundle'],
      ['first_bundle', 'third_bundle'],
    ];
    $data['multiple requirements'] = [
      TRUE,
      ['first_bundle', 'second_bundle'],
      ['first_bundle'],
    ];
    return $data;
  }

  /**
   * @covers ::isSatisfiedBy
   * @covers ::dataTypeMatches
   * @covers ::getSampleValues
   * @covers ::getConstraintObjects
   *
   * @dataProvider providerTestIsSatisfiedByPassBundledEntity
   */
  public function testIsSatisfiedByPassBundledEntity($expected, $requirement_constraint) {
    $entity_type = new EntityType(['id' => 'test_content']);
    $this->entityManager->getDefinitions()->willReturn([
      'test_content' => $entity_type,
    ]);
    $this->entityTypeManager->getDefinition('test_content')->willReturn($entity_type);
    $this->entityTypeManager->getStorage('test_content')->shouldNotBeCalled();

    $this->entityTypeBundleInfo->getBundleInfo('test_content')->willReturn([
      'first_bundle' => ['label' => 'First bundle'],
      'second_bundle' => ['label' => 'Second bundle'],
      'third_bundle' => ['label' => 'Third bundle'],
    ]);

    $entity = $this->prophesize(ContentEntityInterface::class)->willImplement(\IteratorAggregate::class);
    $entity->getEntityTypeId()->willReturn('test_content');
    $entity->getIterator()->willReturn(new \ArrayIterator([]));
    $entity->getCacheContexts()->willReturn([]);
    $entity->getCacheTags()->willReturn([]);
    $entity->getCacheMaxAge()->willReturn(0);
    $entity->bundle()->willReturn('third_bundle');

    $requirement = EntityContextDefinition::fromEntityTypeId('test_content');
    if ($requirement_constraint) {
      $requirement->addConstraint('Bundle', $requirement_constraint);
    }
    $definition = EntityContextDefinition::fromEntityTypeId('test_content');
    $this->assertRequirementIsSatisfied($expected, $requirement, $definition, $entity->reveal());
  }

  /**
   * Provides test data for ::testIsSatisfiedByPassBundledEntity().
   */
  public function providerTestIsSatisfiedByPassBundledEntity() {
    $data = [];
    $data[] = [TRUE, []];
    $data[] = [FALSE, ['first_bundle']];
    $data[] = [FALSE, ['second_bundle']];
    $data[] = [TRUE, ['third_bundle']];
    $data[] = [TRUE, ['first_bundle', 'second_bundle', 'third_bundle']];
    $data[] = [FALSE, ['first_bundle', 'second_bundle']];
    $data[] = [TRUE, ['first_bundle', 'third_bundle']];
    $data[] = [TRUE, ['second_bundle', 'third_bundle']];
    return $data;
  }

}
