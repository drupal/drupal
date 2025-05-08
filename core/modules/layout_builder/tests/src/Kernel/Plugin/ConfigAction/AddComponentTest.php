<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel\Plugin\ConfigAction;

use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_test\EntityTestHelper;
use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionListInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;

/**
 * @coversDefaultClass \Drupal\layout_builder\Plugin\ConfigAction\AddComponent
 *
 * @group layout_builder
 */
class AddComponentTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_discovery',
    'layout_builder',
    'layout_builder_defaults_test',
    'entity_test',
    'field',
    'system',
    'user',
  ];

  /**
   * The plugin.
   */
  private readonly DefaultsSectionStorage $plugin;

  /**
   * The config action manager.
   */
  private readonly ConfigActionManager $configActionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    EntityTestHelper::createBundle('bundle_with_extra_fields');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installConfig(['layout_builder_defaults_test']);

    $this->plugin = $this->container->get(SectionStorageManagerInterface::class)->createInstance('defaults');
    $this->configActionManager = $this->container->get('plugin.manager.config_action');

    // Add some extra empty sections.
    $view_display = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('entity_view_display')
      ->load('entity_test.bundle_with_extra_fields.default');
    assert($view_display instanceof SectionListInterface);
    $view_display->insertSection(1, new Section('layout_onecol'));
    $view_display->insertSection(2, new Section('layout_threecol_25_50_25'));
    $view_display->save();
  }

  /**
   * Tests adding a component to a view display using a config action.
   *
   * @dataProvider provider
   */
  public function testAddComponent(array $config_action_value, string $expected_region, int $added_component_expected_weight, int $existing_component_expected_weight, ?array $expected_error = NULL): void {
    if ($expected_error !== NULL) {
      $this->expectException($expected_error[0]);
      $this->expectExceptionMessage($expected_error[1]);
    }
    $this->configActionManager->applyAction(
      'addComponentToLayout',
      'core.entity_view_display.entity_test.bundle_with_extra_fields.default',
      $config_action_value,
    );

    $view_display = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('entity_view_display')
      ->load('entity_test.bundle_with_extra_fields.default');
    $this->plugin->setContextValue('display', $view_display);
    $components = $this->plugin->getSection(0)->getComponents();
    $uuid = end($components)->getUuid();

    // If we pass the same existing UUID, we replace it.
    $is_replacing = $added_component_expected_weight === $existing_component_expected_weight;
    $expected_existing_plugin = $is_replacing ? 'my_plugin_id' : 'extra_field_block:entity_test:bundle_with_extra_fields:display_extra_field';
    $this->assertCount($is_replacing ? 1 : 2, $components);
    $this->assertSame($expected_existing_plugin, $components['1445597a-c674-431d-ac0a-277d99347a7f']->getPluginId());
    $this->assertSame('my_plugin_id', $components[$uuid]->getPluginId());
    $this->assertSame($expected_region, $components[$uuid]->getRegion());
    $this->assertSame($added_component_expected_weight, $components[$uuid]->getWeight());
    // Assert weight of the existing component in the layout_twocol_section
    // first region.
    $this->assertSame($existing_component_expected_weight, $components['1445597a-c674-431d-ac0a-277d99347a7f']->getWeight());
    // Assert the component configuration (defined with its config schema), and the
    // additional configuration (ignored in config schema)
    $this->assertSame($config_action_value['component']['configuration'], $components[$uuid]->get('configuration'));
    $this->assertSame($config_action_value['component']['additional'] ?? [], $components[$uuid]->get('additional'));
  }

  /**
   * Data provider for testAddComponent.
   */
  public static function provider(): \Generator {
    yield 'add component at first position of a non-empty region' => [
      [
        'section' => 0,
        'position' => 0,
        'component' => [
          'region' => [
            'layout_test_plugin' => 'content',
            'layout_twocol_section' => 'first',
          ],
          'default_region' => 'content',
          'configuration' => [
            'id' => 'my_plugin_id',
          ],
        ],
      ],
      'first',
      1,
      2,
    ];
    yield 'edit existing component by giving the same uuid' => [
      [
        'section' => 0,
        'position' => 0,
        'component' => [
          'uuid' => '1445597a-c674-431d-ac0a-277d99347a7f',
          'region' => [
            'layout_test_plugin' => 'content',
            'layout_twocol_section' => 'first',
          ],
          'default_region' => 'content',
          'configuration' => [
            'id' => 'my_plugin_id',
          ],
        ],
      ],
      'first',
      1,
      1,
    ];
    yield 'add component at second position of a non-empty region' => [
      [
        'section' => 0,
        'position' => 1,
        'component' => [
          'region' => [
            'layout_test_plugin' => 'content',
            'layout_twocol_section' => 'first',
          ],
          'default_region' => 'content',
          'configuration' => [
            'id' => 'my_plugin_id',
            'some_configuration' => 'my_configuration_value',
          ],
          'additional' => [
            'some_additional_value' => 'my_custom_value',
          ],
        ],
      ],
      'first',
      2,
      1,
    ];
    yield 'add component at a position larger than the region size on an empty region' => [
      [
        'section' => 0,
        'position' => 4,
        'component' => [
          'region' => [
            'layout_test_plugin' => 'content',
            'layout_twocol_section' => 'second',
          ],
          'default_region' => 'content',
          'configuration' => [
            'id' => 'my_plugin_id',
            'some_configuration' => 'my_configuration_value',
          ],
          'additional' => [
            'some_additional_value' => 'my_custom_value',
          ],
        ],
      ],
      'second',
      // As there is no other block in that section's region, weight is 0 no matter
      // of the 4th position we asked for.
      0,
      1,
    ];
    yield 'add component at a region not defined in the mapping' => [
      [
        'section' => 0,
        'position' => 4,
        'component' => [
          'region' => [
            'layout_test_plugin' => 'content',
          ],
          'default_region' => 'second',
          'configuration' => [
            'id' => 'my_plugin_id',
          ],
        ],
      ],
      // Assigned to the default region, as no mapping matched.
      'second',
      0,
      1,
    ];
    yield 'add component at a region defined in the mapping while no default region exist' => [
      [
        'section' => 0,
        'position' => 4,
        'component' => [
          'region' => [
            'layout_twocol_section' => 'second',
          ],
          'configuration' => [
            'id' => 'my_plugin_id',
          ],
        ],
      ],
      // Assigned to the matching region, even if no default_region.
      'second',
      0,
      1,
    ];
    yield 'add component with only default_region and no region mapping' => [
      [
        'section' => 0,
        'position' => 4,
        'component' => [
          'default_region' => 'second',
          'configuration' => [
            'id' => 'my_plugin_id',
          ],
        ],
      ],
      // Assigned to the default region, even with no mapping.
      'second',
      0,
      1,
    ];
    yield 'exception when cannot determine a region with mapping and default' => [
      [
        'section' => 0,
        'position' => 4,
        'component' => [
          'region' => [
            'layout_test_plugin' => 'content',
          ],
          'configuration' => [
            'id' => 'my_plugin_id',
          ],
        ],
      ],
      'second',
      0,
      1,
      // No default_region, no matching region, so we error.
      [
        ConfigActionException::class,
        'Cannot determine which region of the section to place this component into, because no default region was provided.',
      ],
      yield 'exception when no region given' => [
        [
          'section' => 0,
          'position' => 4,
          'component' => [
            'configuration' => [
              'id' => 'my_plugin_id',
            ],
          ],
        ],
        'second',
        0,
        1,
        // No default_region, no matching region, so we error.
        [
          ConfigActionException::class,
          'Cannot determine which region of the section to place this component into, because no region was provided.',
        ],
      ],
      yield 'exception when no configuration given' => [
        [
          'section' => 0,
          'position' => 4,
          'component' => [
            'region' => [
              'layout_test_plugin' => 'content',
            ],
            'default_region' => 'content',
          ],
        ],
        'second',
        0,
        1,
        // No component configuration.
        [
          ConfigActionException::class,
          'Cannot determine the component configuration, or misses a plugin ID.',
        ],
      ],
      yield 'exception when no id in configuration is given' => [
        [
          'section' => 0,
          'position' => 4,
          'component' => [
            'region' => [
              'layout_test_plugin' => 'content',
            ],
            'default_region' => 'content',
            'configuration' => [
              'no_id' => 'my_plugin_id',
            ],
          ],
        ],
        'second',
        0,
        1,
        // No component configuration id.
        [
          ConfigActionException::class,
          'Cannot determine the component configuration, or misses a plugin ID.',
        ],
      ],

    ];
  }

  /**
   * Tests that adding a component to another section works as expected.
   */
  public function testAddComponentToEmptyRegionThatIsNotFirst(): void {
    $this->configActionManager->applyAction(
      'addComponentToLayout',
      'core.entity_view_display.entity_test.bundle_with_extra_fields.default',
      [
        'section' => 2,
        'position' => 4,
        'component' => [
          'region' => [
            'layout_twocol_section' => 'second',
            'layout_threecol_25_50_25' => 'bottom',
          ],
          'default_region' => 'content',
          'configuration' => [
            'id' => 'my_plugin_id',
          ],
        ],
      ]);
    $view_display = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('entity_view_display')
      ->load('entity_test.bundle_with_extra_fields.default');
    $this->plugin->setContextValue('display', $view_display);

    $this->assertCount(1, $this->plugin->getSection(0)->getComponents());
    $this->assertCount(0, $this->plugin->getSection(1)->getComponents());
    $this->assertCount(1, $this->plugin->getSection(2)->getComponents());

    $components = $this->plugin->getSection(2)->getComponents();
    $uuid = end($components)->getUuid();

    $this->assertSame('bottom', $components[$uuid]->getRegion());
    $this->assertSame(0, $components[$uuid]->getWeight());
    $this->assertSame(['id' => 'my_plugin_id'], $components[$uuid]->get('configuration'));
  }

  /**
   * Tests that applying the config action to a missing entity fails.
   */
  public function testActionFailsIfEntityNotFound(): void {
    $this->expectException(ConfigActionException::class);
    $this->expectExceptionMessage('No entity found for applying the addComponentToLayout action.');
    $this->configActionManager->applyAction(
      'addComponentToLayout',
      'core.entity_view_display.entity_test.bundle_with_extra_fields.missing_view_mode',
      [
        'section' => 0,
        'position' => 4,
        'component' => [
          'default_region' => 'content',
          'configuration' => [
            'id' => 'my_plugin_id',
          ],
        ],
      ]);
  }

}
