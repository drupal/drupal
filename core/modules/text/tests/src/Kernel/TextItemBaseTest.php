<?php

declare(strict_types=1);

namespace Drupal\Tests\text\Kernel;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatRepositoryInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\text\Plugin\Field\FieldType\TextItemBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests TextItemBase.
 */
#[CoversClass(TextItemBase::class)]
#[Group('text')]
#[RunTestsInSeparateProcesses]
class TextItemBaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter', 'text', 'entity_test', 'field', 'user'];

  /**
   * Tests creation of sample values.
   *
   * @legacy-covers ::generateSampleValue
   */
  #[DataProvider('providerTextFieldSampleValue')]
  public function testTextFieldSampleValue(int $max_length, array $formats, string $expected_format): void {
    // Install filter config so plain_text is registered as the site fallback.
    $this->installConfig(['filter']);
    $this->assertSame('plain_text', \Drupal::service(FilterFormatRepositoryInterface::class)->getFallbackFormatId());

    // Create a text field.
    $field_definition = BaseFieldDefinition::create('text')
      ->setTargetEntityTypeId('foo');

    foreach ($formats as $format_id) {
      if ($format_id === 'plain_text') {
        continue;
      }
      FilterFormat::create([
        'format' => $format_id,
        'name' => $format_id,
      ])->save();
    }
    if (!empty($formats)) {
      $field_definition->setSetting('allowed_formats', $formats);
    }

    // Ensure testing of max_lengths from 1 to 3 because generateSampleValue
    // creates a sentence with a maximum number of words set to 1/3 of the
    // max_length of the field.
    $field_definition->setSetting('max_length', $max_length);
    $sample_value = TextItemBase::generateSampleValue($field_definition);
    $this->assertEquals($max_length, strlen($sample_value['value']));
    $this->assertSame($expected_format, $sample_value['format']);
  }

  /**
   * Data provider for testTextFieldSampleValue.
   */
  public static function providerTextFieldSampleValue(): array {
    return [
      [
        1,
        ['format1'],
        'format1',
      ],
      [
        2,
        ['format2'],
        'format2',
      ],
      [
        3,
        ['format1', 'format2'],
        'format1',
      ],
      [
        4,
        ['plain_text', 'format1'],
        'plain_text',
      ],
      [
        5,
        ['format1', 'plain_text'],
        'plain_text',
      ],
      [
        6,
        [],
        'plain_text',
      ],
    ];
  }

  /**
   * Tests calculate dependencies.
   */
  public function testCalculateDependencies(): void {
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $format = FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
    ]);
    $format->save();
    $fieldName = $this->randomMachineName();
    $field_storage = FieldStorageConfig::create([
      'field_name' => $fieldName,
      'entity_type' => 'entity_test',
      'type' => 'text',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_name' => $fieldName,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'settings' => [
        'allowed_formats' => [$format->id()],
      ],
    ]);
    $field->save();

    $field->calculateDependencies();
    $this->assertEquals([
      'module' => [
        'entity_test',
        'text',
      ],
      'config' => [
        "field.storage.entity_test.$fieldName",
        'filter.format.test_format',
      ],
    ], $field->getDependencies());
  }

}
