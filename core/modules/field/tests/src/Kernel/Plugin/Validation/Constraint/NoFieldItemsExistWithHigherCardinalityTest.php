<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel\Plugin\Validation\Constraint;

use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Plugin\Validation\Constraint\NoFieldItemsExistWithHigherCardinalityValidator;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests NoFieldItemsExistWithHigherCardinality validation.
 */
#[CoversClass(NoFieldItemsExistWithHigherCardinalityValidator::class)]
#[Group('field')]
#[RunTestsInSeparateProcesses]
class NoFieldItemsExistWithHigherCardinalityTest extends FieldKernelTestBase {

  /**
   * Tests validation error and message when cardinality is set too low.
   */
  public function testValidation(): void {
    // Create a field with a cardinality of 2 to show that we are counting
    // entities and not rows in a table.
    /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_int',
      'entity_type' => 'entity_test',
      'type' => 'integer',
      'cardinality' => 2,
    ]);
    $field_storage->save();
    $field_config = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ]);
    $field_config->save();

    $entity = EntityTest::create();
    $entity->field_int[] = mt_rand(1, 99);
    $entity->field_int[] = mt_rand(1, 99);
    $entity->name[] = $this->randomMachineName();
    $entity->save();

    $field_storage->set('cardinality', 1);

    $this->expectException(SchemaIncompleteException::class);
    $this->expectExceptionMessage('Schema errors for field.storage.entity_test.field_int with the following errors: 0 [cardinality] The field &#039;field_int&#039; of entity type &#039;entity_test&#039; has more entries (2) than the cardinality (1) allows');
    $field_storage->save();
  }

}
