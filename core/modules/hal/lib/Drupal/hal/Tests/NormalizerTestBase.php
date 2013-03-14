<?php

/**
 * @file
 * Contains \Drupal\hal\Tests\NormalizerTestBase.
 */

namespace Drupal\hal\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Test the HAL normalizer.
 */
abstract class NormalizerTestBase extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test', 'entity_reference', 'field', 'field_sql_storage', 'hal', 'language', 'rest', 'serialization', 'system', 'text', 'user');

  /**
   * The format being tested.
   *
   * @var string
   */
  protected $format = 'hal_json';

  /**
   * Overrides \Drupal\simpletest\DrupalUnitTestBase::setup().
   */
  function setUp() {
    parent::setUp();
    $this->installSchema('system', array('variable', 'url_alias'));
    $this->installSchema('field', array('field_config', 'field_config_instance'));
    $this->installSchema('user', array('users'));
    $this->installSchema('language', array('language'));
    $this->installSchema('entity_test', array('entity_test'));

    // Add English as a language.
    $english = new Language(array(
      'langcode' => 'en',
      'name' => 'English',
    ));
    language_save($english);
    // Add German as a language.
    $german = new Language(array(
      'langcode' => 'de',
      'name' => 'Deutsch',
    ));
    language_save($german);

    // Create the test text field.
    $field = array(
      'field_name' => 'field_test_text',
      'type' => 'text',
      'cardinality' => 1,
      'translatable' => FALSE,
    );
    field_create_field($field);
    $instance = array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_text',
      'bundle' => 'entity_test',
    );
    field_create_instance($instance);

    // Create the test entity reference field.
    $field = array(
      'translatable' => TRUE,
      'settings' => array(
        'target_type' => 'entity_test',
      ),
      'field_name' => 'field_test_entity_reference',
      'type' => 'entity_reference',
    );
    field_create_field($field);
    $instance = array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_entity_reference',
      'bundle' => 'entity_test',
    );
    field_create_instance($instance);
  }

}
