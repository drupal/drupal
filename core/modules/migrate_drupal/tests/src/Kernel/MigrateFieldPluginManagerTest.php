<?php

namespace Drupal\Tests\migrate_drupal\Kernel;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Tests the field plugin manager.
 *
 * @group migrate_drupal
 * @coversDefaultClass \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManager
 */
class MigrateFieldPluginManagerTest extends MigrateDrupalTestBase {

  /**
   * The field plugin manager.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'datetime',
    'system',
    'user',
    'field',
    'migrate_drupal',
    'options',
    'file',
    'image',
    'text',
    'link',
    'migrate_field_plugin_manager_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->pluginManager = $this->container->get('plugin.manager.migrate.field');
  }

  /**
   * Tests that the correct MigrateField plugins are used.
   *
   * @covers ::getPluginIdFromFieldType
   */
  public function testPluginSelection() {
    $this->assertSame('link', $this->pluginManager->getPluginIdFromFieldType('link', ['core' => 6]));
    $this->assertSame('link_field', $this->pluginManager->getPluginIdFromFieldType('link_field', ['core' => 7]));
    $this->assertSame('image', $this->pluginManager->getPluginIdFromFieldType('image', ['core' => 7]));
    $this->assertSame('file', $this->pluginManager->getPluginIdFromFieldType('file', ['core' => 7]));
    $this->assertSame('d6_file', $this->pluginManager->getPluginIdFromFieldType('file', ['core' => 6]));
    $this->assertSame('d6_text', $this->pluginManager->getPluginIdFromFieldType('text', ['core' => 6]));
    $this->assertSame('d7_text', $this->pluginManager->getPluginIdFromFieldType('text', ['core' => 7]));

    // Test that the deprecated d6 'date' plugin is not returned.
    $this->assertSame('datetime', $this->pluginManager->getPluginIdFromFieldType('date', ['core' => 6]));

    // Test fallback when no core version is specified.
    $this->assertSame('d6_no_core_version_specified', $this->pluginManager->getPluginIdFromFieldType('d6_no_core_version_specified', ['core' => 6]));
  }

  /**
   * Tests that a PluginNotFoundException is thrown when a plugin isn't found.
   *
   * @covers ::getPluginIdFromFieldType
   * @dataProvider nonExistentPluginExceptionsData
   */
  public function testNonExistentPluginExceptions($core, $field_type) {
    $this->setExpectedException(PluginNotFoundException::class, sprintf("Plugin ID '%s' was not found.", $field_type));
    $this->pluginManager->getPluginIdFromFieldType($field_type, ['core' => $core]);
  }

  /**
   * Provides data for testNonExistentPluginExceptions.
   *
   * @return array
   *   The data.
   */
  public function nonExistentPluginExceptionsData() {
    return [
      'D7 Filefield' => [
        'core' => 7,
        'field_type' => 'filefield',
      ],
      'D6 linkfield' => [
        'core' => 6,
        'field_type' => 'link_field',
      ],
      'D7 link' => [
        'core' => 7,
        'field_type' => 'link',
      ],
      'D7 no core version' => [
        'core' => 7,
        'field_type' => 'd6_no_core_version_specified',
      ],
    ];
  }

  /**
   * Tests that deprecated plugins can still be directly created.
   *
   * Tests that a deprecated plugin triggers an error on instantiation. This
   * test has an implicit assertion that the deprecation error will be triggered
   * and does not need an explicit assertion to pass.
   *
   * @covers ::createInstance
   * @group legacy
   * @expectedDeprecation DateField is deprecated in Drupal 8.4.x and will be removed before Drupal 9.0.x. Use \Drupal\datetime\Plugin\migrate\field\DateField instead.
   */
  public function testDeprecatedPluginDirectAccess() {
    $this->pluginManager->createInstance('date');
  }

  /**
   * Tests that plugins with no explicit weight are given a weight of 0.
   */
  public function testDefaultWeight() {
    $definitions = $this->pluginManager->getDefinitions();
    $deprecated_plugins = [
      'date',
    ];
    foreach ($definitions as $id => $definition) {
      $this->assertArrayHasKey('weight', $definition);
      if (in_array($id, $deprecated_plugins, TRUE)) {
        $this->assertSame(9999999, $definition['weight']);
      }
      else {
        $this->assertSame(0, $definition['weight']);
      }
    }
  }

}
