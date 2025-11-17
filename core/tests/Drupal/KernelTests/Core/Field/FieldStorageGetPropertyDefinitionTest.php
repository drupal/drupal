<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Field;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests getting property definitions from field storages.
 */
#[Group('Field')]
#[RunTestsInSeparateProcesses]
class FieldStorageGetPropertyDefinitionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'field_test'];

  /**
   * Tests getPropertyDefinition().
   */
  public function testGetPropertyDefinition(): void {
    $this->assertInstanceOf(DataDefinitionInterface::class, BaseFieldDefinition::create('string')->getFieldStorageDefinition()->getPropertyDefinition('value'));

    $this->assertInstanceOf(DataDefinitionInterface::class, FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ])->getPropertyDefinition('value'));
  }

  /**
   * Tests BaseFieldDefinition::getPropertyDefinition() with null.
   */
  #[IgnoreDeprecations]
  public function testBaseFieldGetPropertyDefinitionWithNull(): void {
    $this->expectDeprecation('Calling Drupal\Core\Field\BaseFieldDefinition::getPropertyDefinition() with a non-string $name is deprecated in drupal:11.3.0 and throws an exception in drupal:12.0.0. See https://www.drupal.org/node/3557373');
    $this->assertNull(BaseFieldDefinition::create('string')->getPropertyDefinition(NULL));
  }

  /**
   * Tests FieldStorageConfig::getPropertyDefinition() with null.
   */
  #[IgnoreDeprecations]
  public function testFieldStorageConfigGetPropertyDefinitionWithNull(): void {
    $this->expectDeprecation('Calling Drupal\field\Entity\FieldStorageConfig::getPropertyDefinition() with a non-string $name is deprecated in drupal:11.3.0 and throws an exception in drupal:12.0.0. See https://www.drupal.org/node/3557373');
    $this->assertNull(FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ])->getPropertyDefinition(NULL));
  }

}
