<?php

namespace Drupal\Tests\field_layout\Unit;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\field_layout\Display\EntityDisplayWithLayoutInterface;
use Drupal\field_layout\FieldLayoutBuilder;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\field_layout\FieldLayoutBuilder
 * @group field_layout
 */
class FieldLayoutBuilderTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\Layout\LayoutPluginManager|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $layoutPluginManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\field_layout\FieldLayoutBuilder
   */
  protected $fieldLayoutBuilder;

  /**
   * @var \Drupal\Core\Layout\LayoutInterface
   */
  protected $layoutPlugin;

  /**
   * @var \Drupal\Core\Layout\LayoutDefinition
   */
  protected $pluginDefinition;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->pluginDefinition = new LayoutDefinition([
      'library' => 'field_layout/drupal.layout.twocol',
      'theme_hook' => 'layout__twocol',
      'regions' => [
        'left' => [
          'label' => 'Left',
        ],
        'right' => [
          'label' => 'Right',
        ],
      ],
    ]);
    $this->layoutPlugin = new LayoutDefault([], 'two_column', $this->pluginDefinition);

    $this->layoutPluginManager = $this->prophesize(LayoutPluginManagerInterface::class);
    $this->layoutPluginManager->getDefinition('unknown', FALSE)->willReturn(NULL);
    $this->layoutPluginManager->getDefinition('two_column', FALSE)->willReturn($this->pluginDefinition);

    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);

    $this->fieldLayoutBuilder = new FieldLayoutBuilder($this->layoutPluginManager->reveal(), $this->entityFieldManager->reveal());
  }

  /**
   * @covers ::buildView
   * @covers ::getFields
   */
  public function testBuildView() {
    $definitions = [];
    $non_configurable_field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $non_configurable_field_definition->isDisplayConfigurable('view')->willReturn(FALSE);
    $definitions['non_configurable_field'] = $non_configurable_field_definition->reveal();
    $definitions['non_configurable_field_with_extra_field'] = $non_configurable_field_definition->reveal();
    $this->entityFieldManager->getFieldDefinitions('the_entity_type_id', 'the_entity_type_bundle')->willReturn($definitions);
    $extra_fields = [];
    $extra_fields['non_configurable_field_with_extra_field'] = [
      'label' => 'This non-configurable field is also defined in hook_entity_extra_field_info()',
    ];
    $this->entityFieldManager->getExtraFields('the_entity_type_id', 'the_entity_type_bundle')->willReturn($extra_fields);

    $build = [
      'test1' => [
        '#markup' => 'Test1',
      ],
      'non_configurable_field' => [
        '#markup' => 'Non-configurable',
      ],
      'non_configurable_field_with_extra_field' => [
        '#markup' => 'Non-configurable with extra field',
      ],
    ];

    $display = $this->prophesize(EntityDisplayWithLayoutInterface::class);
    $display->getTargetEntityTypeId()->willReturn('the_entity_type_id');
    $display->getTargetBundle()->willReturn('the_entity_type_bundle');
    $display->getLayout()->willReturn($this->layoutPlugin);
    $display->getLayoutId()->willReturn('two_column');
    $display->getLayoutSettings()->willReturn([]);
    $display->getComponents()->willReturn([
      'test1' => [
        'region' => 'right',
      ],
      'non_configurable_field' => [
        'region' => 'left',
      ],
      'non_configurable_field_with_extra_field' => [
        'region' => 'left',
      ],
    ]);

    $expected = [
      'non_configurable_field' => [
        '#markup' => 'Non-configurable',
      ],
      '_field_layout' => [
        'left' => [
          'non_configurable_field_with_extra_field' => [
            '#markup' => 'Non-configurable with extra field',
          ],
        ],
        'right' => [
          'test1' => [
            '#markup' => 'Test1',
          ],
        ],
        '#settings' => [],
        '#layout' => $this->pluginDefinition,
        '#theme' => 'layout__twocol',
        '#attached' => [
          'library' => [
            'field_layout/drupal.layout.twocol',
          ],
        ],
      ],
    ];
    $this->fieldLayoutBuilder->buildView($build, $display->reveal());
    $this->assertEquals($expected, $build);
    $this->assertSame($expected, $build);
  }

  /**
   * @covers ::buildForm
   * @covers ::getFields
   */
  public function testBuildForm() {
    $definitions = [];
    $non_configurable_field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $non_configurable_field_definition->isDisplayConfigurable('form')->willReturn(FALSE);
    $definitions['non_configurable_field'] = $non_configurable_field_definition->reveal();
    $this->entityFieldManager->getFieldDefinitions('the_entity_type_id', 'the_entity_type_bundle')->willReturn($definitions);
    $this->entityFieldManager->getExtraFields('the_entity_type_id', 'the_entity_type_bundle')->willReturn([]);

    $build = [
      'test1' => [
        '#markup' => 'Test1',
      ],
      'test2' => [
        '#markup' => 'Test2',
        '#group' => 'existing_group',
      ],
      'field_layout' => [
        '#markup' => 'Field created through the UI happens to be named "Layout"',
      ],
      'non_configurable_field' => [
        '#markup' => 'Non-configurable',
      ],
    ];

    $display = $this->prophesize(EntityDisplayWithLayoutInterface::class);
    $display->getTargetEntityTypeId()->willReturn('the_entity_type_id');
    $display->getTargetBundle()->willReturn('the_entity_type_bundle');
    $display->getLayout()->willReturn($this->layoutPlugin);
    $display->getLayoutId()->willReturn('two_column');
    $display->getLayoutSettings()->willReturn([]);
    $display->getComponents()->willReturn([
      'test1' => [
        'region' => 'right',
      ],
      'test2' => [
        'region' => 'left',
      ],
      'field_layout' => [
        'region' => 'right',
      ],
      'non_configurable_field' => [
        'region' => 'left',
      ],
    ]);

    $expected = [
      'test1' => [
        '#markup' => 'Test1',
        '#group' => 'right',
      ],
      'test2' => [
        '#markup' => 'Test2',
        '#group' => 'existing_group',
      ],
      'field_layout' => [
        '#markup' => 'Field created through the UI happens to be named "Layout"',
        '#group' => 'right',
      ],
      'non_configurable_field' => [
        '#markup' => 'Non-configurable',
      ],
      '_field_layout' => [
        'left' => [
          '#process' => ['\Drupal\Core\Render\Element\RenderElement::processGroup'],
          '#pre_render' => ['\Drupal\Core\Render\Element\RenderElement::preRenderGroup'],
        ],
        'right' => [
          '#process' => ['\Drupal\Core\Render\Element\RenderElement::processGroup'],
          '#pre_render' => ['\Drupal\Core\Render\Element\RenderElement::preRenderGroup'],
        ],
        '#settings' => [],
        '#layout' => $this->pluginDefinition,
        '#theme' => 'layout__twocol',
        '#attached' => [
          'library' => [
            'field_layout/drupal.layout.twocol',
          ],
        ],
      ],
    ];
    $this->fieldLayoutBuilder->buildForm($build, $display->reveal());
    $this->assertEquals($expected, $build);
    $this->assertSame($expected, $build);
  }

  /**
   * @covers ::buildForm
   */
  public function testBuildFormEmpty() {
    $definitions = [];
    $non_configurable_field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $non_configurable_field_definition->isDisplayConfigurable('form')->willReturn(FALSE);
    $definitions['non_configurable_field'] = $non_configurable_field_definition->reveal();
    $this->entityFieldManager->getFieldDefinitions('the_entity_type_id', 'the_entity_type_bundle')->willReturn($definitions);
    $this->entityFieldManager->getExtraFields('the_entity_type_id', 'the_entity_type_bundle')->willReturn([]);

    $build = [
      'non_configurable_field' => [
        '#markup' => 'Non-configurable',
      ],
    ];

    $display = $this->prophesize(EntityDisplayWithLayoutInterface::class);
    $display->getTargetEntityTypeId()->willReturn('the_entity_type_id');
    $display->getTargetBundle()->willReturn('the_entity_type_bundle');
    $display->getLayout()->willReturn($this->layoutPlugin);
    $display->getLayoutId()->willReturn('two_column');
    $display->getLayoutSettings()->willReturn([]);
    $display->getComponents()->willReturn([
      'test1' => [
        'region' => 'right',
      ],
      'non_configurable_field' => [
        'region' => 'left',
      ],
    ]);

    $expected = [
      'non_configurable_field' => [
        '#markup' => 'Non-configurable',
      ],
    ];
    $this->fieldLayoutBuilder->buildForm($build, $display->reveal());
    $this->assertSame($expected, $build);
  }

  /**
   * @covers ::buildForm
   */
  public function testBuildFormNoLayout() {
    $this->entityFieldManager->getFieldDefinitions(Argument::any(), Argument::any())->shouldNotBeCalled();

    $build = [
      'test1' => [
        '#markup' => 'Test1',
      ],
    ];

    $display = $this->prophesize(EntityDisplayWithLayoutInterface::class);
    $display->getLayoutId()->willReturn('unknown');
    $display->getLayoutSettings()->willReturn([]);
    $display->getComponents()->shouldNotBeCalled();

    $expected = [
      'test1' => [
        '#markup' => 'Test1',
      ],
    ];
    $this->fieldLayoutBuilder->buildForm($build, $display->reveal());
    $this->assertSame($expected, $build);
  }

}
