<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel;

use Drupal\migrate\MigrateExecutable;

/**
 * Tests rolling back of configuration objects.
 *
 * @group migrate
 */
class MigrateConfigRollbackTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'language', 'config_translation'];

  /**
   * Tests rolling back configuration.
   */
  public function testConfigRollback(): void {
    // Use system.site configuration to demonstrate importing and rolling back
    // configuration.
    $variable = [
      [
        'id' => 'site_name',
        'site_name' => 'Some site',
        'site_slogan' => 'Awesome slogan',
      ],
    ];
    $ids = [
      'id' =>
        [
          'type' => 'string',
        ],
    ];
    $definition = [
      'id' => 'config',
      'migration_tags' => ['Import and rollback test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => $variable,
        'ids' => $ids,
      ],
      'process' => [
        'name' => 'site_name',
        'slogan' => 'site_slogan',
      ],
      'destination' => [
        'plugin' => 'config',
        'config_name' => 'system.site',
      ],
    ];

    /** @var \Drupal\migrate\Plugin\Migration $config_migration */
    $config_migration = \Drupal::service('plugin.manager.migration')
      ->createStubMigration($definition);
    $config_id_map = $config_migration->getIdMap();

    // Rollback is not enabled for configuration translations.
    $this->assertFalse($config_migration->getDestinationPlugin()->supportsRollback());

    // Import and validate config entities were created.
    $config_executable = new MigrateExecutable($config_migration, $this);
    $config_executable->import();
    $config = $this->config('system.site');
    $this->assertSame('Some site', $config->get('name'));
    $this->assertSame('Awesome slogan', $config->get('slogan'));
    $map_row = $config_id_map->getRowBySource(['id' => $variable[0]['id']]);
    $this->assertNotNull($map_row['destid1']);

    // Rollback and verify the configuration changes are still there.
    $config_executable->rollback();
    $config = $this->config('system.site');
    $this->assertSame('Some site', $config->get('name'));
    $this->assertSame('Awesome slogan', $config->get('slogan'));
    // Confirm the map row is deleted.
    $this->assertFalse($config_id_map->getRowBySource(['id' => $variable[0]['id']]));

    // We use system configuration to demonstrate importing and rolling back
    // configuration translations.
    $i18n_variable = [
      [
        'id' => 'site_name',
        'language' => 'fr',
        'site_name' => 'fr - Some site',
        'site_slogan' => 'fr - Awesome slogan',
      ],
      [
        'id' => 'site_name',
        'language' => 'is',
        'site_name' => 'is - Some site',
        'site_slogan' => 'is - Awesome slogan',
      ],
    ];
    $ids = [
      'id' =>
        [
          'type' => 'string',
        ],
      'language' =>
        [
          'type' => 'string',
        ],
    ];
    $definition = [
      'id' => 'i18n_config',
      'migration_tags' => ['Import and rollback test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => $i18n_variable,
        'ids' => $ids,
      ],
      'process' => [
        'langcode' => 'language',
        'name' => 'site_name',
        'slogan' => 'site_slogan',
      ],
      'destination' => [
        'plugin' => 'config',
        'config_name' => 'system.site',
        'translations' => 'true',
      ],
    ];

    $config_migration = \Drupal::service('plugin.manager.migration')
      ->createStubMigration($definition);
    $config_id_map = $config_migration->getIdMap();

    // Rollback is enabled for configuration translations.
    $this->assertTrue($config_migration->getDestinationPlugin()->supportsRollback());

    // Import and validate config entities were created.
    $config_executable = new MigrateExecutable($config_migration, $this);
    $config_executable->import();

    $language_manager = \Drupal::service('language_manager');
    foreach ($i18n_variable as $row) {
      $langcode = $row['language'];
      /** @var \Drupal\language\Config\LanguageConfigOverride $config_translation */
      $config_translation = $language_manager->getLanguageConfigOverride($langcode, 'system.site');
      $this->assertSame($row['site_name'], $config_translation->get('name'));
      $this->assertSame($row['site_slogan'], $config_translation->get('slogan'));
      $map_row = $config_id_map->getRowBySource(['id' => $row['id'], 'language' => $row['language']]);
      $this->assertNotNull($map_row['destid1']);
    }

    // Rollback and verify the translation have been removed.
    $config_executable->rollback();
    foreach ($i18n_variable as $row) {
      $langcode = $row['language'];
      $config_translation = $language_manager->getLanguageConfigOverride($langcode, 'system.site');
      $this->assertNull($config_translation->get('name'));
      $this->assertNull($config_translation->get('slogan'));
      // Confirm the map row is deleted.
      $map_row = $config_id_map->getRowBySource(['id' => $row['id'], 'language' => $langcode]);
      $this->assertFalse($map_row);
    }

    // Test that the configuration is still present.
    $config = $this->config('system.site');
    $this->assertSame('Some site', $config->get('name'));
    $this->assertSame('Awesome slogan', $config->get('slogan'));
  }

}
