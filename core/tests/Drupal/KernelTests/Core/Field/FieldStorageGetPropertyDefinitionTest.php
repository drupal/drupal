<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Field;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
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

}
