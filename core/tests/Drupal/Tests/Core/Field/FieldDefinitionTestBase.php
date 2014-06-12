<?php
/**
 * @file
 * Contains \Drupal\Tests\Core\Fied\FieldDefinitionTestBase.
 */

namespace Drupal\Tests\Core\Field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;

/**
 * Provides a test base class for testing field definitions.
 */
abstract class FieldDefinitionTestBase extends UnitTestCase {

  /**
   * The field definition used in this test.
   *
   * @var \Drupal\Core\Field\FieldDefinition
   */
  protected $definition;

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    // getModuleAndPath() returns an array of the module name and directory.
    list($module_name, $module_dir) = $this->getModuleAndPath();

    $namespaces = new \ArrayObject();
    $namespaces["Drupal\\$module_name"] = $module_dir . '/src';

    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $module_handler->expects($this->once())
      ->method('moduleExists')
      ->with($module_name)
      ->will($this->returnValue(TRUE));
    $plugin_manager = new FieldTypePluginManager(
      $namespaces,
      $this->getMock('Drupal\Core\Cache\CacheBackendInterface'),
      $module_handler
    );

    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $plugin_manager);
    // The 'string_translation' service is used by the @Translation annotation.
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->definition = FieldDefinition::create($this->getPluginId());
  }

  /**
   * Returns the plugin ID of the tested field type.
   *
   * @return string
   *   The plugin ID.
   */
  abstract protected function getPluginId();

  /**
   * Returns the module name and the module directory for the plugin.
   *
   * drupal_get_path() cannot be used here, because it is not available in
   * Drupal PHPUnit tests.
   *
   * @return array
   *   A one-dimensional array containing the following strings:
   *   - The module name.
   *   - The module directory, e.g. DRUPAL_CORE . 'core/modules/path'.
   */
  abstract protected function getModuleAndPath();

}
