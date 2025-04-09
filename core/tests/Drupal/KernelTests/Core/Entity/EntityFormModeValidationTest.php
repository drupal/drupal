<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;

/**
 * Tests validation of entity_form_mode entities.
 *
 * @group Entity
 * @group Validation
 */
class EntityFormModeValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected static array $propertiesWithOptionalValues = ['description'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('user');

    $this->entity = EntityFormMode::create([
      'id' => 'user.test',
      'label' => 'Test',
      'targetEntityType' => 'user',
    ]);
    $this->entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testImmutableProperties(array $valid_values = []): void {
    $valid_values['id'] = 'user.test_changed';
    $valid_values['targetEntityType'] = 'entity_test';
    parent::testImmutableProperties($valid_values);
  }

  /**
   * {@inheritdoc}
   */
  public static function providerInvalidMachineNameCharacters(): array {
    return [
      'INVALID: contains a space' => ['prefix.space separated', FALSE],
      'INVALID: dash separated' => ['prefix.dash-separated', FALSE],
      'INVALID: uppercase letters' => ['Uppercase.Letters', FALSE],
      'VALID: underscore separated' => ['prefix.underscore_separated', TRUE],
      'VALID: contains numbers' => ['prefix1.part2', TRUE],
    ];
  }

  /**
   * Tests that the entity ID's length is validated if it is a machine name.
   *
   * @param string $prefix
   *   Prefix for machine name.
   */
  public function testMachineNameLength(string $prefix = ''): void {
    // Entity form mode IDs are prefixed by an entity type ID.
    parent::testMachineNameLength('test.');
  }

  /**
   * Tests that description can be NULL, but not empty strings.
   */
  public function testDescriptionCannotBeEmpty(): void {
    $this->entity->set('description', NULL);
    // The entity's getters should cast NULL values to empty strings.
    $this->assertSame('', $this->entity->getDescription());
    // But NULL values should be valid at the config level.
    $this->assertValidationErrors([]);

    // But they cannot be empty strings, because that doesn't make sense.
    $this->entity->set('description', '');
    $this->assertValidationErrors([
      'description' => 'This value should not be blank.',
    ]);
  }

}
