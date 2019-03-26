<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\layout_builder\Entity\LayoutBuilderSampleEntityGenerator;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage;
use Drupal\layout_builder\SectionStorage\SectionStorageDefinition;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage
 *
 * @group layout_builder
 */
class DefaultsSectionStorageTest extends UnitTestCase {

  /**
   * The plugin.
   *
   * @var \Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage
   */
  protected $plugin;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The sample entity generator.
   *
   * @var \Drupal\layout_builder\Entity\LayoutBuilderSampleEntityGenerator
   */
  protected $sampleEntityGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_bundle_info = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $this->sampleEntityGenerator = $this->prophesize(LayoutBuilderSampleEntityGenerator::class);

    $definition = new SectionStorageDefinition([
      'id' => 'defaults',
      'class' => DefaultsSectionStorage::class,
    ]);
    $this->plugin = new DefaultsSectionStorage([], '', $definition, $this->entityTypeManager->reveal(), $entity_type_bundle_info->reveal(), $this->sampleEntityGenerator->reveal());
  }

  /**
   * @covers ::getThirdPartySetting
   * @covers ::setThirdPartySetting
   */
  public function testThirdPartySettings() {
    // Set an initial value on the section list.
    $section_list = $this->prophesize(LayoutEntityDisplayInterface::class);

    $context = $this->prophesize(ContextInterface::class);
    $context->getContextValue()->willReturn($section_list->reveal());
    $this->plugin->setContext('display', $context->reveal());

    $section_list->getThirdPartySetting('the_module', 'the_key', NULL)->willReturn('value 1');

    // The plugin returns the initial value.
    $this->assertSame('value 1', $this->plugin->getThirdPartySetting('the_module', 'the_key'));

    // When the section list is updated, also update the result returned.
    $section_list->setThirdPartySetting('the_module', 'the_key', 'value 2')->shouldBeCalled()->will(function ($args) {
      $this->getThirdPartySetting('the_module', 'the_key', NULL)->willReturn($args[2]);
    });

    // Update the plugin value.
    $this->plugin->setThirdPartySetting('the_module', 'the_key', 'value 2');
    // Assert that the returned value matches.
    $this->assertSame('value 2', $this->plugin->getThirdPartySetting('the_module', 'the_key'));
  }

  /**
   * @covers ::extractIdFromRoute
   *
   * @dataProvider providerTestExtractIdFromRoute
   *
   * @expectedDeprecation \Drupal\layout_builder\SectionStorageInterface::extractIdFromRoute() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. \Drupal\layout_builder\SectionStorageInterface::deriveContextsFromRoute() should be used instead. See https://www.drupal.org/node/3016262.
   *
   * @group legacy
   */
  public function testExtractIdFromRoute($expected, $value, array $defaults) {
    $result = $this->plugin->extractIdFromRoute($value, [], 'the_parameter_name', $defaults);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides data for ::testExtractIdFromRoute().
   */
  public function providerTestExtractIdFromRoute() {
    $data = [];
    $data['with value'] = [
      'foo.bar.baz',
      'foo.bar.baz',
      [],
    ];
    $data['empty value, without bundle'] = [
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
      'my_entity_type.bundle_name.default',
      '',
      [
        'entity_type_id' => 'my_entity_type',
        'view_mode_name' => 'default',
        'bundle' => 'bundle_name',
      ],
    ];
    $data['without value, empty defaults'] = [
      NULL,
      '',
      [],
    ];
    return $data;
  }

  /**
   * @covers ::getSectionListFromId
   *
   * @dataProvider providerTestGetSectionListFromId
   *
   * @expectedDeprecation \Drupal\layout_builder\SectionStorageInterface::getSectionListFromId() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. The section list should be derived from context. See https://www.drupal.org/node/3016262.
   *
   * @group legacy
   */
  public function testGetSectionListFromId($success, $expected_entity_id, $value) {
    if ($expected_entity_id) {
      $entity_storage = $this->prophesize(EntityStorageInterface::class);
      $entity_storage->load($expected_entity_id)->willReturn('the_return_value');

      $this->entityTypeManager->getDefinition('entity_view_display')->willReturn(new EntityType(['id' => 'entity_view_display']));
      $this->entityTypeManager->getStorage('entity_view_display')->willReturn($entity_storage->reveal());
    }
    else {
      $this->entityTypeManager->getDefinition('entity_view_display')->shouldNotBeCalled();
      $this->entityTypeManager->getStorage('entity_view_display')->shouldNotBeCalled();
    }

    if (!$success) {
      $this->setExpectedException(\InvalidArgumentException::class);
    }

    $result = $this->plugin->getSectionListFromId($value);
    if ($success) {
      $this->assertEquals('the_return_value', $result);
    }
  }

  /**
   * Provides data for ::testGetSectionListFromId().
   */
  public function providerTestGetSectionListFromId() {
    $data = [];
    $data['with value'] = [
      TRUE,
      'foo.bar.baz',
      'foo.bar.baz',
    ];
    $data['without value, empty defaults'] = [
      FALSE,
      NULL,
      '',
    ];
    return $data;
  }

  /**
   * @covers ::getSectionListFromId
   *
   * @expectedDeprecation \Drupal\layout_builder\SectionStorageInterface::getSectionListFromId() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. The section list should be derived from context. See https://www.drupal.org/node/3016262.
   *
   * @group legacy
   */
  public function testGetSectionListFromIdCreate() {
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

    $this->entityTypeManager->getDefinition('entity_view_display')->willReturn(new EntityType(['id' => 'entity_view_display']));
    $this->entityTypeManager->getStorage('entity_view_display')->willReturn($entity_storage->reveal());

    $result = $this->plugin->getSectionListFromId($value);
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::extractEntityFromRoute
   *
   * @dataProvider providerTestExtractEntityFromRoute
   *
   * @param bool $success
   *   Whether a successful result is expected.
   * @param string|null $expected_entity_id
   *   The expected entity ID.
   * @param string $value
   *   The value to pass to ::extractEntityFromRoute().
   * @param array $defaults
   *   The defaults to pass to ::extractEntityFromRoute().
   */
  public function testExtractEntityFromRoute($success, $expected_entity_id, $value, array $defaults) {
    if ($expected_entity_id) {
      $entity_storage = $this->prophesize(EntityStorageInterface::class);
      $entity_storage->load($expected_entity_id)->willReturn('the_return_value');

      $this->entityTypeManager->getDefinition('entity_view_display')->willReturn(new EntityType(['id' => 'entity_view_display']));
      $this->entityTypeManager->getStorage('entity_view_display')->willReturn($entity_storage->reveal());
    }
    else {
      $this->entityTypeManager->getDefinition('entity_view_display')->shouldNotBeCalled();
      $this->entityTypeManager->getStorage('entity_view_display')->shouldNotBeCalled();
    }

    $method = new \ReflectionMethod($this->plugin, 'extractEntityFromRoute');
    $method->setAccessible(TRUE);
    $result = $method->invoke($this->plugin, $value, $defaults);
    if ($success) {
      $this->assertEquals('the_return_value', $result);
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
    $data['with value'] = [
      TRUE,
      'foo.bar.baz',
      'foo.bar.baz',
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
   * @covers ::extractEntityFromRoute
   */
  public function testExtractEntityFromRouteCreate() {
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

    $this->entityTypeManager->getDefinition('entity_view_display')->willReturn(new EntityType(['id' => 'entity_view_display']));
    $this->entityTypeManager->getStorage('entity_view_display')->willReturn($entity_storage->reveal());

    $method = new \ReflectionMethod($this->plugin, 'extractEntityFromRoute');
    $method->setAccessible(TRUE);
    $result = $method->invoke($this->plugin, $value, []);
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::buildRoutes
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

    $no_view_builder = $this->prophesize(EntityTypeInterface::class);
    $no_view_builder->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);
    $no_view_builder->hasHandlerClass('form', 'layout_builder')->willReturn(TRUE);
    $no_view_builder->hasViewBuilderClass()->willReturn(FALSE);
    $entity_types['no_view_builder'] = $no_view_builder->reveal();

    $no_field_ui_route = $this->prophesize(EntityTypeInterface::class);
    $no_field_ui_route->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);
    $no_field_ui_route->hasHandlerClass('form', 'layout_builder')->willReturn(TRUE);
    $no_field_ui_route->hasViewBuilderClass()->willReturn(TRUE);
    $no_field_ui_route->get('field_ui_base_route')->willReturn(NULL);
    $entity_types['no_field_ui_route'] = $no_field_ui_route->reveal();

    $unknown_field_ui_route = $this->prophesize(EntityTypeInterface::class);
    $unknown_field_ui_route->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);
    $unknown_field_ui_route->hasHandlerClass('form', 'layout_builder')->willReturn(TRUE);
    $unknown_field_ui_route->hasViewBuilderClass()->willReturn(TRUE);
    $unknown_field_ui_route->get('field_ui_base_route')->willReturn('unknown');
    $entity_types['unknown_field_ui_route'] = $unknown_field_ui_route->reveal();

    $with_bundle_key = $this->prophesize(EntityTypeInterface::class);
    $with_bundle_key->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);
    $with_bundle_key->hasHandlerClass('form', 'layout_builder')->willReturn(TRUE);
    $with_bundle_key->hasViewBuilderClass()->willReturn(TRUE);
    $with_bundle_key->get('field_ui_base_route')->willReturn('known');
    $with_bundle_key->hasKey('bundle')->willReturn(TRUE);
    $with_bundle_key->getBundleEntityType()->willReturn('my_bundle_type');
    $entity_types['with_bundle_key'] = $with_bundle_key->reveal();

    $with_bundle_parameter = $this->prophesize(EntityTypeInterface::class);
    $with_bundle_parameter->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);
    $with_bundle_parameter->hasHandlerClass('form', 'layout_builder')->willReturn(TRUE);
    $with_bundle_parameter->hasViewBuilderClass()->willReturn(TRUE);
    $with_bundle_parameter->get('field_ui_base_route')->willReturn('with_bundle');
    $entity_types['with_bundle_parameter'] = $with_bundle_parameter->reveal();
    $this->entityTypeManager->getDefinitions()->willReturn($entity_types);

    $expected = [
      'known' => new Route('/admin/entity/whatever', [], [], ['_admin_route' => TRUE]),
      'with_bundle' => new Route('/admin/entity/{bundle}'),
      'layout_builder.defaults.with_bundle_key.view' => new Route(
        '/admin/entity/whatever/display/{view_mode_name}/layout',
        [
          'entity_type_id' => 'with_bundle_key',
          'bundle_key' => 'my_bundle_type',
          'bundle' => '',
          'section_storage_type' => 'defaults',
          'section_storage' => '',
          '_entity_form' => 'entity_view_display.layout_builder',
          '_title_callback' => '\Drupal\layout_builder\Controller\LayoutBuilderController::title',
        ],
        [
          '_field_ui_view_mode_access' => 'administer with_bundle_key display',
          '_layout_builder_access' => 'view',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
          ],
          '_layout_builder' => TRUE,
          '_admin_route' => FALSE,
          '_field_ui' => TRUE,
        ]
      ),
      'layout_builder.defaults.with_bundle_key.discard_changes' => new Route(
        '/admin/entity/whatever/display/{view_mode_name}/layout/discard-changes',
        [
          'entity_type_id' => 'with_bundle_key',
          'bundle_key' => 'my_bundle_type',
          'section_storage_type' => 'defaults',
          'section_storage' => '',
          '_form' => '\Drupal\layout_builder\Form\DiscardLayoutChangesForm',
        ],
        [
          '_field_ui_view_mode_access' => 'administer with_bundle_key display',
          '_layout_builder_access' => 'view',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
          ],
          '_layout_builder' => TRUE,
          '_admin_route' => FALSE,
        ]
      ),
      'layout_builder.defaults.with_bundle_key.disable' => new Route(
        '/admin/entity/whatever/display/{view_mode_name}/layout/disable',
        [
          'entity_type_id' => 'with_bundle_key',
          'bundle_key' => 'my_bundle_type',
          'section_storage_type' => 'defaults',
          'section_storage' => '',
          '_form' => '\Drupal\layout_builder\Form\LayoutBuilderDisableForm',
        ],
        [
          '_field_ui_view_mode_access' => 'administer with_bundle_key display',
          '_layout_builder_access' => 'view',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
          ],
        ]
      ),
      'layout_builder.defaults.with_bundle_parameter.view' => new Route(
        '/admin/entity/{bundle}/display/{view_mode_name}/layout',
        [
          'entity_type_id' => 'with_bundle_parameter',
          'section_storage_type' => 'defaults',
          'section_storage' => '',
          '_entity_form' => 'entity_view_display.layout_builder',
          '_title_callback' => '\Drupal\layout_builder\Controller\LayoutBuilderController::title',
        ],
        [
          '_field_ui_view_mode_access' => 'administer with_bundle_parameter display',
          '_layout_builder_access' => 'view',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
          ],
          '_layout_builder' => TRUE,
          '_admin_route' => FALSE,
        ]
      ),
      'layout_builder.defaults.with_bundle_parameter.discard_changes' => new Route(
        '/admin/entity/{bundle}/display/{view_mode_name}/layout/discard-changes',
        [
          'entity_type_id' => 'with_bundle_parameter',
          'section_storage_type' => 'defaults',
          'section_storage' => '',
          '_form' => '\Drupal\layout_builder\Form\DiscardLayoutChangesForm',
        ],
        [
          '_field_ui_view_mode_access' => 'administer with_bundle_parameter display',
          '_layout_builder_access' => 'view',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
          ],
          '_layout_builder' => TRUE,
          '_admin_route' => FALSE,
        ]
      ),
      'layout_builder.defaults.with_bundle_parameter.disable' => new Route(
        '/admin/entity/{bundle}/display/{view_mode_name}/layout/disable',
        [
          'entity_type_id' => 'with_bundle_parameter',
          'section_storage_type' => 'defaults',
          'section_storage' => '',
          '_form' => '\Drupal\layout_builder\Form\LayoutBuilderDisableForm',
        ],
        [
          '_field_ui_view_mode_access' => 'administer with_bundle_parameter display',
          '_layout_builder_access' => 'view',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
          ],
        ]
      ),
    ];

    $collection = new RouteCollection();
    $collection->add('known', new Route('/admin/entity/whatever', [], [], ['_admin_route' => TRUE]));
    $collection->add('with_bundle', new Route('/admin/entity/{bundle}'));

    $this->plugin->buildRoutes($collection);
    $this->assertEquals($expected, $collection->all());
    $this->assertSame(array_keys($expected), array_keys($collection->all()));
  }

}
