<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\layout_builder\Routing\LayoutBuilderRoutes;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\layout_builder\Routing\LayoutBuilderRoutes
 *
 * @group layout_builder
 */
class LayoutBuilderRoutesTest extends UnitTestCase {

  /**
   * The Layout Builder route builder.
   *
   * @var \Drupal\layout_builder\Routing\LayoutBuilderRoutes
   */
  protected $routeBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $entity_types = [];
    $entity_types['no_link_template'] = new EntityType(['id' => 'no_link_template']);
    $entity_types['with_link_template'] = new EntityType([
      'id' => 'with_link_template',
      'links' => ['layout-builder' => '/entity/{entity}/layout'],
      'entity_keys' => ['id' => 'id'],
      'field_ui_base_route' => 'unknown',
    ]);
    $entity_types['with_integer_id'] = new EntityType([
      'id' => 'with_integer_id',
      'links' => ['layout-builder' => '/entity/{entity}/layout'],
      'entity_keys' => ['id' => 'id'],
    ]);
    $entity_types['with_field_ui_route'] = new EntityType([
      'id' => 'with_field_ui_route',
      'links' => ['layout-builder' => '/entity/{entity}/layout'],
      'entity_keys' => ['id' => 'id'],
      'field_ui_base_route' => 'known',
    ]);
    $entity_types['with_bundle_key'] = new EntityType([
      'id' => 'with_field_ui_route',
      'links' => ['layout-builder' => '/entity/{entity}/layout'],
      'entity_keys' => ['id' => 'id', 'bundle' => 'bundle'],
      'bundle_entity_type' => 'my_bundle_type',
      'field_ui_base_route' => 'known',
    ]);
    $entity_types['with_bundle_parameter'] = new EntityType([
      'id' => 'with_bundle_parameter',
      'links' => ['layout-builder' => '/entity/{entity}/layout'],
      'entity_keys' => ['id' => 'id'],
      'field_ui_base_route' => 'with_bundle',
    ]);
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getDefinitions()->willReturn($entity_types);

    $string_id = $this->prophesize(FieldStorageDefinitionInterface::class);
    $string_id->getType()->willReturn('string');
    $integer_id = $this->prophesize(FieldStorageDefinitionInterface::class);
    $integer_id->getType()->willReturn('integer');
    $entity_field_manager = $this->prophesize(EntityFieldManagerInterface::class);
    $entity_field_manager->getFieldStorageDefinitions('no_link_template')->shouldNotBeCalled();
    $entity_field_manager->getFieldStorageDefinitions('with_link_template')->willReturn(['id' => $string_id->reveal()]);
    $entity_field_manager->getFieldStorageDefinitions('with_integer_id')->willReturn(['id' => $integer_id->reveal()]);
    $entity_field_manager->getFieldStorageDefinitions('with_field_ui_route')->willReturn(['id' => $integer_id->reveal()]);
    $entity_field_manager->getFieldStorageDefinitions('with_bundle_parameter')->willReturn(['id' => $integer_id->reveal()]);

