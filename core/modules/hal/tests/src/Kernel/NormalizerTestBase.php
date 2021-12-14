<?php

namespace Drupal\Tests\hal\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Test the HAL normalizer.
 */
abstract class NormalizerTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'entity_test',
    'field',
    'hal',
    'language',
    'serialization',
    'system',
    'text',
    'user',
    'filter',
  ];

  /**
   * The mock serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The format being tested.
   *
   * @var string
   */
  protected $format = 'hal_json';

  /**
   * The class name of the test class.
   *
   * @var string
   */
  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    // If the concrete test sub-class installs the Node or Comment modules,
    // ensure that the node and comment entity schema are created before the
    // field configurations are installed. This is because the entity tables
    // need to be created before the body field storage tables. This prevents
    // trying to create the body field tables twice.
    $class = static::class;
    while ($class) {
      if (property_exists($class, 'modules')) {
        // Only check the modules, if the $modules property was not inherited.
        $rp = new \ReflectionProperty($class, 'modules');
        if ($rp->class == $class) {
          foreach (array_intersect(['node', 'comment'], $class::$modules) as $module) {
            $this->installEntitySchema($module);
          }
        }
      }
      $class = get_parent_class($class);
    }
    $this->installConfig(['field', 'language']);

    // Add German as a language.
    ConfigurableLanguage::create([
      'id' => 'de',
      'label' => 'Deutsch',
      'weight' => -1,
    ])->save();

    // Create the test text field.
    FieldStorageConfig::create([
      'field_name' => 'field_test_text',
      'entity_type' => 'entity_test',
      'type' => 'text',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_text',
      'bundle' => 'entity_test',
      'translatable' => FALSE,
    ])->save();

    // Create the test translatable field.
    FieldStorageConfig::create([
      'field_name' => 'field_test_translatable_text',
      'entity_type' => 'entity_test',
      'type' => 'text',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_translatable_text',
      'bundle' => 'entity_test',
      'translatable' => TRUE,
    ])->save();

    // Create the test entity reference field.
    FieldStorageConfig::create([
      'field_name' => 'field_test_entity_reference',
      'entity_type' => 'entity_test',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'entity_test',
      ],
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_entity_reference',
      'bundle' => 'entity_test',
      'translatable' => TRUE,
    ])->save();

    $this->serializer = $this->container->get('serializer');
  }

}
