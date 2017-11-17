<?php

namespace Drupal\Tests\file\Functional\Formatter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides methods specifically for testing File module's media formatter's.
 */
abstract class FileMediaFormatterTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'file',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['view test entity']));
  }

  /**
   * Creates a file field and set's the correct formatter.
   *
   * @param string $formatter
   *   The formatter ID.
   * @param string $file_extensions
   *   The file extensions of the new field.
   * @param array $formatter_settings
   *   Settings for the formatter.
   *
   * @return \Drupal\field\Entity\FieldConfig
   *   Newly created file field.
   */
  protected function createMediaField($formatter, $file_extensions, array $formatter_settings = []) {
    $entity_type = $bundle = 'entity_test';
    $field_name = Unicode::strtolower($this->randomMachineName());

    FieldStorageConfig::create([
      'entity_type' => $entity_type,
      'field_name' => $field_name,
      'type' => 'file',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    $field_config = FieldConfig::create([
      'entity_type' => $entity_type,
      'field_name' => $field_name,
      'bundle' => $bundle,
      'settings' => [
        'file_extensions' => trim($file_extensions),
      ],
    ]);
    $field_config->save();

    $display = entity_get_display('entity_test', 'entity_test', 'full');
    $display->setComponent($field_name, [
      'type' => $formatter,
      'settings' => $formatter_settings,
    ])->save();

    return $field_config;
  }

  /**
   * Data provider for testRender.
   *
   * @return array
   *   An array of data arrays.
   *   The data array contains:
   *     - The number of expected HTML tags.
   *     - An array of settings for the field formatter.
   */
  public function dataProvider() {
    return [
      [2, []],
      [1, ['multiple_file_display_type' => 'sources']],
    ];
  }

}
