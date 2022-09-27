<?php

namespace Drupal\Tests\serialization\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Helper base class to set up some test fields for serialization testing.
 */
abstract class NormalizerTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'serialization',
    'system',
    'field',
    'entity_test',
    'text',
    'filter',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_mulrev');
    $this->installEntitySchema('user');
    $this->installConfig(['field']);
    \Drupal::moduleHandler()->invoke('rest', 'install');

    // Auto-create a field for testing.
    FieldStorageConfig::create([
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_text',
      'type' => 'text',
      'cardinality' => 1,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_text',
      'bundle' => 'entity_test_mulrev',
      'label' => 'Test text-field',
      'widget' => [
        'type' => 'text_textfield',
        'weight' => 0,
      ],
    ])->save();
  }

}
