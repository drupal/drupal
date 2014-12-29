<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityFieldDefaultValueTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Component\Uuid\Uuid;
use Drupal\Component\Utility\String;
use Drupal\Core\Language\LanguageInterface;

/**
 * Tests default values for entity fields.
 *
 * @group Entity
 */
class EntityFieldDefaultValueTest extends EntityUnitTestBase  {

  /**
   * The UUID object to be used for generating UUIDs.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  protected function setUp() {
    parent::setUp();
    // Initiate the generator object.
    $this->uuid = $this->container->get('uuid');
  }

  /**
   * Tests default values on entities and fields.
   */
  public function testDefaultValues() {
    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->assertDefaultValues($entity_type);
    }
  }

  /**
   * Executes a test set for a defined entity type.
   *
   * @param string $entity_type_id
   *   The entity type to run the tests with.
   */
  protected function assertDefaultValues($entity_type_id) {
    $entity = entity_create($entity_type_id);
    $definition = $this->entityManager->getDefinition($entity_type_id);
    $langcode_key = $definition->getKey('langcode');
    $this->assertEqual($entity->{$langcode_key}->value, 'en', String::format('%entity_type: Default language', array('%entity_type' => $entity_type_id)));
    $this->assertTrue(Uuid::isValid($entity->uuid->value), String::format('%entity_type: Default UUID', array('%entity_type' => $entity_type_id)));
    $this->assertEqual($entity->name->getValue(), array(0 => array('value' => NULL)), 'Field has one empty value by default.');
  }

  /**
   * Tests custom default value callbacks.
   */
  public function testDefaultValueCallback() {
    $entity = $this->entityManager->getStorage('entity_test_default_value')->create();
    // The description field has a default value callback for testing, see
    // entity_test_field_default_value().
    $string = 'description_' . $entity->language()->getId();
    $expected = array(
      array(
        'shape' => "shape:0:$string",
        'color' => "color:0:$string",
      ),
      array(
        'shape' => "shape:1:$string",
        'color' => "color:1:$string",
      ),
    );
    $this->assertEqual($entity->description->getValue(), $expected);
  }

}
