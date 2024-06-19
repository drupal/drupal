<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\EntityType;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests general features of entity types.
 *
 * @group Entity
 */
class EntityTypeTest extends KernelTestBase {

  /**
   * Sets up an EntityType object for a given set of values.
   *
   * @param array $definition
   *   An array of values to use for the EntityType.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   */
  protected function setUpEntityType($definition) {
    $definition += [
      'id' => 'example_entity_type',
    ];
    return new EntityType($definition);
  }

  /**
   * Tests that the EntityType object can be serialized.
   */
  public function testIsSerializable(): void {
    $entity_type = $this->setUpEntityType([]);

    $translation_service = new class () extends TranslationManager {

      /**
       * Constructs a UnserializableTranslationManager object.
       */
      public function __construct() {
      }

      /**
       * Always throw an exception.
       */
      public function __serialize(): array {
        throw new \Exception();
      }

    };

    $this->container->set('bar', $translation_service);
    $entity_type->setStringTranslation($this->container->get('string_translation'));

    // This should not throw an exception.
    $tmp = serialize($entity_type);
    $entity_type = unserialize($tmp);
    // And this should have the correct id.
    $this->assertEquals('example_entity_type', $entity_type->id());
  }

}
