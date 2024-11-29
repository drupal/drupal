<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Theme\Icon;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Theme\Icon\Exception\IconPackConfigErrorException;
use Drupal\Core\Theme\Icon\IconDefinitionInterface;
use Drupal\Core\Theme\Icon\Plugin\IconPackManager;
use Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface;

/**
 * @coversDefaultClass \Drupal\Core\Theme\Icon\Plugin\IconPackManager
 *
 * Tests values are from test module icon_test. Any change of the definition
 * will impact the tests.
 *
 * @see core/modules/system/tests/modules/icon_test/icon_test.icons.yml
 *
 * @group icon
 */
class IconPackManagerKernelTest extends KernelTestBase {

  /**
   * Icon from icon_test module.
   */
  private const TEST_ICON_FULL_ID = 'test_minimal:foo';

  private const EXPECTED_TOTAL_TEST_ICONS = 30;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'icon_test',
  ];

  /**
   * The IconPackManager instance.
   *
   * @var \Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface
   */
  private IconPackManagerInterface $pluginManagerIconPack;

  /**
   * The App root instance.
   *
   * @var string
   */
  private string $appRoot;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $module_handler = $this->container->get('module_handler');
    $theme_handler = $this->container->get('theme_handler');
    $cache_backend = $this->container->get('cache.default');
    $icon_extractor_plugin_manager = $this->container->get('plugin.manager.icon_extractor');
    $icon_collector = $this->container->get('Drupal\Core\Theme\Icon\IconCollector');
    $this->appRoot = $this->container->getParameter('app.root');

    $this->pluginManagerIconPack = new IconPackManager(
      $module_handler,
      $theme_handler,
      $cache_backend,
      $icon_extractor_plugin_manager,
      $icon_collector,
      $this->appRoot,
    );
  }

  /**
   * Test the IconPackManager::_construct method.
   */
  public function testConstructor(): void {
    $this->assertInstanceOf(IconPackManager::class, $this->pluginManagerIconPack);
  }

  /**
   * Test the IconPackManager::getIcons method.
   */
  public function testGetIcons(): void {
    $icons = $this->pluginManagerIconPack->getIcons();
    $this->assertCount(self::EXPECTED_TOTAL_TEST_ICONS, $icons);
    foreach ($icons as $icon) {
      $this->assertArrayHasKey('source', $icon);
      $this->assertArrayHasKey('group', $icon);
    }

    $icons = $this->pluginManagerIconPack->getIcons(['test_minimal']);
    $this->assertCount(1, $icons);
    foreach ($icons as $icon) {
      $this->assertArrayHasKey('source', $icon);
      $this->assertArrayHasKey('group', $icon);
    }

    $icons = $this->pluginManagerIconPack->getIcons(['do_not_exist']);
    $this->assertEmpty($icons);
  }

  /**
   * Test the IconPackManager::getIcon method.
   */
  public function testGetIcon(): void {
    $icon = $this->pluginManagerIconPack->getIcon(self::TEST_ICON_FULL_ID);
    $this->assertInstanceOf(IconDefinitionInterface::class, $icon);

    $icon = $this->pluginManagerIconPack->getIcon('test_minimal:_do_not_exist_');
    $this->assertNull($icon);
  }

  /**
   * Test the IconPackManager::listIconPackOptions method.
   */
  public function testListIconPackOptions(): void {
    $actual = $this->pluginManagerIconPack->listIconPackOptions();
    $expected = [
      'test_minimal' => 'test_minimal (1)',
      'test_path' => 'Test path (10)',
      'test_svg' => 'Test svg (12)',
      'test_svg_sprite' => 'Test sprite (3)',
      'test_no_settings' => 'test_no_settings (1)',
      'test_settings' => 'Test settings (1)',
      'test_url_path' => 'Test url path (2)',
    ];
    $this->assertEquals($expected, $actual);

    $actual = $this->pluginManagerIconPack->listIconPackOptions(TRUE);
    $expected = [
      'test_minimal' => 'test_minimal (1)',
      'test_path' => 'Test path - Local png files available for test with all metadata. (10)',
      'test_svg' => 'Test svg (12)',
      'test_svg_sprite' => 'Test sprite (3)',
      'test_no_settings' => 'test_no_settings (1)',
      'test_settings' => 'Test settings (1)',
      'test_url_path' => 'Test url path (2)',
    ];
    $this->assertEquals($expected, $actual);
  }

  /**
   * Test the IconPackManager::getExtractorFormDefault method.
   */
  public function testGetExtractorFormDefaults(): void {
    $actual = $this->pluginManagerIconPack->getExtractorFormDefaults('test_settings');
    // @see core/modules/system/tests/modules/icon_test/icon_test.icons.yml
    $expected = [
      'width' => 32,
      'height' => 33,
      'title' => 'Default title',
      'alt' => 'Default alt',
      'select' => 400,
      'boolean' => TRUE,
      'decimal' => 66.66,
      'number' => 30,
    ];
    $this->assertSame($expected, $actual);

    $actual = $this->pluginManagerIconPack->getExtractorFormDefaults('test_no_settings');
    $this->assertSame([], $actual);
  }

  /**
   * Test the IconPackManager::getExtractorPluginForms method.
   */
  public function testGetExtractorPluginForms(): void {
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->disableOriginalConstructor()
      ->getMock();
    $form = [];

    $this->pluginManagerIconPack->getExtractorPluginForms($form, $form_state);

    // @see core/modules/system/tests/modules/icon_test/icon_test.icons.yml
    $this->assertCount(4, array_keys($form));
    $expected = ['test_path', 'test_svg', 'test_svg_sprite', 'test_settings'];
    $this->assertSame($expected, array_keys($form));

    $expected = [
      '#type',
      '#title',
      'width',
      'height',
      'title',
    ];
    $this->assertSame($expected, array_keys($form['test_path']));

    $expected = [
      '#type',
      '#title',
      'width',
      'height',
    ];
    $this->assertSame($expected, array_keys($form['test_svg_sprite']));

    $expected = [
      '#type',
      '#title',
      'width',
      'height',
      'title',
      'alt',
      'select',
      'select_string',
      'boolean',
      'decimal',
      'number',
    ];
    $this->assertSame($expected, array_keys($form['test_settings']));

    // No form if no settings.
    $this->assertArrayNotHasKey('test_no_settings', $form);
  }

  /**
   * Test the IconPackManager::getExtractorPluginForms method.
   */
  public function testGetExtractorPluginFormsWithAllowed(): void {
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->disableOriginalConstructor()
      ->getMock();
    $form = [];

    $allowed_icon_pack['test_svg'] = '';

    $this->pluginManagerIconPack->getExtractorPluginForms($form, $form_state, [], $allowed_icon_pack);

    $this->assertArrayHasKey('test_svg', $form);

    $this->assertArrayNotHasKey('test_minimal', $form);
    $this->assertArrayNotHasKey('test_svg_sprite', $form);
    $this->assertArrayNotHasKey('test_no_icons', $form);
  }

  /**
   * Test the IconPackManager::getExtractorPluginForms method with default.
   */
  public function testGetExtractorPluginFormsWithDefault(): void {
    $form = [
      '#parents' => [],
      'test_settings' => [
        '#parents' => ['test_settings'],
        '#array_parents' => ['test_settings'],
      ],
    ];

    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->disableOriginalConstructor()
      ->onlyMethods(['setValue', 'getValue'])
      ->getMock();
    $this->pluginManagerIconPack->getExtractorPluginForms($form, $form_state);

    // Without default, values are from definition.
    $expected = [
      'width' => 32,
      'height' => 33,
      'title' => 'Default title',
      'alt' => 'Default alt',
      'select' => 400,
      'boolean' => TRUE,
      'decimal' => 66.66,
      'number' => 30,
    ];
    foreach ($expected as $key => $value) {
      $this->assertSame($value, $form['test_settings'][$key]['#default_value']);
    }

    // Test definition without value.
    $this->assertArrayNotHasKey('#default_value', $form['test_svg']['size']);

    $default_settings = ['test_settings' => ['width' => 100, 'height' => 110, 'title' => 'Test']];

    // Test the set/get of default values as 'saved_values'.
    $form_state->expects($this->once())
      ->method('setValue')
      ->with('saved_values', $default_settings['test_settings']);

    $form_state->expects($this->once())
      ->method('getValue')
      ->with('saved_values')
      ->willReturn($default_settings['test_settings']);

    $this->pluginManagerIconPack->getExtractorPluginForms($form, $form_state, $default_settings, ['test_settings' => '']);

    $this->assertSame($default_settings['test_settings']['width'], $form['test_settings']['width']['#default_value']);
    $this->assertSame($default_settings['test_settings']['height'], $form['test_settings']['height']['#default_value']);
    $this->assertSame($default_settings['test_settings']['title'], $form['test_settings']['title']['#default_value']);
  }

  /**
   * Test the IconPackManager::processDefinition method.
   */
  public function testProcessDefinition(): void {
    $relative_path = 'core/modules/system/tests/modules/icon_test';

    $definition = [
      'id' => 'foo',
      'label' => 'Foo',
      'provider' => 'icon_test',
      'extractor' => 'test',
    ];

    $this->pluginManagerIconPack->processDefinition($definition, 'foo');

    $this->assertSame('foo', $definition['id']);
    $this->assertSame('Foo', $definition['label']);

    $this->assertEquals($relative_path, $definition['relative_path']);

    $absolute_path = sprintf('%s/%s', $this->appRoot, $relative_path);
    $this->assertEquals($absolute_path, $definition['absolute_path']);

    $this->assertArrayHasKey('icons', $definition);
    $this->assertEmpty($definition['icons']);

    $definition = [
      'id' => 'foo',
      'provider' => 'icon_test',
      'extractor' => 'test_finder',
      'template' => '{{ icon_id }}',
      'config' => [
        'sources' => ['icons/flat/*.svg'],
      ],
    ];

    $this->pluginManagerIconPack->processDefinition($definition, 'foo');

    $this->assertArrayHasKey('icons', $definition);
    $this->assertNotEmpty($definition['icons']);
    $this->assertCount(5, $definition['icons']);
    foreach ($definition['icons'] as $icon) {
      $this->assertInstanceOf(IconDefinitionInterface::class, $icon);
    }
  }

  /**
   * Test the IconPackManager::processDefinition method with disable pack.
   */
  public function testProcessDefinitionDisabled(): void {
    $definition = [
      'id' => 'foo',
      'enabled' => FALSE,
      'provider' => 'icon_test',
      'extractor' => 'bar',
      'template' => '',
    ];

    $this->pluginManagerIconPack->processDefinition($definition, 'foo');

    $this->assertSame('foo', $definition['id']);

    $this->assertArrayNotHasKey('relative_path', $definition);
    $this->assertArrayNotHasKey('absolute_path', $definition);
    $this->assertArrayNotHasKey('icons', $definition);
  }

  /**
   * Test the IconPackManager::processDefinition method with exception.
   */
  public function testProcessDefinitionExceptionName(): void {
    $definition = ['provider' => 'foo'];
    $this->expectException(IconPackConfigErrorException::class);
    $this->expectExceptionMessage('Invalid icon pack id in: foo, name: $ Not valid !* must contain only lowercase letters, numbers, and underscores.');
    $this->pluginManagerIconPack->processDefinition($definition, '$ Not valid !*');
  }

  /**
   * Test the IconPackManager::processDefinition method with exception.
   */
  public function testProcessDefinitionExceptionRequired(): void {
    $definition = [
      'id' => 'foo',
      'provider' => 'icon_test',
    ];
    $this->pluginManagerIconPack->setValidator();
    $this->expectException(IconPackConfigErrorException::class);
    $this->expectExceptionMessage('icon_test:foo Error in definition `foo`:[extractor] The property extractor is required, [template] The property template is required');
    $this->pluginManagerIconPack->processDefinition($definition, 'foo');
  }

}
