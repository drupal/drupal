<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Component\Uuid\Uuid;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Tests default values for entity fields.
 *
 * @group Entity
 */
class EntityFieldDefaultValueTest extends EntityKernelTestBase {

  /**
   * The UUID object to be used for generating UUIDs.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
   *
   * @internal
   */
  protected function assertDefaultValues(string $entity_type_id): void {
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type_id)
      ->create();
    $definition = $this->entityTypeManager->getDefinition($entity_type_id);
    $langcode_key = $definition->getKey('langcode');
    $this->assertEquals('en', $entity->{$langcode_key}->value, new FormattableMarkup('%entity_type: Default language', ['%entity_type' => $entity_type_id]));
    $this->assertTrue(Uuid::isValid($entity->uuid->value), new FormattableMarkup('%entity_type: Default UUID', ['%entity_type' => $entity_type_id]));
    $this->assertEquals([], $entity->name->getValue(), 'Field has one empty value by default.');
  }

  /**
   * Tests custom default value callbacks.
   */
  public function testDefaultValueCallback() {
    $entity = $this->entityTypeManager->getStorage('entity_test_default_value')->create();
    // The description field has a default value callback for testing, see
    // entity_test_field_default_value().
    $string = 'description_' . $entity->language()->getId();
    $expected = [
      [
        'shape' => "shape:0:$string",
        'color' => "color:0:$string",
      ],
      [
        'shape' => "shape:1:$string",
        'color' => "color:1:$string",
      ],
    ];
    $this->assertEquals($expected, $entity->description->getValue());
  }

}
