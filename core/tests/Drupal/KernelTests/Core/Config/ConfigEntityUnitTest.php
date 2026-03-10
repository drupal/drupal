<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Unit tests for configuration entity base methods.
 */
#[Group('config')]
#[RunTestsInSeparateProcesses]
class ConfigEntityUnitTest extends KernelTestBase {

  /**
   * Exempt from strict schema checking.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test'];

  /**
   * The config_test entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->storage = $this->container->get('entity_type.manager')->getStorage('config_test');
  }

  /**
   * Tests storage methods.
   */
  public function testStorageMethods(): void {
    $entity_type = \Drupal::entityTypeManager()->getDefinition('config_test');

    // Test the static extractID() method.
    $expected_id = 'test_id';
    $config_name = $entity_type->getConfigPrefix() . '.' . $expected_id;
    $storage = $this->storage;
    $this->assertSame($expected_id, $storage::getIDFromConfigName($config_name, $entity_type->getConfigPrefix()));

    // Create three entities, two with the same style.
    $style = $this->randomMachineName(8);
    for ($i = 0; $i < 2; $i++) {
      $entity = $this->storage->create([
        'id' => $this->randomMachineName(),
        'label' => $this->randomString(),
        'style' => $style,
      ]);
      $entity->save();
    }
    $entity = $this->storage->create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      // Use a different length for the entity to ensure uniqueness.
      'style' => $this->randomMachineName(9),
    ]);
    $entity->save();

    // Ensure that the configuration entity can be loaded by UUID.
    $entity_loaded_by_uuid = \Drupal::service('entity.repository')->loadEntityByUuid($entity_type->id(), $entity->uuid());
    if (!$entity_loaded_by_uuid) {
      $this->fail(sprintf("Failed to load '%s' entity ID '%s' by UUID '%s'.", $entity_type->id(), $entity->id(), $entity->uuid()));
    }
    // Compare UUIDs as the objects are not identical since
    // $entity->enforceIsNew is FALSE and $entity_loaded_by_uuid->enforceIsNew
    // is NULL.
    $this->assertSame($entity->uuid(), $entity_loaded_by_uuid->uuid());

    $entities = $this->storage->loadByProperties();
    $this->assertCount(3, $entities, 'Three entities are loaded when no properties are specified.');

    $entities = $this->storage->loadByProperties(['style' => $style]);
    $this->assertCount(2, $entities, 'Two entities are loaded when the style property is specified.');

    // Assert that both returned entities have a matching style property.
    foreach ($entities as $entity) {
      $this->assertSame($style, $entity->get('style'), 'The loaded entity has the correct style value specified.');
    }

    // Test that schema type enforcement.
    $entity = $this->storage->create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'style' => 999,
    ]);
    $entity->save();
    $this->assertSame('999', $entity->style);
  }

  /**
   * Tests the legacy trusted data behavior.
   */
  #[IgnoreDeprecations]
  public function testTrustedDataDeprecations(): void {
    $this->expectUserDeprecationMessage('Drupal\\Core\\Config\\Entity\\ConfigEntityBase::trustData() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3348180');
    $this->expectUserDeprecationMessage('Calling Drupal\\Core\\Config\\Config::save() with the $has_trusted_data argument is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3348180');
    // Test that schema type enforcement.
    $entity = $this->storage->create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'style' => 999,
    ]);
    $this->assertInstanceOf(ConfigEntityInterface::class, $entity);
    $entity->trustData();
    $this->assertTrue($entity->hasTrustedData());
    $entity->save();
    $this->assertFalse($entity->hasTrustedData());
    $this->assertSame(999, $entity->style);
  }

}
