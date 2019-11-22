<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorage\SectionStorageDefinition;

/**
 * @coversDefaultClass \Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage
 *
 * @group layout_builder
 */
class DefaultsSectionStorageTest extends KernelTestBase {

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
   *
   * @var \Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    entity_test_create_bundle('bundle_with_extra_fields');
    $this->installSchema('system', ['key_value_expire']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installConfig(['layout_builder_defaults_test']);

    $definition = (new SectionStorageDefinition())
      ->addContextDefinition('display', EntityContextDefinition::fromEntityTypeId('entity_view_display'))
      ->addContextDefinition('view_mode', new ContextDefinition('string'));
    $this->plugin = DefaultsSectionStorage::create($this->container, [], 'defaults', $definition);
  }

  /**
   * Tests installing defaults via config install.
   */
  public function testConfigInstall() {
    /** @var \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface $display */
    $display = LayoutBuilderEntityViewDisplay::load('entity_test.bundle_with_extra_fields.default');
    $section = $display->getSection(0);
    $this->assertInstanceOf(Section::class, $section);
    $this->assertEquals('layout_twocol_section', $section->getLayoutId());
    $this->assertEquals([
      'column_widths' => '50-50',
      'label' => '',
    ], $section->getLayoutSettings());
  }

  /**
   * @covers ::access
   * @dataProvider providerTestAccess
   *
   * @param bool $expected
   *   The expected outcome of ::access().
   * @param string $operation
   *   The operation to pass to ::access().
   * @param bool $is_enabled
   *   Whether Layout Builder is enabled for this display.
   * @param array $section_data
   *   Data to store as the sections value for Layout Builder.
   */
  public function testAccess($expected, $operation, $is_enabled, array $section_data) {
    $display = LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    if ($is_enabled) {
      $display->enableLayoutBuilder();
    }
    $display
      ->setThirdPartySetting('layout_builder', 'sections', $section_data)
      ->save();

    $this->plugin->setContext('display', EntityContext::fromEntity($display));
    $result = $this->plugin->access($operation);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for ::testAccess().
   */
  public function providerTestAccess() {
    $section_data = [
      new Section(
        'layout_onecol',
        [],
        [
          'first-uuid' => new SectionComponent('first-uuid', 'content', ['id' => 'foo'], ['harold' => 'maude']),
        ],
        ['layout_builder_defaults_test' => ['which_party' => 'third']]
      ),
    ];

    // Data provider values are:
    // - the expected outcome of the call to ::access()
    // - the operation
    // - whether Layout Builder has been enabled for this display
    // - whether this display has any section data.
    $data = [];
    $data['view, disabled, no data'] = [FALSE, 'view', FALSE, []];
    $data['view, enabled, no data'] = [TRUE, 'view', TRUE, []];
    $data['view, disabled, data'] = [FALSE, 'view', FALSE, $section_data];
    $data['view, enabled, data'] = [TRUE, 'view', TRUE, $section_data];
    return $data;
  }

  /**
   * @covers ::getContexts
   */
  public function testGetContexts() {
    $display = LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $display->save();

    $context = EntityContext::fromEntity($display);
    $this->plugin->setContext('display', $context);

    $result = $this->plugin->getContexts();
    $this->assertSame(['view_mode', 'display'], array_keys($result));
    $this->assertSame($context, $result['display']);
  }

  /**
   * @covers ::getContextsDuringPreview
   */
  public function testGetContextsDuringPreview() {
    $display = LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $display->save();

    $context = EntityContext::fromEntity($display);
    $this->plugin->setContext('display', $context);

    $result = $this->plugin->getContextsDuringPreview();
    $this->assertSame(['view_mode', 'display', 'layout_builder.entity'], array_keys($result));

    $this->assertSame($context, $result['display']);

    $this->assertInstanceOf(EntityContext::class, $result['layout_builder.entity']);
    $result_value = $result['layout_builder.entity']->getContextValue();
    $this->assertInstanceOf(EntityTest::class, $result_value);
    $this->assertSame('entity_test', $result_value->bundle());

    $this->assertInstanceOf(Context::class, $result['view_mode']);
    $result_value = $result['view_mode']->getContextValue();
    $this->assertSame('default', $result_value);
  }

  /**
   * @covers ::getTempstoreKey
   */
  public function testGetTempstoreKey() {
    $display = LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $display->save();

    $context = EntityContext::fromEntity($display);
    $this->plugin->setContext('display', $context);

    $result = $this->plugin->getTempstoreKey();
    $this->assertSame('entity_test.entity_test.default', $result);
  }

  /**
   * Tests loading given a display.
   */
  public function testLoadFromDisplay() {
    $display = LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $display->save();
    $contexts = [
      'display' => EntityContext::fromEntity($display),
    ];

    $section_storage_manager = $this->container->get('plugin.manager.layout_builder.section_storage');
    $section_storage = $section_storage_manager->load('defaults', $contexts);
    $this->assertInstanceOf(DefaultsSectionStorage::class, $section_storage);
  }

}
