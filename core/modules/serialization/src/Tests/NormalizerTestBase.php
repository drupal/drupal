<?php

/**
 * @file
 * Contains \Drupal\serialization\Tests\NormalizerTestBase.
 */

namespace Drupal\serialization\Tests;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

abstract class NormalizerTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('serialization', 'system', 'field', 'entity_test', 'text', 'filter', 'user');

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_mulrev');
    $this->installEntitySchema('user');
    $this->installSchema('system', array('url_alias', 'router'));
    $this->installConfig(array('field'));
    \Drupal::service('router.builder')->rebuild();
    \Drupal::moduleHandler()->invoke('rest', 'install');

    // Auto-create a field for testing.
    FieldstorageConfig::create(array(
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_text',
      'type' => 'text',
      'cardinality' => 1,
      'translatable' => FALSE,
    ))->save();
    FieldConfig::create(array(
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_text',
      'bundle' => 'entity_test_mulrev',
      'label' => 'Test text-field',
      'widget' => array(
        'type' => 'text_textfield',
        'weight' => 0,
      ),
    ))->save();
  }

}
