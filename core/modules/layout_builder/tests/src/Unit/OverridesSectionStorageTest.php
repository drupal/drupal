<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\SectionStorage\SectionStorageDefinition;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage
 *
 * @group layout_builder
 */
class OverridesSectionStorageTest extends UnitTestCase {

  /**
   * The plugin.
   *
   * @var \Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage
   */
  protected $plugin;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
    $section_storage_manager = $this->prophesize(SectionStorageManagerInterface::class);
    $this->entityRepository = $this->prophesize(EntityRepositoryInterface::class);
    $account = $this->prophesize(AccountInterface::class);

    $definition = new SectionStorageDefinition([
      'id' => 'overrides',
      'class' => OverridesSectionStorage::class,
    ]);
    $this->plugin = new OverridesSectionStorage([], 'overrides', $definition, $this->entityTypeManager->reveal(), $this->entityFieldManager->reveal(), $section_storage_manager->reveal(), $this->entityRepository->reveal(), $account->reveal());
  }

  /**
   * @covers ::extractEntityFromRoute
   *
   * @dataProvider providerTestExtractEntityFromRoute
   *
   * @param bool $success
   *   Whether a successful result is expected.
   * @param string|null $expected_entity_type_id
   *   The expected entity type ID.
   * @param string $value
   *   The value to pass to ::extractEntityFromRoute().
   * @param array $defaults
   *   The defaults to pass to ::extractEntityFromRoute().
   */
  public function testExtractEntityFromRoute($success, $expected_entity_type_id, $value, array $defaults) {
    if ($expected_entity_type_id) {
      $entity_without_layout = $this->prophesize(FieldableEntityInterface::class);
      $entity_without_layout->hasField(OverridesSectionStorage::FIELD_NAME)->willReturn(FALSE);
      $this->entityRepository->getActive($expected_entity_type_id, 'entity_without_layout')->willReturn($entity_without_layout->reveal());

      $entity_with_layout = $this->prophesize(FieldableEntityInterface::class);
      $entity_with_layout->hasField(OverridesSectionStorage::FIELD_NAME)->willReturn(TRUE);
      $this->entityRepository->getActive($expected_entity_type_id, 'entity_with_layout')->willReturn($entity_with_layout->reveal());

      $entity_type = new EntityType([
        'id' => $expected_entity_type_id,
      ]);
      $this->entityTypeManager->getDefinition($expected_entity_type_id)->willReturn($entity_type);
    }
    else {
      $this->entityRepository->getActive(Argument::any())->shouldNotBeCalled();
    }

    $method = new \ReflectionMethod($this->plugin, 'extractEntityFromRoute');
    $method->setAccessible(TRUE);
    $result = $method->invoke($this->plugin, $value, $defaults);
    if ($success) {
      $this->assertInstanceOf(FieldableEntityInterface::class, $result);
    }
    else {
      $this->assertNull($result);
    }
  }

  /**
   * Provides data for ::testExtractEntityFromRoute().
   */
  public function providerTestExtractEntityFromRoute() {
    // Data provider values are:
    // - whether a successful result is expected
    // - the expected entity ID
    // - the value to pass to ::extractEntityFromRoute()
    // - the defaults to pass to ::extractEntityFromRoute().
    $data = [];
    $data['with value, with layout'] = [
      TRUE,
      'my_entity_type',
      'my_entity_type.entity_with_layout',
      [],
    ];
    $data['with value, without layout'] = [
      FALSE,
      'my_entity_type',
      'my_entity_type.entity_without_layout',
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

  /**
   * @covers ::buildRoutes
   * @covers ::hasIntegerId
   * @covers ::getEntityTypes
   * @covers \Drupal\layout_builder\Routing\LayoutBuilderRoutesTrait::buildLayoutRoutes
   */
  public function testBuildRoutes() {
    $entity_types = [];

    $not_fieldable = $this->prophesize(EntityTypeInterface::class);
    $not_fieldable->entityClassImplements(FieldableEntityInterface::class)->willReturn(FALSE);
    $entity_types['not_fieldable'] = $not_fieldable->reveal();

    $no_layout_builder_form = $this->prophesize(EntityTypeInterface::class);
    $no_layout_builder_form->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);
    $no_layout_builder_form->hasHandlerClass('form', 'layout_builder')->willReturn(FALSE);
    $entity_types['no_layout_builder_form'] = $no_layout_builder_form->reveal();
    $this->entityFieldManager->getFieldStorageDefinitions('no_layout_builder_form')->shouldNotBeCalled();

    $no_view_builder = $this->prophesize(EntityTypeInterface::class);
    $no_view_builder->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);
    $no_view_builder->hasViewBuilderClass()->willReturn(FALSE);
    $no_view_builder->hasHandlerClass('form', 'layout_builder')->willReturn(TRUE);
    $entity_types['no_view_builder'] = $no_view_builder->reveal();

    $no_canonical_link = $this->prophesize(EntityTypeInterface::class);
    $no_canonical_link->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);
    $no_canonical_link->hasViewBuilderClass()->willReturn(TRUE);
    $no_canonical_link->hasLinkTemplate('canonical')->willReturn(FALSE);
    $no_canonical_link->hasHandlerClass('form', 'layout_builder')->willReturn(TRUE);
    $entity_types['no_canonical_link'] = $no_canonical_link->reveal();
    $this->entityFieldManager->getFieldStorageDefinitions('no_canonical_link')->shouldNotBeCalled();

    $canonical_link_no_route = $this->prophesize(EntityTypeInterface::class);
    $canonical_link_no_route->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);
    $canonical_link_no_route->hasViewBuilderClass()->willReturn(TRUE);
    $canonical_link_no_route->hasLinkTemplate('canonical')->willReturn(TRUE);
    $canonical_link_no_route->getLinkTemplate('canonical')->willReturn('/entity/{entity}');
    $canonical_link_no_route->hasHandlerClass('form', 'layout_builder')->willReturn(TRUE);
    $entity_types['canonical_link_no_route'] = $canonical_link_no_route->reveal();
    $this->entityFieldManager->getFieldStorageDefinitions('canonical_link_no_route')->shouldNotBeCalled();

    $from_canonical = $this->prophesize(EntityTypeInterface::class);
    $from_canonical->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);
    $from_canonical->hasViewBuilderClass()->willReturn(TRUE);
    $from_canonical->hasLinkTemplate('canonical')->willReturn(TRUE);
    $from_canonical->getLinkTemplate('canonical')->willReturn('/entity/{entity}');
    $from_canonical->hasHandlerClass('form', 'layout_builder')->willReturn(TRUE);
    $entity_types['from_canonical'] = $from_canonical->reveal();

    $with_string_id = $this->prophesize(EntityTypeInterface::class);
    $with_string_id->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);
    $with_string_id->hasViewBuilderClass()->willReturn(TRUE);
    $with_string_id->hasLinkTemplate('canonical')->willReturn(TRUE);
    $with_string_id->getLinkTemplate('canonical')->willReturn('/entity/{entity}');
    $with_string_id->hasHandlerClass('form', 'layout_builder')->willReturn(TRUE);
    $with_string_id->id()->willReturn('with_string_id');
    $with_string_id->getKey('id')->willReturn('id');
    $entity_types['with_string_id'] = $with_string_id->reveal();
    $string_id = $this->prophesize(FieldStorageDefinitionInterface::class);
    $string_id->getType()->willReturn('string');
    $this->entityFieldManager->getFieldStorageDefinitions('with_string_id')->willReturn(['id' => $string_id->reveal()]);

    $with_integer_id = $this->prophesize(EntityTypeInterface::class);
    $with_integer_id->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);
    $with_integer_id->hasViewBuilderClass()->willReturn(TRUE);
    $with_integer_id->hasLinkTemplate('canonical')->willReturn(TRUE);
    $with_integer_id->getLinkTemplate('canonical')->willReturn('/entity/{entity}');
    $with_integer_id->hasHandlerClass('form', 'layout_builder')->willReturn(TRUE);
    $with_integer_id->id()->willReturn('with_integer_id');
    $with_integer_id->getKey('id')->willReturn('id');
    $entity_types['with_integer_id'] = $with_integer_id->reveal();
    $integer_id = $this->prophesize(FieldStorageDefinitionInterface::class);
    $integer_id->getType()->willReturn('integer');
    $this->entityFieldManager->getFieldStorageDefinitions('with_integer_id')->willReturn(['id' => $integer_id->reveal()]);

    $this->entityTypeManager->getDefinitions()->willReturn($entity_types);

    $expected = [
      'entity.from_canonical.canonical' => new Route(
        '/entity/{entity}',
        [],
        [
          'custom requirement' => 'from_canonical_route',
        ]
      ),
      'entity.with_string_id.canonical' => new Route(
        '/entity/{entity}'
      ),
      'entity.with_integer_id.canonical' => new Route(
        '/entity/{entity}',
        [],
        [
          'with_integer_id' => '\d+',
        ]
      ),
      'layout_builder.overrides.from_canonical.view' => new Route(
        '/entity/{entity}/layout',
        [
          'entity_type_id' => 'from_canonical',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_entity_form' => 'from_canonical.layout_builder',
          '_title_callback' => '\Drupal\layout_builder\Controller\LayoutBuilderController::title',
        ],
        [
          '_layout_builder_access' => 'view',
          'custom requirement' => 'from_canonical_route',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'from_canonical' => ['type' => 'entity:from_canonical'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'layout_builder.overrides.from_canonical.discard_changes' => new Route(
        '/entity/{entity}/layout/discard-changes',
        [
          'entity_type_id' => 'from_canonical',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_form' => '\Drupal\layout_builder\Form\DiscardLayoutChangesForm',
        ],
        [
          '_layout_builder_access' => 'view',
          'custom requirement' => 'from_canonical_route',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'from_canonical' => ['type' => 'entity:from_canonical'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'layout_builder.overrides.from_canonical.revert' => new Route(
        '/entity/{entity}/layout/revert',
        [
          'entity_type_id' => 'from_canonical',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_form' => '\Drupal\layout_builder\Form\RevertOverridesForm',
        ],
        [
          '_layout_builder_access' => 'view',
          'custom requirement' => 'from_canonical_route',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'from_canonical' => ['type' => 'entity:from_canonical'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'layout_builder.overrides.with_string_id.view' => new Route(
        '/entity/{entity}/layout',
        [
          'entity_type_id' => 'with_string_id',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_title_callback' => '\Drupal\layout_builder\Controller\LayoutBuilderController::title',
          '_entity_form' => 'with_string_id.layout_builder',
        ],
        [
          '_layout_builder_access' => 'view',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_string_id' => ['type' => 'entity:with_string_id'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'layout_builder.overrides.with_string_id.discard_changes' => new Route(
        '/entity/{entity}/layout/discard-changes',
        [
          'entity_type_id' => 'with_string_id',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_form' => '\Drupal\layout_builder\Form\DiscardLayoutChangesForm',
        ],
        [
          '_layout_builder_access' => 'view',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_string_id' => ['type' => 'entity:with_string_id'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'layout_builder.overrides.with_string_id.revert' => new Route(
        '/entity/{entity}/layout/revert',
        [
          'entity_type_id' => 'with_string_id',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_form' => '\Drupal\layout_builder\Form\RevertOverridesForm',
        ],
        [
          '_layout_builder_access' => 'view',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_string_id' => ['type' => 'entity:with_string_id'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'layout_builder.overrides.with_integer_id.view' => new Route(
        '/entity/{entity}/layout',
        [
          'entity_type_id' => 'with_integer_id',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_title_callback' => '\Drupal\layout_builder\Controller\LayoutBuilderController::title',
          '_entity_form' => 'with_integer_id.layout_builder',
        ],
        [
          '_layout_builder_access' => 'view',
          'with_integer_id' => '\d+',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_integer_id' => ['type' => 'entity:with_integer_id'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'layout_builder.overrides.with_integer_id.discard_changes' => new Route(
        '/entity/{entity}/layout/discard-changes',
        [
          'entity_type_id' => 'with_integer_id',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_form' => '\Drupal\layout_builder\Form\DiscardLayoutChangesForm',
        ],
        [
          '_layout_builder_access' => 'view',
          'with_integer_id' => '\d+',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_integer_id' => ['type' => 'entity:with_integer_id'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'layout_builder.overrides.with_integer_id.revert' => new Route(
        '/entity/{entity}/layout/revert',
        [
          'entity_type_id' => 'with_integer_id',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_form' => '\Drupal\layout_builder\Form\RevertOverridesForm',
        ],
        [
          '_layout_builder_access' => 'view',
          'with_integer_id' => '\d+',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_integer_id' => ['type' => 'entity:with_integer_id'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
    ];

    $collection = new RouteCollection();
    // Entity types that declare a link template for canonical must have a
    // canonical route present in the route colletion.
    $collection->add('entity.from_canonical.canonical', $expected['entity.from_canonical.canonical']);
    $collection->add('entity.with_string_id.canonical', $expected['entity.with_string_id.canonical']);
    $collection->add('entity.with_integer_id.canonical', $expected['entity.with_integer_id.canonical']);

    $this->plugin->buildRoutes($collection);
    $this->assertEquals($expected, $collection->all());
    $this->assertSame(array_keys($expected), array_keys($collection->all()));
  }

}
