<?php

namespace Drupal\Tests\text\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the text formatters functionality.
 *
 * @group text
 */
class TextFormatterTest extends EntityKernelTestBase {

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    FilterFormat::create([
      'format' => 'my_text_format',
      'name' => 'My text format',
      'filters' => [
        'filter_autop' => [
          'module' => 'filter',
          'status' => TRUE,
        ],
      ],
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'formatted_text',
      'entity_type' => $this->entityType,
      'type' => 'text',
      'settings' => [],
    ])->save();
    FieldConfig::create([
      'entity_type' => $this->entityType,
      'bundle' => $this->bundle,
      'field_name' => 'formatted_text',
      'label' => 'Filtered text',
    ])->save();
  }

  /**
   * Tests all text field formatters.
   */
  public function testFormatters() {
    $formatters = [
      'text_default',
      'text_trimmed',
      'text_summary_or_trimmed',
    ];

    // Create the entity to be referenced.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($this->entityType)
      ->create(['name' => $this->randomMachineName()]);
    $entity->formatted_text = [
      'value' => 'Hello, world!',
      'format' => 'my_text_format',
    ];
    $entity->save();

    foreach ($formatters as $formatter) {
      // Verify the text field formatter's render array.
      $build = $entity->get('formatted_text')->view(['type' => $formatter]);
      \Drupal::service('renderer')->renderRoot($build[0]);
      $this->assertEqual($build[0]['#markup'], "<p>Hello, world!</p>\n");
      $this->assertEqual($build[0]['#cache']['tags'], FilterFormat::load('my_text_format')->getCacheTags(), format_string('The @formatter formatter has the expected cache tags when formatting a formatted text field.', ['@formatter' => $formatter]));
    }
  }

}
