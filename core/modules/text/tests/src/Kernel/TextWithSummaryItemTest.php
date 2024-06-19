<?php

declare(strict_types=1);

namespace Drupal\Tests\text\Kernel;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests using entity fields of the text summary field type.
 *
 * @group text
 */
class TextWithSummaryItemTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['filter'];

  /**
   * Field storage entity.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * Field entity.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');

    // Create the necessary formats.
    $this->installConfig(['filter']);
    FilterFormat::create([
      'format' => 'no_filters',
      'name' => 'No filters',
      'filters' => [],
    ])->save();
  }

  /**
   * Tests processed properties.
   */
  public function testCrudAndUpdate(): void {
    $entity_type = 'entity_test';
    $this->createField($entity_type);

    // Create an entity with a summary and no text format.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($entity_type);
    $entity = $storage->create();
    $entity->summary_field->value = $value = $this->randomMachineName();
    $entity->summary_field->summary = $summary = $this->randomMachineName();
    $entity->summary_field->format = NULL;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $entity = $storage->load($entity->id());
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->summary_field);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->summary_field[0]);
    $this->assertEquals($value, $entity->summary_field->value);
    $this->assertEquals($summary, $entity->summary_field->summary);
    $this->assertNull($entity->summary_field->format);
    // Even if no format is given, if text processing is enabled, the default
    // format is used.
    $this->assertSame("<p>{$value}</p>\n", (string) $entity->summary_field->processed);
    $this->assertSame("<p>{$summary}</p>\n", (string) $entity->summary_field->summary_processed);

    // Change the format, this should update the processed properties.
    $entity->summary_field->format = 'no_filters';
    $this->assertSame($value, (string) $entity->summary_field->processed);
    $this->assertSame($summary, (string) $entity->summary_field->summary_processed);

    // Test the generateSampleValue() method.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create();
    $entity->summary_field->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

  /**
   * Creates a text_with_summary field storage and field.
   *
   * @param string $entity_type
   *   Entity type for which the field should be created.
   */
  protected function createField($entity_type) {
    // Create a field .
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => 'summary_field',
      'entity_type' => $entity_type,
      'type' => 'text_with_summary',
      'settings' => [
        'max_length' => 10,
      ],
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => $entity_type,
    ]);
    $this->field->save();
  }

}
