<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests Locale update functions.
 *
 * @group locale
 */
class LocalesLocationAddIndexUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles[] = $this->root . '/core/modules/system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz';
  }

  /**
   * Tests locale_update_10300().
   *
   * @see locale_update_10300
   */
  public function testIndex(): void {
    $this->assertFalse(\Drupal::database()
      ->schema()
      ->indexExists('locales_location', 'type_name'));

    // Run updates and test them.
    $this->runUpdates();

    $this->assertTrue(\Drupal::database()
      ->schema()
      ->indexExists('locales_location', 'type_name'));
  }

  /**
   * Tests locale_update_10300().
   *
   * @see locale_update_10300
   */
  public function testExistingIndex(): void {
    $spec = [];
    $spec['locales_location'] = [
      'description' => 'Location information for source strings.',
      'fields' => [
        'lid' => [
          'type' => 'serial',
          'not null' => TRUE,
          'description' => 'Unique identifier of this location.',
        ],
        'sid' => [
          'type' => 'int',
          'not null' => TRUE,
          'description' => 'Unique identifier of this string.',
        ],
        'type' => [
          'type' => 'varchar_ascii',
          'length' => 50,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The location type (file, config, path, etc).',
        ],
        'name' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Type dependent location information (file name, path, etc).',
        ],
        'version' => [
          'type' => 'varchar_ascii',
          'length' => 20,
          'not null' => TRUE,
          'default' => 'none',
          'description' => 'Version of Drupal where the location was found.',
        ],
      ],
      'primary key' => ['lid'],
      'foreign keys' => [
        'locales_source' => [
          'table' => 'locales_source',
          'columns' => ['sid' => 'lid'],
        ],
      ],
      'indexes' => [
        'string_type' => ['sid', 'type'],
        'type_name' => ['type', 'name'],
      ],
    ];
    \Drupal::database()->schema()->addIndex('locales_location', 'type_name', ['type', 'name', 'sid'], $spec['locales_location']);

    // Run updates and test them.
    $this->runUpdates();

    // Ensure the update runs successfully even if an index existed prior to
    // the update.
    $schema = \Drupal::database()->schema();
    $this->assertTrue($schema->indexExists('locales_location', 'type_name'));
  }

}