    $this->routeBuilder = new LayoutBuilderRoutes($entity_type_manager->reveal(), $entity_field_manager->reveal());
  }

  /**
   * @covers ::getRoutes
   * @covers ::buildRoute
   * @covers ::hasIntegerId
   * @covers ::getEntityTypes
   */
  public function testGetRoutes() {
    $expected = [
      'entity.with_link_template.layout_builder' => new Route(
        '/entity/{entity}/layout',
        [
          'entity_type_id' => 'with_link_template',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          'is_rebuilding' => FALSE,
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::layout',
          '_title_callback' => '\Drupal\layout_builder\Controller\LayoutBuilderController::title',
        ],
        [
          '_has_layout_section' => 'true',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_link_template' => ['type' => 'entity:with_link_template'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'entity.with_link_template.layout_builder_save' => new Route(
        '/entity/{entity}/layout/save',
        [
          'entity_type_id' => 'with_link_template',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::saveLayout',
        ],
        [
          '_has_layout_section' => 'true',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_link_template' => ['type' => 'entity:with_link_template'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'entity.with_link_template.layout_builder_cancel' => new Route(
        '/entity/{entity}/layout/cancel',
        [
          'entity_type_id' => 'with_link_template',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::cancelLayout',
        ],
        [
          '_has_layout_section' => 'true',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_link_template' => ['type' => 'entity:with_link_template'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'entity.with_integer_id.layout_builder' => new Route(
        '/entity/{entity}/layout',
        [
          'entity_type_id' => 'with_integer_id',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          'is_rebuilding' => FALSE,
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::layout',
          '_title_callback' => '\Drupal\layout_builder\Controller\LayoutBuilderController::title',
        ],
        [
          '_has_layout_section' => 'true',
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
      'entity.with_integer_id.layout_builder_save' => new Route(
        '/entity/{entity}/layout/save',
        [
          'entity_type_id' => 'with_integer_id',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::saveLayout',
        ],
        [
          '_has_layout_section' => 'true',
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
      'entity.with_integer_id.layout_builder_cancel' => new Route(
        '/entity/{entity}/layout/cancel',
        [
          'entity_type_id' => 'with_integer_id',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::cancelLayout',
        ],
        [
          '_has_layout_section' => 'true',
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
      'entity.with_field_ui_route.layout_builder' => new Route(
        '/entity/{entity}/layout',
        [
          'entity_type_id' => 'with_field_ui_route',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          'is_rebuilding' => FALSE,
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::layout',
          '_title_callback' => '\Drupal\layout_builder\Controller\LayoutBuilderController::title',
        ],
        [
          '_has_layout_section' => 'true',
          'with_field_ui_route' => '\d+',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_field_ui_route' => ['type' => 'entity:with_field_ui_route'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'entity.with_field_ui_route.layout_builder_save' => new Route(
        '/entity/{entity}/layout/save',
        [
          'entity_type_id' => 'with_field_ui_route',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::saveLayout',
        ],
        [
          '_has_layout_section' => 'true',
          'with_field_ui_route' => '\d+',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_field_ui_route' => ['type' => 'entity:with_field_ui_route'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'entity.with_field_ui_route.layout_builder_cancel' => new Route(
        '/entity/{entity}/layout/cancel',
        [
          'entity_type_id' => 'with_field_ui_route',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::cancelLayout',
        ],
        [
          '_has_layout_section' => 'true',
          'with_field_ui_route' => '\d+',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_field_ui_route' => ['type' => 'entity:with_field_ui_route'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'entity.with_bundle_key.layout_builder' => new Route(
        '/entity/{entity}/layout',
        [
          'entity_type_id' => 'with_bundle_key',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          'is_rebuilding' => FALSE,
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::layout',
          '_title_callback' => '\Drupal\layout_builder\Controller\LayoutBuilderController::title',
        ],
        [
          '_has_layout_section' => 'true',
          'with_bundle_key' => '\d+',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_bundle_key' => ['type' => 'entity:with_bundle_key'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'entity.with_bundle_key.layout_builder_save' => new Route(
        '/entity/{entity}/layout/save',
        [
          'entity_type_id' => 'with_bundle_key',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::saveLayout',
        ],
        [
          '_has_layout_section' => 'true',
          'with_bundle_key' => '\d+',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_bundle_key' => ['type' => 'entity:with_bundle_key'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'entity.with_bundle_key.layout_builder_cancel' => new Route(
        '/entity/{entity}/layout/cancel',
        [
          'entity_type_id' => 'with_bundle_key',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::cancelLayout',
        ],
        [
          '_has_layout_section' => 'true',
          'with_bundle_key' => '\d+',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_bundle_key' => ['type' => 'entity:with_bundle_key'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'entity.with_bundle_parameter.layout_builder' => new Route(
        '/entity/{entity}/layout',
        [
          'entity_type_id' => 'with_bundle_parameter',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          'is_rebuilding' => FALSE,
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::layout',
          '_title_callback' => '\Drupal\layout_builder\Controller\LayoutBuilderController::title',
        ],
        [
          '_has_layout_section' => 'true',
          'with_bundle_parameter' => '\d+',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_bundle_parameter' => ['type' => 'entity:with_bundle_parameter'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'entity.with_bundle_parameter.layout_builder_save' => new Route(
        '/entity/{entity}/layout/save',
        [
          'entity_type_id' => 'with_bundle_parameter',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::saveLayout',
        ],
        [
          '_has_layout_section' => 'true',
          'with_bundle_parameter' => '\d+',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_bundle_parameter' => ['type' => 'entity:with_bundle_parameter'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
      'entity.with_bundle_parameter.layout_builder_cancel' => new Route(
        '/entity/{entity}/layout/cancel',
        [
          'entity_type_id' => 'with_bundle_parameter',
          'section_storage_type' => 'overrides',
          'section_storage' => '',
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::cancelLayout',
        ],
        [
          '_has_layout_section' => 'true',
          'with_bundle_parameter' => '\d+',
        ],
        [
          'parameters' => [
            'section_storage' => ['layout_builder_tempstore' => TRUE],
            'with_bundle_parameter' => ['type' => 'entity:with_bundle_parameter'],
          ],
          '_layout_builder' => TRUE,
        ]
      ),
    ];

    $this->assertEquals($expected, $this->routeBuilder->getRoutes());
  }

}
