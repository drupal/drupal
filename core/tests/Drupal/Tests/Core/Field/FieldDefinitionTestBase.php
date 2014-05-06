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
    $namespace_path = $this->getNamespacePath();
    // Suppport both PSR-0 and PSR-4 directory layouts.
    $module_name = basename($namespace_path);
    if ($module_name == 'src') {
      $module_name = basename($module_name);
    }
    $namespaces = new \ArrayObject(array(
      'Drupal\\' . $module_name => $namespace_path,
    ));

    $language_manager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $language_manager->expects($this->once())
      ->method('getCurrentLanguage')
      ->will($this->returnValue(new Language(array('id' => 'en'))));
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $module_handler->expects($this->once())
      ->method('moduleExists')
      ->with($module_name)
      ->will($this->returnValue(TRUE));
    $plugin_manager = new FieldTypePluginManager(
      $namespaces,
      $this->getMock('Drupal\Core\Cache\CacheBackendInterface'),
      $language_manager,
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
   * Returns the path to the module's classes.
   *
   * Depending on whether the module follows the PSR-0 or PSR-4 directory layout
   * this should be either /path/to/module/lib/Drupal/mymodule or
   * /path/to/module/src.
   *
   * @return string
   *   The path to the module's classes.
   */
  abstract protected function getNamespacePath();

}
